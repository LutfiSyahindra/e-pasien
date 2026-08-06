<?php

namespace App\Repositories\epasien\settings;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorDirectoryRepository
{
    private const ACTIVE_STATUS = '1';

    public function paginate(?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->activeDoctors()
            ->select('dokter.kd_dokter', 'dokter.nm_dokter', 'dokter.jk')
            ->when($search !== null, function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->where('dokter.nm_dokter', 'like', $like)
                        ->orWhere('dokter.kd_dokter', 'like', $like);
                });
            })
            ->orderBy('dokter.nm_dokter')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findActive(string $doctorCode): ?object
    {
        return $this->activeDoctors()
            ->select('dokter.kd_dokter', 'dokter.nm_dokter', 'dokter.jk')
            ->where('dokter.kd_dokter', $doctorCode)
            ->first();
    }

    /**
     * @param  array<int, string>  $doctorCodes
     */
    public function activeByCodes(array $doctorCodes): Collection
    {
        $doctorCodes = collect($doctorCodes)
            ->map(fn (mixed $code): string => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        if ($doctorCodes->isEmpty()) {
            return collect();
        }

        return $this->activeDoctors()
            ->select('dokter.kd_dokter', 'dokter.nm_dokter', 'dokter.jk')
            ->whereIn('dokter.kd_dokter', $doctorCodes)
            ->get();
    }

    private function activeDoctors(): Builder
    {
        return $this->connection()
            ->table('dokter')
            ->where('dokter.status', self::ACTIVE_STATUS)
            ->whereNotNull('dokter.nm_dokter')
            ->where('dokter.nm_dokter', '<>', '');
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
