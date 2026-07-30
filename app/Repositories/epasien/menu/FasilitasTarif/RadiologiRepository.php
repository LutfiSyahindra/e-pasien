<?php

namespace App\Repositories\epasien\menu\FasilitasTarif;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RadiologiRepository
{
    private const ACTIVE_STATUS = '1';

    private const PAYOR_CODE = 'A09';

    public function paginateRates(
        ?string $class,
        ?string $search,
        int $perPage
    ): LengthAwarePaginator {
        return $this->activeRateQuery()
            ->select(
                'kd_jenis_prw',
                'nm_perawatan',
                'kelas',
                'total_byr'
            )
            ->when(
                $class !== null,
                fn (Builder $query) => $query->where('kelas', $class)
            )
            ->when(
                $search !== null,
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('nm_perawatan', 'like', '%'.$search.'%')
                            ->orWhere('kd_jenis_prw', 'like', '%'.$search.'%');
                    });
                }
            )
            ->orderByRaw("CASE kelas
                WHEN '-' THEN 1
                WHEN 'Rawat Jalan' THEN 2
                WHEN 'Kelas 1' THEN 3
                WHEN 'Kelas 2' THEN 4
                WHEN 'Kelas 3' THEN 5
                WHEN 'Kelas Utama' THEN 6
                WHEN 'Kelas VIP' THEN 7
                WHEN 'Kelas VVIP' THEN 8
                ELSE 9
            END")
            ->orderBy('nm_perawatan')
            ->orderBy('kd_jenis_prw')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, string>
     */
    public function classes(): Collection
    {
        return $this->activeRateQuery()
            ->whereNotNull('kelas')
            ->where('kelas', '<>', '')
            ->distinct()
            ->orderByRaw("CASE kelas
                WHEN '-' THEN 1
                WHEN 'Rawat Jalan' THEN 2
                WHEN 'Kelas 1' THEN 3
                WHEN 'Kelas 2' THEN 4
                WHEN 'Kelas 3' THEN 5
                WHEN 'Kelas Utama' THEN 6
                WHEN 'Kelas VIP' THEN 7
                WHEN 'Kelas VVIP' THEN 8
                ELSE 9
            END")
            ->pluck('kelas');
    }

    /**
     * @return object{
     *     total: int|string|null,
     *     minimum: int|float|string|null,
     *     maximum: int|float|string|null
     * }|null
     */
    public function summary(): ?object
    {
        return $this->activeRateQuery()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'MIN(CASE WHEN total_byr > 0 THEN total_byr END) as minimum'
            )
            ->selectRaw('MAX(total_byr) as maximum')
            ->first();
    }

    private function activeRateQuery(): Builder
    {
        return $this->connection()
            ->table('jns_perawatan_radiologi')
            ->where('status', self::ACTIVE_STATUS)
            ->where('kd_pj', self::PAYOR_CODE);
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
