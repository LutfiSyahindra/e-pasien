<?php

namespace App\Repositories\epasien\menu;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RiwayatPemeriksaanRepository
{
    public function findPatient(string $medicalRecordNumber): ?object
    {
        return $this->connection()
            ->table('pasien')
            ->select(
                'no_rkm_medis',
                'nm_pasien',
                'jk',
                'tgl_lahir',
                'no_tlp'
            )
            ->where('no_rkm_medis', $medicalRecordNumber)
            ->first();
    }

    public function paginateCompletedExaminations(
        string $medicalRecordNumber,
        ?string $careType,
        ?string $startDate,
        ?string $endDate,
        ?string $doctorCode,
        int $perPage
    ): LengthAwarePaginator {
        return $this->completedExaminationQuery($medicalRecordNumber)
            ->when(
                $careType !== null,
                fn (Builder $query) => $query->where('reg_periksa.status_lanjut', $careType)
            )
            ->when(
                $startDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'reg_periksa.tgl_registrasi',
                    '>=',
                    $startDate
                )
            )
            ->when(
                $endDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'reg_periksa.tgl_registrasi',
                    '<=',
                    $endDate
                )
            )
            ->when(
                $doctorCode !== null,
                fn (Builder $query) => $query->where('reg_periksa.kd_dokter', $doctorCode)
            )
            ->orderByDesc('reg_periksa.tgl_registrasi')
            ->orderByDesc('reg_periksa.jam_reg')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, object>
     */
    public function completedExaminationDoctors(string $medicalRecordNumber): Collection
    {
        return $this->connection()
            ->table('reg_periksa')
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', 'reg_periksa.kd_dokter')
            ->select(
                'reg_periksa.kd_dokter',
                'dokter.nm_dokter'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->where('reg_periksa.stts', 'Sudah')
            ->whereNotNull('reg_periksa.kd_dokter')
            ->where('reg_periksa.kd_dokter', '<>', '')
            ->distinct()
            ->orderBy('dokter.nm_dokter')
            ->get();
    }

    /**
     * @return array{all: int, Ralan: int, Ranap: int}
     */
    public function completedExaminationCounts(string $medicalRecordNumber): array
    {
        $counts = $this->connection()
            ->table('reg_periksa')
            ->where('no_rkm_medis', $medicalRecordNumber)
            ->where('stts', 'Sudah')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status_lanjut = 'Ralan' THEN 1 ELSE 0 END) as ralan")
            ->selectRaw("SUM(CASE WHEN status_lanjut = 'Ranap' THEN 1 ELSE 0 END) as ranap")
            ->first();

        return [
            'all' => (int) ($counts?->total ?? 0),
            'Ralan' => (int) ($counts?->ralan ?? 0),
            'Ranap' => (int) ($counts?->ranap ?? 0),
        ];
    }

    public function findResumeForCompletedVisit(
        string $medicalRecordNumber,
        string $noRawat,
        string $careType
    ): ?object {
        $resumeTable = match ($careType) {
            'Ralan' => 'resume_pasien',
            'Ranap' => 'resume_pasien_ranap',
            default => throw new \InvalidArgumentException('Jenis layanan resume tidak valid.'),
        };

        return $this->connection()
            ->table('reg_periksa')
            ->join(
                $resumeTable,
                $resumeTable.'.no_rawat',
                '=',
                'reg_periksa.no_rawat'
            )
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', $resumeTable.'.kd_dokter')
            ->select(
                $resumeTable.'.*',
                'dokter.nm_dokter'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->where('reg_periksa.no_rawat', $noRawat)
            ->where('reg_periksa.status_lanjut', $careType)
            ->where('reg_periksa.stts', 'Sudah')
            ->first();
    }

    /**
     * @return array{visit: object, rows: Collection<int, object>}|null
     */
    public function findBillingForCompletedVisit(
        string $medicalRecordNumber,
        string $noRawat
    ): ?array {
        $connection = $this->connection();
        $visit = $connection
            ->table('reg_periksa')
            ->leftJoin('pasien', 'pasien.no_rkm_medis', '=', 'reg_periksa.no_rkm_medis')
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', 'reg_periksa.kd_dokter')
            ->leftJoin('poliklinik', 'poliklinik.kd_poli', '=', 'reg_periksa.kd_poli')
            ->leftJoin('penjab', 'penjab.kd_pj', '=', 'reg_periksa.kd_pj')
            ->select(
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.status_lanjut',
                'reg_periksa.status_bayar',
                'pasien.nm_pasien',
                'dokter.nm_dokter',
                'poliklinik.nm_poli',
                'penjab.png_jawab'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->where('reg_periksa.no_rawat', $noRawat)
            ->where('reg_periksa.stts', 'Sudah')
            ->first();

        if ($visit === null) {
            return null;
        }

        $rows = $connection
            ->table('billing')
            ->select('billing.*')
            ->where('billing.no_rawat', $noRawat)
            ->orderBy('billing.noindex')
            ->get();

        return [
            'visit' => $visit,
            'rows' => $rows,
        ];
    }

    private function completedExaminationQuery(string $medicalRecordNumber): Builder
    {
        return $this->connection()
            ->table('reg_periksa')
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', 'reg_periksa.kd_dokter')
            ->leftJoin('poliklinik', 'poliklinik.kd_poli', '=', 'reg_periksa.kd_poli')
            ->leftJoin('penjab', 'penjab.kd_pj', '=', 'reg_periksa.kd_pj')
            ->select(
                'reg_periksa.no_reg',
                'reg_periksa.no_rawat',
                'reg_periksa.no_rkm_medis',
                'reg_periksa.tgl_registrasi',
                'reg_periksa.jam_reg',
                'reg_periksa.status_lanjut',
                'reg_periksa.status_bayar',
                'reg_periksa.stts',
                'reg_periksa.stts_daftar',
                'reg_periksa.kd_dokter',
                'reg_periksa.kd_poli',
                'reg_periksa.kd_pj',
                'dokter.nm_dokter',
                'poliklinik.nm_poli',
                'penjab.png_jawab'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->where('reg_periksa.stts', 'Sudah');
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
