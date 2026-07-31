<?php

namespace App\Repositories\epasien\menu\Surat;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SuratKontrolRepository
{
    public function findPatient(string $medicalRecordNumber): ?object
    {
        return $this->connection()
            ->table('pasien')
            ->select(
                'no_rkm_medis',
                'nm_pasien',
                'tgl_lahir',
                'no_tlp',
                'no_peserta'
            )
            ->where('no_rkm_medis', $medicalRecordNumber)
            ->first();
    }

    public function paginateGeneralControlLetters(
        string $medicalRecordNumber,
        ?string $status,
        int $perPage
    ): LengthAwarePaginator {
        return $this->generalControlLetterQuery($medicalRecordNumber)
            ->when(
                $status !== null,
                fn (Builder $query) => $query->where('skdp_bpjs.status', $status)
            )
            ->orderByRaw('skdp_bpjs.tanggal_datang IS NULL')
            ->orderByDesc('skdp_bpjs.tanggal_datang')
            ->orderByDesc('skdp_bpjs.tanggal_rujukan')
            ->orderByDesc('skdp_bpjs.tahun')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{all: int, waiting: int, examined: int, cancelled: int}
     */
    public function generalControlLetterCounts(string $medicalRecordNumber): array
    {
        $counts = $this->connection()
            ->table('skdp_bpjs')
            ->where('no_rkm_medis', $medicalRecordNumber)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as waiting")
            ->selectRaw("SUM(CASE WHEN status = 'Sudah Periksa' THEN 1 ELSE 0 END) as examined")
            ->selectRaw("SUM(CASE WHEN status = 'Batal Periksa' THEN 1 ELSE 0 END) as cancelled")
            ->first();

        return [
            'all' => (int) ($counts?->total ?? 0),
            'waiting' => (int) ($counts?->waiting ?? 0),
            'examined' => (int) ($counts?->examined ?? 0),
            'cancelled' => (int) ($counts?->cancelled ?? 0),
        ];
    }

    private function generalControlLetterQuery(string $medicalRecordNumber): Builder
    {
        return $this->connection()
            ->table('skdp_bpjs')
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', 'skdp_bpjs.kd_dokter')
            ->select(
                'skdp_bpjs.tahun',
                'skdp_bpjs.no_rkm_medis',
                'skdp_bpjs.diagnosa',
                'skdp_bpjs.terapi',
                'skdp_bpjs.alasan1',
                'skdp_bpjs.alasan2',
                'skdp_bpjs.rtl1',
                'skdp_bpjs.rtl2',
                'skdp_bpjs.tanggal_datang',
                'skdp_bpjs.tanggal_rujukan',
                'skdp_bpjs.kd_dokter',
                'skdp_bpjs.status',
                'dokter.nm_dokter'
            )
            ->where('skdp_bpjs.no_rkm_medis', $medicalRecordNumber);
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
