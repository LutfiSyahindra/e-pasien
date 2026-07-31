<?php

namespace App\Repositories\epasien\settings;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DoctorScheduleRepository
{
    private const ACTIVE_STATUS = '1';

    public function paginate(
        ?string $search,
        ?string $day,
        ?string $clinicCode,
        int $perPage
    ): LengthAwarePaginator {
        return $this->activeScheduleQuery()
            ->select(
                'jadwal_spesialis.kd_dokter',
                'dokter.nm_dokter',
                'jadwal_spesialis.kd_poli',
                'poliklinik.nm_poli',
                'jadwal_spesialis.hari_kerja',
                'jadwal_spesialis.jam_mulai',
                'jadwal_spesialis.jam_selesai',
                'jadwal_spesialis.kuota'
            )
            ->when($search !== null, function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->where('dokter.nm_dokter', 'like', $like)
                        ->orWhere('dokter.kd_dokter', 'like', $like)
                        ->orWhere('poliklinik.nm_poli', 'like', $like);
                });
            })
            ->when(
                $day !== null,
                fn (Builder $query) => $query->where('jadwal_spesialis.hari_kerja', $day)
            )
            ->when(
                $clinicCode !== null,
                fn (Builder $query) => $query->where('jadwal_spesialis.kd_poli', $clinicCode)
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

    public function clinics(): Collection
    {
        return $this->activeScheduleQuery()
            ->select('jadwal_spesialis.kd_poli', 'poliklinik.nm_poli')
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
            ->selectRaw('COUNT(DISTINCT jadwal_spesialis.kd_dokter) as doctors')
            ->selectRaw('COUNT(DISTINCT jadwal_spesialis.kd_poli) as clinics')
            ->first();
    }

    /**
     * @param  array{doctor_code: string, day: string, start_time: string}  $original
     * @param  array{day: string, start_time: string, end_time: string, quota: int}  $changes
     */
    public function update(array $original, array $changes): void
    {
        $this->connection()->transaction(function () use ($original, $changes): void {
            $scheduleQuery = $this->connection()
                ->table('jadwal_spesialis')
                ->where('kd_dokter', $original['doctor_code'])
                ->where('hari_kerja', $original['day'])
                ->where('jam_mulai', $original['start_time']);

            $schedule = (clone $scheduleQuery)->lockForUpdate()->first();

            if ($schedule === null) {
                throw ValidationException::withMessages([
                    'schedule' => 'Jadwal tidak ditemukan atau baru saja diubah oleh petugas lain.',
                ]);
            }

            $primaryKeyChanged = $original['day'] !== $changes['day']
                || substr($original['start_time'], 0, 5) !== substr($changes['start_time'], 0, 5);

            if (
                $primaryKeyChanged
                && $this->connection()
                    ->table('jadwal_spesialis')
                    ->where('kd_dokter', $original['doctor_code'])
                    ->where('hari_kerja', $changes['day'])
                    ->where('jam_mulai', $changes['start_time'])
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'schedule' => 'Dokter sudah memiliki jadwal pada hari dan jam mulai tersebut.',
                ]);
            }

            $scheduleQuery->update([
                'hari_kerja' => $changes['day'],
                'jam_mulai' => $changes['start_time'],
                'jam_selesai' => $changes['end_time'],
                'kuota' => $changes['quota'],
            ]);
        });
    }

    private function activeScheduleQuery(): Builder
    {
        return $this->connection()
            ->table('jadwal_spesialis')
            ->join('dokter', 'dokter.kd_dokter', '=', 'jadwal_spesialis.kd_dokter')
            ->join('poliklinik', 'poliklinik.kd_poli', '=', 'jadwal_spesialis.kd_poli')
            ->where('dokter.status', self::ACTIVE_STATUS)
            ->where('poliklinik.status', self::ACTIVE_STATUS)
            ->whereNotNull('dokter.nm_dokter')
            ->whereNotNull('poliklinik.nm_poli')
            ->where('dokter.nm_dokter', '<>', '')
            ->where('poliklinik.nm_poli', '<>', '');
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
