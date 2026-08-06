<?php

namespace App\Repositories\epasien\menu;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JadwalDokterRepository
{
    private const ACTIVE_STATUS = '1';

    public function paginateSchedules(
        ?string $search,
        ?string $day,
        ?string $clinicCode,
        int $perPage
    ): LengthAwarePaginator {
        return $this->scheduleListQuery()
            ->when(
                $search !== null,
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $like = '%'.$search.'%';

                        $query
                            ->where('dokter.nm_dokter', 'like', $like)
                            ->orWhere('poliklinik.nm_poli', 'like', $like);
                    });
                }
            )
            ->when(
                $day !== null,
                fn (Builder $query) => $query->where(
                    'jadwal_spesialis.hari_kerja',
                    $day
                )
            )
            ->when(
                $clinicCode !== null,
                fn (Builder $query) => $query->where(
                    'jadwal_spesialis.kd_poli',
                    $clinicCode
                )
            )
            ->orderByRaw(
                'FIELD(jadwal_spesialis.hari_kerja, '
                ."'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'AKHAD')"
            )
            ->orderBy('jadwal_spesialis.jam_mulai')
            ->orderBy('dokter.nm_dokter')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function schedulesForDay(string $day, int $limit): Collection
    {
        return $this->scheduleListQuery()
            ->where('jadwal_spesialis.hari_kerja', $day)
            ->orderBy('jadwal_spesialis.jam_mulai')
            ->orderBy('dokter.nm_dokter')
            ->limit($limit)
            ->get();
    }

    public function clinics(): Collection
    {
        return $this->activeScheduleQuery()
            ->select(
                'jadwal_spesialis.kd_poli',
                'poliklinik.nm_poli'
            )
            ->distinct()
            ->orderBy('poliklinik.nm_poli')
            ->get();
    }

    /**
     * @return object{
     *     schedules: int|string|null,
     *     doctors: int|string|null,
     *     clinics: int|string|null
     * }|null
     */
    public function summary(): ?object
    {
        return $this->activeScheduleQuery()
            ->selectRaw('COUNT(*) as schedules')
            ->selectRaw(
                'COUNT(DISTINCT jadwal_spesialis.kd_dokter) as doctors'
            )
            ->selectRaw(
                'COUNT(DISTINCT jadwal_spesialis.kd_poli) as clinics'
            )
            ->first();
    }

    private function activeScheduleQuery(): Builder
    {
        return $this->connection()
            ->table('jadwal_spesialis')
            ->join(
                'dokter',
                'dokter.kd_dokter',
                '=',
                'jadwal_spesialis.kd_dokter'
            )
            ->join(
                'poliklinik',
                'poliklinik.kd_poli',
                '=',
                'jadwal_spesialis.kd_poli'
            )
            ->where('dokter.status', self::ACTIVE_STATUS)
            ->where('poliklinik.status', self::ACTIVE_STATUS)
            ->whereNotNull('dokter.nm_dokter')
            ->whereNotNull('poliklinik.nm_poli')
            ->where('dokter.nm_dokter', '<>', '')
            ->where('poliklinik.nm_poli', '<>', '');
    }

    private function scheduleListQuery(): Builder
    {
        return $this->activeScheduleQuery()->select(
            'jadwal_spesialis.kd_dokter',
            'dokter.nm_dokter',
            'dokter.jk',
            'jadwal_spesialis.kd_poli',
            'poliklinik.nm_poli',
            'jadwal_spesialis.hari_kerja',
            'jadwal_spesialis.jam_mulai',
            'jadwal_spesialis.jam_selesai',
            'jadwal_spesialis.kuota'
        );
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
