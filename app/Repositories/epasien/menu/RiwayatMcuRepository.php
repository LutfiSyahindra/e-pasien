<?php

namespace App\Repositories\epasien\menu;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RiwayatMcuRepository
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

    public function paginateAssessments(
        string $medicalRecordNumber,
        ?string $startDate,
        ?string $endDate,
        ?string $doctorCode,
        ?string $search,
        int $perPage
    ): LengthAwarePaginator {
        return $this->assessmentQuery($medicalRecordNumber)
            ->when(
                $startDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'penilaian_mcu.tanggal',
                    '>=',
                    $startDate
                )
            )
            ->when(
                $endDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'penilaian_mcu.tanggal',
                    '<=',
                    $endDate
                )
            )
            ->when(
                $doctorCode !== null,
                fn (Builder $query) => $query->where(
                    'penilaian_mcu.kd_dokter',
                    $doctorCode
                )
            )
            ->when(
                $search !== null,
                fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                    $like = '%'.$search.'%';

                    $nested
                        ->where('penilaian_mcu.no_rawat', 'like', $like)
                        ->orWhere('dokter.nm_dokter', 'like', $like)
                        ->orWhere('poliklinik.nm_poli', 'like', $like)
                        ->orWhere('penilaian_mcu.kesimpulan', 'like', $like)
                        ->orWhere('penilaian_mcu.anjuran', 'like', $like);
                })
            )
            ->orderByDesc('penilaian_mcu.tanggal')
            ->orderByDesc('penilaian_mcu.no_rawat')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, object>
     */
    public function assessmentDoctors(string $medicalRecordNumber): Collection
    {
        return $this->connection()
            ->table('penilaian_mcu')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'penilaian_mcu.no_rawat'
            )
            ->leftJoin(
                'dokter',
                'dokter.kd_dokter',
                '=',
                'penilaian_mcu.kd_dokter'
            )
            ->select(
                'penilaian_mcu.kd_dokter',
                'dokter.nm_dokter'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->where('penilaian_mcu.kd_dokter', '<>', '')
            ->distinct()
            ->orderBy('dokter.nm_dokter')
            ->get();
    }

    /**
     * @return array{all: int, current_year: int, latest_at: ?string}
     */
    public function assessmentSummary(
        string $medicalRecordNumber,
        int $currentYear
    ): array {
        $summary = $this->connection()
            ->table('penilaian_mcu')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'penilaian_mcu.no_rawat'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN YEAR(penilaian_mcu.tanggal) = ? THEN 1 ELSE 0 END) as current_year',
                [$currentYear]
            )
            ->selectRaw('MAX(penilaian_mcu.tanggal) as latest_at')
            ->first();

        return [
            'all' => (int) ($summary?->total ?? 0),
            'current_year' => (int) ($summary?->current_year ?? 0),
            'latest_at' => $summary?->latest_at
                ? (string) $summary->latest_at
                : null,
        ];
    }

    public function findAssessmentForPatient(
        string $medicalRecordNumber,
        string $treatmentNumber
    ): ?object {
        return $this->connection()
            ->table('penilaian_mcu')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'penilaian_mcu.no_rawat'
            )
            ->leftJoin(
                'pasien',
                'pasien.no_rkm_medis',
                '=',
                'reg_periksa.no_rkm_medis'
            )
            ->leftJoin(
                'dokter',
                'dokter.kd_dokter',
                '=',
                'penilaian_mcu.kd_dokter'
            )
            ->leftJoin(
                'poliklinik',
                'poliklinik.kd_poli',
                '=',
                'reg_periksa.kd_poli'
            )
            ->select(
                'penilaian_mcu.*',
                'reg_periksa.no_rkm_medis',
                'reg_periksa.status_lanjut',
                'pasien.nm_pasien',
                'dokter.nm_dokter',
                'poliklinik.nm_poli'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->where('penilaian_mcu.no_rawat', $treatmentNumber)
            ->first();
    }

    private function assessmentQuery(string $medicalRecordNumber): Builder
    {
        return $this->connection()
            ->table('penilaian_mcu')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'penilaian_mcu.no_rawat'
            )
            ->leftJoin(
                'dokter',
                'dokter.kd_dokter',
                '=',
                'penilaian_mcu.kd_dokter'
            )
            ->leftJoin(
                'poliklinik',
                'poliklinik.kd_poli',
                '=',
                'reg_periksa.kd_poli'
            )
            ->select(
                'penilaian_mcu.no_rawat',
                'penilaian_mcu.tanggal',
                'penilaian_mcu.kd_dokter',
                'penilaian_mcu.keadaan',
                'penilaian_mcu.kesadaran',
                'penilaian_mcu.td',
                'penilaian_mcu.nadi',
                'penilaian_mcu.rr',
                'penilaian_mcu.tb',
                'penilaian_mcu.bb',
                'penilaian_mcu.suhu',
                'penilaian_mcu.kesimpulan',
                'penilaian_mcu.anjuran',
                'reg_periksa.status_lanjut',
                'dokter.nm_dokter',
                'poliklinik.nm_poli'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber);
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
