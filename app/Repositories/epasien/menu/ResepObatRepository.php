<?php

namespace App\Repositories\epasien\menu;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResepObatRepository
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

    public function paginatePrescriptions(
        string $medicalRecordNumber,
        ?string $prescriptionType,
        ?string $careType,
        ?string $startDate,
        ?string $endDate,
        ?string $search,
        int $perPage
    ): LengthAwarePaginator {
        $connection = $this->connection();

        return $connection
            ->query()
            ->fromSub(
                $this->prescriptionUnionQuery($connection, $medicalRecordNumber),
                'prescriptions'
            )
            ->select('prescriptions.*')
            ->when(
                $prescriptionType !== null,
                fn (Builder $query) => $query->where(
                    'prescriptions.sumber',
                    $prescriptionType
                )
            )
            ->when(
                $careType !== null,
                fn (Builder $query) => $query->where(
                    'prescriptions.status_layanan',
                    $careType
                )
            )
            ->when(
                $startDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'prescriptions.tanggal',
                    '>=',
                    $startDate
                )
            )
            ->when(
                $endDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'prescriptions.tanggal',
                    '<=',
                    $endDate
                )
            )
            ->when(
                $search !== null,
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where(
                                'prescriptions.nomor_resep',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'prescriptions.no_rawat',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'prescriptions.nm_dokter',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'prescriptions.nm_poli',
                                'like',
                                '%'.$search.'%'
                            );
                    });
                }
            )
            ->orderByDesc('prescriptions.tanggal')
            ->orderByDesc('prescriptions.jam')
            ->orderByDesc('prescriptions.nomor_resep')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{all: int, ralan: int, ranap: int, dokter: int, pulang: int}
     */
    public function prescriptionCounts(string $medicalRecordNumber): array
    {
        $connection = $this->connection();
        $counts = $connection
            ->query()
            ->fromSub(
                $this->prescriptionUnionQuery($connection, $medicalRecordNumber),
                'prescriptions'
            )
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE
                    WHEN prescriptions.status_layanan = 'ralan'
                    THEN 1 ELSE 0 END) as ralan"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN prescriptions.status_layanan = 'ranap'
                    THEN 1 ELSE 0 END) as ranap"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN prescriptions.sumber = 'dokter'
                    THEN 1 ELSE 0 END) as dokter"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN prescriptions.sumber = 'pulang'
                    THEN 1 ELSE 0 END) as pulang"
            )
            ->first();

        return [
            'all' => (int) ($counts?->total ?? 0),
            'ralan' => (int) ($counts?->ralan ?? 0),
            'ranap' => (int) ($counts?->ranap ?? 0),
            'dokter' => (int) ($counts?->dokter ?? 0),
            'pulang' => (int) ($counts?->pulang ?? 0),
        ];
    }

    /**
     * @param  array<int, string>  $prescriptionNumbers
     * @return Collection<int, object>
     */
    public function medicineItems(array $prescriptionNumbers): Collection
    {
        if ($prescriptionNumbers === []) {
            return collect();
        }

        return $this->connection()
            ->table('resep_dokter')
            ->leftJoin(
                'databarang',
                'databarang.kode_brng',
                '=',
                'resep_dokter.kode_brng'
            )
            ->select(
                'resep_dokter.no_resep',
                'resep_dokter.kode_brng',
                'resep_dokter.jml',
                'resep_dokter.aturan_pakai',
                'databarang.nama_brng',
                'databarang.kode_sat'
            )
            ->whereIn('resep_dokter.no_resep', $prescriptionNumbers)
            ->orderBy('databarang.nama_brng')
            ->orderBy('resep_dokter.kode_brng')
            ->get();
    }

    /**
     * @param  array<int, string>  $prescriptionNumbers
     * @return Collection<int, object>
     */
    public function compoundedItems(array $prescriptionNumbers): Collection
    {
        if ($prescriptionNumbers === []) {
            return collect();
        }

        return $this->connection()
            ->table('resep_dokter_racikan as racikan')
            ->leftJoin(
                'metode_racik',
                'metode_racik.kd_racik',
                '=',
                'racikan.kd_racik'
            )
            ->select(
                'racikan.no_resep',
                'racikan.no_racik',
                'racikan.nama_racik',
                'racikan.kd_racik',
                'racikan.jml_dr',
                'racikan.aturan_pakai',
                'racikan.keterangan',
                'metode_racik.nm_racik'
            )
            ->whereIn('racikan.no_resep', $prescriptionNumbers)
            ->orderBy('racikan.no_resep')
            ->orderBy('racikan.no_racik')
            ->get();
    }

    /**
     * @param  array<int, string>  $requestNumbers
     * @return Collection<int, object>
     */
    public function dischargeItems(array $requestNumbers): Collection
    {
        if ($requestNumbers === []) {
            return collect();
        }

        return $this->connection()
            ->table('detail_permintaan_resep_pulang as detail')
            ->leftJoin(
                'databarang',
                'databarang.kode_brng',
                '=',
                'detail.kode_brng'
            )
            ->select(
                'detail.no_permintaan',
                'detail.kode_brng',
                'detail.jml',
                'detail.dosis',
                'databarang.nama_brng',
                'databarang.kode_sat'
            )
            ->whereIn('detail.no_permintaan', $requestNumbers)
            ->orderBy('databarang.nama_brng')
            ->orderBy('detail.kode_brng')
            ->get();
    }

    private function prescriptionUnionQuery(
        Connection $connection,
        string $medicalRecordNumber
    ): Builder {
        return $this
            ->doctorPrescriptionQuery($connection, $medicalRecordNumber)
            ->unionAll(
                $this->dischargePrescriptionQuery(
                    $connection,
                    $medicalRecordNumber
                )
            );
    }

    private function doctorPrescriptionQuery(
        Connection $connection,
        string $medicalRecordNumber
    ): Builder {
        return $connection
            ->table('resep_obat')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'resep_obat.no_rawat'
            )
            ->leftJoin(
                'dokter',
                'dokter.kd_dokter',
                '=',
                'resep_obat.kd_dokter'
            )
            ->leftJoin(
                'poliklinik',
                'poliklinik.kd_poli',
                '=',
                'reg_periksa.kd_poli'
            )
            ->select(
                'resep_obat.no_resep as nomor_resep',
                'resep_obat.no_rawat'
            )
            ->selectRaw("'dokter' as sumber")
            ->selectRaw(
                "CASE
                    WHEN resep_obat.tgl_peresepan IS NOT NULL
                        AND resep_obat.tgl_peresepan <> '0000-00-00'
                    THEN resep_obat.tgl_peresepan
                    ELSE resep_obat.tgl_perawatan
                END as tanggal"
            )
            ->selectRaw(
                "CASE
                    WHEN resep_obat.tgl_peresepan IS NOT NULL
                        AND resep_obat.tgl_peresepan <> '0000-00-00'
                    THEN resep_obat.jam_peresepan
                    ELSE resep_obat.jam
                END as jam"
            )
            ->selectRaw('LOWER(resep_obat.status) as status_layanan')
            ->selectRaw(
                "CASE
                    WHEN resep_obat.tgl_penyerahan IS NOT NULL
                        AND resep_obat.tgl_penyerahan <> '0000-00-00'
                    THEN 'selesai'
                    ELSE 'proses'
                END as status_proses"
            )
            ->addSelect(
                'resep_obat.tgl_penyerahan as tanggal_selesai',
                'resep_obat.jam_penyerahan as jam_selesai',
                'dokter.nm_dokter',
                'poliklinik.nm_poli'
            )
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('resep_dokter')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'resep_dokter.no_resep',
                        'resep_obat.no_resep'
                    );
            }, 'jumlah_obat')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('resep_dokter_racikan')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'resep_dokter_racikan.no_resep',
                        'resep_obat.no_resep'
                    );
            }, 'jumlah_racikan')
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->where('resep_obat.status', 'ralan');
    }

    private function dischargePrescriptionQuery(
        Connection $connection,
        string $medicalRecordNumber
    ): Builder {
        return $connection
            ->table('permintaan_resep_pulang as permintaan')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'permintaan.no_rawat'
            )
            ->leftJoin(
                'dokter',
                'dokter.kd_dokter',
                '=',
                'permintaan.kd_dokter'
            )
            ->leftJoin(
                'poliklinik',
                'poliklinik.kd_poli',
                '=',
                'reg_periksa.kd_poli'
            )
            ->select(
                'permintaan.no_permintaan as nomor_resep',
                'permintaan.no_rawat'
            )
            ->selectRaw("'pulang' as sumber")
            ->addSelect(
                'permintaan.tgl_permintaan as tanggal',
                'permintaan.jam'
            )
            ->selectRaw('LOWER(reg_periksa.status_lanjut) as status_layanan')
            ->selectRaw(
                "CASE
                    WHEN permintaan.status = 'Sudah'
                    THEN 'selesai'
                    ELSE 'menunggu'
                END as status_proses"
            )
            ->addSelect(
                'permintaan.tgl_validasi as tanggal_selesai',
                'permintaan.jam_validasi as jam_selesai',
                'dokter.nm_dokter',
                'poliklinik.nm_poli'
            )
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('detail_permintaan_resep_pulang as detail')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'detail.no_permintaan',
                        'permintaan.no_permintaan'
                    );
            }, 'jumlah_obat')
            ->selectRaw('0 as jumlah_racikan')
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber);
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
