<?php

namespace App\Repositories\epasien\menu\Surat;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SuratRujukanRepository
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

    public function paginateGeneralOutgoingReferrals(
        string $medicalRecordNumber,
        ?string $startDate,
        ?string $endDate,
        int $perPage
    ): LengthAwarePaginator {
        return $this->generalOutgoingQuery($medicalRecordNumber)
            ->when(
                $startDate !== null,
                fn (Builder $query) => $query->whereDate('rujuk.tgl_rujuk', '>=', $startDate)
            )
            ->when(
                $endDate !== null,
                fn (Builder $query) => $query->whereDate('rujuk.tgl_rujuk', '<=', $endDate)
            )
            ->orderByDesc('rujuk.tgl_rujuk')
            ->orderByDesc('rujuk.jam')
            ->paginate($perPage, ['*'], 'umum_page')
            ->withQueryString();
    }

    private function generalOutgoingQuery(string $medicalRecordNumber): Builder
    {
        return $this->connection()
            ->table('rujuk')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'rujuk.no_rawat'
            )
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', 'rujuk.kd_dokter')
            ->leftJoin('penjab', 'penjab.kd_pj', '=', 'reg_periksa.kd_pj')
            ->select(
                'rujuk.no_rujuk',
                'rujuk.no_rawat',
                'rujuk.rujuk_ke',
                'rujuk.tgl_rujuk',
                'rujuk.jam',
                'rujuk.keterangan_diagnosa',
                'rujuk.kd_dokter',
                'rujuk.kat_rujuk',
                'rujuk.ambulance',
                'rujuk.keterangan',
                'rujuk.terapi',
                'rujuk.indikasi',
                'reg_periksa.kd_pj',
                'penjab.png_jawab',
                'dokter.nm_dokter'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('reg_periksa.kd_pj')
                    ->orWhereRaw(
                        'UPPER(TRIM(reg_periksa.kd_pj)) <> ?',
                        ['BPJ']
                    );
            });
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
