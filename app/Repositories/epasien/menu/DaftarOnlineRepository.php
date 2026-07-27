<?php

namespace App\Repositories\epasien\menu;

use App\Exceptions\RegistrationLockException;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DaftarOnlineRepository
{
    public function findPatient(string $medicalRecordNumber): ?object
    {
        return $this->connection()
            ->table('pasien')
            ->select(
                'no_rkm_medis',
                'nm_pasien',
                'tgl_lahir',
                'alamat',
                'keluarga',
                'namakeluarga',
                'no_tlp',
                'kd_pj',
                'no_peserta'
            )
            ->where('no_rkm_medis', $medicalRecordNumber)
            ->first();
    }

    public function getPenjaminOptions(bool $includeBpjs = false): Collection
    {
        return $this->connection()
            ->table('penjab')
            ->select('kd_pj', 'png_jawab')
            ->when(
                ! $includeBpjs,
                fn (Builder $query) => $query->whereRaw('UPPER(kd_pj) <> ?', ['BPJ'])
            )
            ->orderBy('png_jawab')
            ->get();
    }

    public function getSchedules(array $workdayAliases): Collection
    {
        return $this->connection()
            ->table('jadwal')
            ->join('dokter', 'dokter.kd_dokter', '=', 'jadwal.kd_dokter')
            ->join('poliklinik', 'poliklinik.kd_poli', '=', 'jadwal.kd_poli')
            ->select(
                'jadwal.kd_dokter',
                'dokter.nm_dokter',
                'jadwal.kd_poli',
                'poliklinik.nm_poli',
                'jadwal.hari_kerja',
                'jadwal.jam_mulai',
                'jadwal.jam_selesai',
                'jadwal.kuota'
            )
            ->whereIn(DB::raw('UPPER(jadwal.hari_kerja)'), $workdayAliases)
            ->orderBy('poliklinik.nm_poli')
            ->orderBy('dokter.nm_dokter')
            ->orderBy('jadwal.jam_mulai')
            ->get();
    }

    public function countActiveRegistrations(
        string $date,
        string $doctorCode,
        string $clinicCode
    ): int {
        return $this->connection()
            ->table('reg_periksa')
            ->where('tgl_registrasi', $date)
            ->where('kd_dokter', $doctorCode)
            ->where('kd_poli', $clinicCode)
            ->where('stts', '<>', 'Batal')
            ->count();
    }

    public function previewNextRegistrationNumber(
        string $date,
        string $doctorCode,
        string $clinicCode
    ): string {
        $lastRegistrationNumber = $this->connection()
            ->table('reg_periksa')
            ->where('tgl_registrasi', $date)
            ->where('kd_dokter', $doctorCode)
            ->where('kd_poli', $clinicCode)
            ->orderByRaw('CAST(no_reg AS UNSIGNED) DESC')
            ->value('no_reg');

        return $this->nextSequence((string) $lastRegistrationNumber, 3);
    }

    public function findPendingRegistration(string $medicalRecordNumber): ?object
    {
        return $this->registrationQuery($medicalRecordNumber)
            ->where('reg_periksa.stts', 'Belum')
            ->orderByDesc('reg_periksa.tgl_registrasi')
            ->orderByDesc('reg_periksa.jam_reg')
            ->first();
    }

    public function paginateRegistrationHistory(
        ?string $medicalRecordNumber,
        string $searchQuery,
        int $perPage,
        string $guarantorCode = ''
    ): LengthAwarePaginator {
        $query = $this->registrationQuery($medicalRecordNumber);

        if ($guarantorCode !== '') {
            $query->where('reg_periksa.kd_pj', $guarantorCode);
        }

        if ($searchQuery !== '') {
            $likeSearch = '%'.$searchQuery.'%';

            $query->where(function (Builder $query) use ($likeSearch): void {
                $query
                    ->where('reg_periksa.no_reg', 'like', $likeSearch)
                    ->orWhere('reg_periksa.no_rawat', 'like', $likeSearch)
                    ->orWhere('reg_periksa.stts', 'like', $likeSearch)
                    ->orWhere('reg_periksa.status_bayar', 'like', $likeSearch)
                    ->orWhere('dokter.nm_dokter', 'like', $likeSearch)
                    ->orWhere('poliklinik.nm_poli', 'like', $likeSearch)
                    ->orWhere('penjab.png_jawab', 'like', $likeSearch)
                    ->orWhere('reg_periksa.no_rkm_medis', 'like', $likeSearch)
                    ->orWhere('pasien.nm_pasien', 'like', $likeSearch);
            });
        }

        return $query
            ->orderByDesc('reg_periksa.tgl_registrasi')
            ->orderByDesc('reg_periksa.jam_reg')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findSchedule(
        string $doctorCode,
        string $clinicCode,
        array $workdayAliases
    ): ?object {
        return $this->connection()
            ->table('jadwal')
            ->join('dokter', 'dokter.kd_dokter', '=', 'jadwal.kd_dokter')
            ->join('poliklinik', 'poliklinik.kd_poli', '=', 'jadwal.kd_poli')
            ->select(
                'jadwal.kd_dokter',
                'dokter.nm_dokter',
                'jadwal.kd_poli',
                'poliklinik.nm_poli',
                'jadwal.hari_kerja',
                'jadwal.jam_mulai',
                'jadwal.jam_selesai',
                'jadwal.kuota'
            )
            ->where('jadwal.kd_dokter', $doctorCode)
            ->where('jadwal.kd_poli', $clinicCode)
            ->whereIn(DB::raw('UPPER(jadwal.hari_kerja)'), $workdayAliases)
            ->orderBy('jadwal.jam_mulai')
            ->first();
    }

    public function findEligiblePenjamin(string $guarantorCode, bool $includeBpjs = false): ?object
    {
        return $this->connection()
            ->table('penjab')
            ->select('kd_pj', 'png_jawab')
            ->where('kd_pj', $guarantorCode)
            ->when(
                ! $includeBpjs,
                fn (Builder $query) => $query->whereRaw('UPPER(kd_pj) <> ?', ['BPJ'])
            )
            ->first();
    }

    public function createRegistration(
        array $registration,
        ?string $patientCardNumber = null
    ): ?array {
        $connection = $this->connection();
        $registrationDate = (string) $registration['tgl_registrasi'];
        $doctorCode = (string) $registration['kd_dokter'];
        $clinicCode = (string) $registration['kd_poli'];
        $lockName = 'epasien_reg_'.$registrationDate;

        $this->acquireLock($connection, $lockName);

        try {
            return $connection->transaction(function () use (
                $connection,
                $registration,
                $registrationDate,
                $doctorCode,
                $clinicCode,
                $patientCardNumber
            ): ?array {
                $existingRegistration = $connection
                    ->table('reg_periksa')
                    ->where('tgl_registrasi', $registrationDate)
                    ->where('no_rkm_medis', $registration['no_rkm_medis'])
                    ->where('kd_dokter', $doctorCode)
                    ->where('kd_poli', $clinicCode)
                    ->where('stts', '<>', 'Batal')
                    ->first();

                if ($existingRegistration) {
                    return null;
                }

                if ($patientCardNumber !== null) {
                    $connection
                        ->table('pasien')
                        ->where('no_rkm_medis', $registration['no_rkm_medis'])
                        ->update(['no_peserta' => $patientCardNumber]);
                }

                $row = array_merge($registration, [
                    'no_reg' => $this->nextRegistrationNumber(
                        $connection,
                        $registrationDate,
                        $doctorCode,
                        $clinicCode
                    ),
                    'no_rawat' => $this->nextTreatmentNumber($connection, $registrationDate),
                ]);

                $connection->table('reg_periksa')->insert($row);

                return $row;
            });
        } finally {
            $this->releaseLock($connection, $lockName);
        }
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }

    private function registrationQuery(?string $medicalRecordNumber): Builder
    {
        return $this->connection()
            ->table('reg_periksa')
            ->leftJoin('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', 'reg_periksa.kd_dokter')
            ->leftJoin('poliklinik', 'poliklinik.kd_poli', '=', 'reg_periksa.kd_poli')
            ->leftJoin('penjab', 'penjab.kd_pj', '=', 'reg_periksa.kd_pj')
            ->select(
                'reg_periksa.no_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.kd_dokter',
                'reg_periksa.kd_poli',
                'reg_periksa.kd_pj',
                'reg_periksa.stts',
                'reg_periksa.status_bayar',
                'reg_periksa.status_lanjut',
                'reg_periksa.stts_daftar',
                'reg_periksa.status_poli',
                'reg_periksa.biaya_reg',
                'reg_periksa.umurdaftar',
                'reg_periksa.sttsumur',
                'reg_periksa.p_jawab',
                'reg_periksa.almt_pj',
                'reg_periksa.hubunganpj',
                'pasien.nm_pasien',
                'pasien.no_tlp',
                'dokter.nm_dokter',
                'poliklinik.nm_poli',
                'penjab.png_jawab'
            )
            ->when(
                $medicalRecordNumber !== null,
                fn (Builder $query) => $query->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            );
    }

    private function nextRegistrationNumber(
        Connection $connection,
        string $date,
        string $doctorCode,
        string $clinicCode
    ): string {
        $lastRegistrationNumber = $connection
            ->table('reg_periksa')
            ->where('tgl_registrasi', $date)
            ->where('kd_dokter', $doctorCode)
            ->where('kd_poli', $clinicCode)
            ->lockForUpdate()
            ->orderByRaw('CAST(no_reg AS UNSIGNED) DESC')
            ->value('no_reg');

        return $this->nextSequence((string) $lastRegistrationNumber, 3);
    }

    private function nextTreatmentNumber(Connection $connection, string $date): string
    {
        $prefix = Carbon::parse($date)->format('Y/m/d').'/';
        $lastTreatmentNumber = $connection
            ->table('reg_periksa')
            ->where('tgl_registrasi', $date)
            ->lockForUpdate()
            ->orderByRaw("CAST(SUBSTRING_INDEX(no_rawat, '/', -1) AS UNSIGNED) DESC")
            ->value('no_rawat');

        $nextNumber = $this->lastSequenceNumber((string) $lastTreatmentNumber) + 1;

        return $prefix.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function nextSequence(string $lastValue, int $minimumWidth): string
    {
        $lastNumber = $this->lastSequenceNumber($lastValue);
        $digitWidth = $this->digitWidth($lastValue, $minimumWidth);

        return str_pad((string) ($lastNumber + 1), $digitWidth, '0', STR_PAD_LEFT);
    }

    private function lastSequenceNumber(string $value): int
    {
        if (preg_match('/(\d+)$/', $value, $matches) === 1) {
            return (int) $matches[1];
        }

        return (int) $value;
    }

    private function digitWidth(string $value, int $minimumWidth): int
    {
        if (preg_match('/(\d+)$/', $value, $matches) === 1) {
            return max($minimumWidth, strlen($matches[1]));
        }

        return $minimumWidth;
    }

    private function acquireLock(Connection $connection, string $lockName): void
    {
        $result = $connection->selectOne('SELECT GET_LOCK(?, 10) AS locked', [$lockName]);

        if ((int) ($result->locked ?? 0) !== 1) {
            throw new RegistrationLockException('Nomor registrasi sedang diproses.');
        }
    }

    private function releaseLock(Connection $connection, string $lockName): void
    {
        $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
    }
}
