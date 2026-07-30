<?php

namespace App\Repositories\epasien\menu\FasilitasTarif;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KamarRepository
{
    public function paginateRooms(
        ?string $status,
        ?string $class,
        ?string $search,
        int $perPage
    ): LengthAwarePaginator {
        return $this->roomQuery()
            ->select(
                'kamar.kd_kamar',
                'kamar.kd_bangsal',
                'bangsal.nm_bangsal',
                'kamar.trf_kamar',
                'kamar.status',
                'kamar.kelas'
            )
            ->when(
                $status !== null,
                fn (Builder $query) => $query->where(
                    'kamar.status',
                    $status
                )
            )
            ->when(
                $class !== null,
                fn (Builder $query) => $query->where(
                    'kamar.kelas',
                    $class
                )
            )
            ->when(
                $search !== null,
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('kamar.kd_kamar', 'like', '%'.$search.'%')
                            ->orWhere('bangsal.nm_bangsal', 'like', '%'.$search.'%')
                            ->orWhere('kamar.kelas', 'like', '%'.$search.'%');
                    });
                }
            )
            ->orderByRaw("CASE kamar.status
                WHEN 'KOSONG' THEN 1
                WHEN 'DIBERSIHKAN' THEN 2
                WHEN 'DIBOOKING' THEN 3
                WHEN 'ISI' THEN 4
                ELSE 5
            END")
            ->orderBy('kamar.kelas')
            ->orderBy('bangsal.nm_bangsal')
            ->orderBy('kamar.kd_kamar')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{
     *     all: int,
     *     available: int,
     *     occupied: int,
     *     cleaning: int,
     *     booked: int
     * }
     */
    public function roomCounts(): array
    {
        $counts = $this->roomQuery()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE WHEN kamar.status = 'KOSONG' THEN 1 ELSE 0 END)
                    as available"
            )
            ->selectRaw(
                "SUM(CASE WHEN kamar.status = 'ISI' THEN 1 ELSE 0 END)
                    as occupied"
            )
            ->selectRaw(
                "SUM(CASE WHEN kamar.status = 'DIBERSIHKAN' THEN 1 ELSE 0 END)
                    as cleaning"
            )
            ->selectRaw(
                "SUM(CASE WHEN kamar.status = 'DIBOOKING' THEN 1 ELSE 0 END)
                    as booked"
            )
            ->first();

        return [
            'all' => (int) ($counts?->total ?? 0),
            'available' => (int) ($counts?->available ?? 0),
            'occupied' => (int) ($counts?->occupied ?? 0),
            'cleaning' => (int) ($counts?->cleaning ?? 0),
            'booked' => (int) ($counts?->booked ?? 0),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    public function roomClasses(): Collection
    {
        return $this->connection()
            ->table('kamar')
            ->where('statusdata', '1')
            ->whereNotNull('kelas')
            ->where('kelas', '<>', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->map(fn (mixed $class): string => trim((string) $class))
            ->filter()
            ->values();
    }

    private function roomQuery(): Builder
    {
        return $this->connection()
            ->table('kamar')
            ->leftJoin(
                'bangsal',
                'bangsal.kd_bangsal',
                '=',
                'kamar.kd_bangsal'
            )
            ->where('kamar.statusdata', '1');
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
