<?php

namespace App\Repositories\epasien\menu\PermintaanTindakan;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PemeriksaanLaboratRepository
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

    public function paginateRequests(
        string $medicalRecordNumber,
        ?string $resultStatus,
        ?string $careType,
        ?string $startDate,
        ?string $endDate,
        ?string $search,
        int $perPage
    ): LengthAwarePaginator {
        return $this->requestQuery($medicalRecordNumber)
            ->when(
                $resultStatus !== null,
                fn (Builder $query) => $this->applyResultStatus($query, $resultStatus)
            )
            ->when(
                $careType !== null,
                fn (Builder $query) => $query->where('permintaan_lab.status', $careType)
            )
            ->when(
                $startDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'permintaan_lab.tgl_permintaan',
                    '>=',
                    $startDate
                )
            )
            ->when(
                $endDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'permintaan_lab.tgl_permintaan',
                    '<=',
                    $endDate
                )
            )
            ->when(
                $search !== null,
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('permintaan_lab.noorder', 'like', '%'.$search.'%')
                            ->orWhere('permintaan_lab.no_rawat', 'like', '%'.$search.'%')
                            ->orWhere('dokter_perujuk.nm_dokter', 'like', '%'.$search.'%')
                            ->orWhere('poliklinik.nm_poli', 'like', '%'.$search.'%')
                            ->orWhere('permintaan_lab.diagnosa_klinis', 'like', '%'.$search.'%');
                    });
                }
            )
            ->orderByDesc('permintaan_lab.tgl_permintaan')
            ->orderByDesc('permintaan_lab.jam_permintaan')
            ->orderByDesc('permintaan_lab.noorder')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{all: int, menunggu: int, proses: int, selesai: int}
     */
    public function requestCounts(string $medicalRecordNumber): array
    {
        $counts = $this->connection()
            ->table('permintaan_lab')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'permintaan_lab.no_rawat'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE
                    WHEN permintaan_lab.tgl_hasil IS NOT NULL
                        AND permintaan_lab.tgl_hasil <> '0000-00-00'
                    THEN 1 ELSE 0 END) as selesai"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN (permintaan_lab.tgl_hasil IS NULL
                        OR permintaan_lab.tgl_hasil = '0000-00-00')
                        AND permintaan_lab.tgl_sampel IS NOT NULL
                        AND permintaan_lab.tgl_sampel <> '0000-00-00'
                    THEN 1 ELSE 0 END) as proses"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN (permintaan_lab.tgl_hasil IS NULL
                        OR permintaan_lab.tgl_hasil = '0000-00-00')
                        AND (permintaan_lab.tgl_sampel IS NULL
                        OR permintaan_lab.tgl_sampel = '0000-00-00')
                    THEN 1 ELSE 0 END) as menunggu"
            )
            ->first();

        return [
            'all' => (int) ($counts?->total ?? 0),
            'menunggu' => (int) ($counts?->menunggu ?? 0),
            'proses' => (int) ($counts?->proses ?? 0),
            'selesai' => (int) ($counts?->selesai ?? 0),
        ];
    }

    public function findRequestForPatient(
        string $medicalRecordNumber,
        string $orderNumber
    ): ?object {
        return $this->requestQuery($medicalRecordNumber)
            ->where('permintaan_lab.noorder', $orderNumber)
            ->first();
    }

    /**
     * @param  array<int, string>  $orderNumbers
     * @return Collection<int, object>
     */
    public function requestedExaminations(array $orderNumbers): Collection
    {
        if ($orderNumbers === []) {
            return collect();
        }

        return $this->connection()
            ->table('permintaan_detail_permintaan_lab as detail_permintaan')
            ->leftJoin(
                'jns_perawatan_lab',
                'jns_perawatan_lab.kd_jenis_prw',
                '=',
                'detail_permintaan.kd_jenis_prw'
            )
            ->select(
                'detail_permintaan.noorder',
                'detail_permintaan.kd_jenis_prw',
                'jns_perawatan_lab.nm_perawatan'
            )
            ->selectRaw('COUNT(*) as jumlah_parameter_diminta')
            ->whereIn('detail_permintaan.noorder', $orderNumbers)
            ->groupBy(
                'detail_permintaan.noorder',
                'detail_permintaan.kd_jenis_prw',
                'jns_perawatan_lab.nm_perawatan'
            )
            ->orderBy('jns_perawatan_lab.nm_perawatan')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function resultRows(
        string $orderNumber,
        string $treatmentNumber,
        string $resultDate
    ): Collection {
        return $this->connection()
            ->table('permintaan_detail_permintaan_lab as detail_permintaan')
            ->join(
                'detail_periksa_lab as hasil',
                function (JoinClause $join): void {
                    $join
                        ->on(
                            'hasil.kd_jenis_prw',
                            '=',
                            'detail_permintaan.kd_jenis_prw'
                        )
                        ->on(
                            'hasil.id_template',
                            '=',
                            'detail_permintaan.id_template'
                        );
                }
            )
            ->leftJoin(
                'template_laboratorium as template',
                function (JoinClause $join): void {
                    $join
                        ->on('template.kd_jenis_prw', '=', 'hasil.kd_jenis_prw')
                        ->on('template.id_template', '=', 'hasil.id_template');
                }
            )
            ->leftJoin(
                'jns_perawatan_lab as jenis',
                'jenis.kd_jenis_prw',
                '=',
                'hasil.kd_jenis_prw'
            )
            ->select(
                'hasil.kd_jenis_prw',
                'hasil.id_template',
                'hasil.tgl_periksa',
                'hasil.jam',
                'hasil.nilai',
                'hasil.nilai_rujukan',
                'hasil.keterangan',
                'jenis.nm_perawatan',
                'template.Pemeriksaan as nama_parameter',
                'template.satuan',
                'template.urut'
            )
            ->where('detail_permintaan.noorder', $orderNumber)
            ->where('hasil.no_rawat', $treatmentNumber)
            ->where('hasil.tgl_periksa', $resultDate)
            ->orderBy('hasil.jam')
            ->orderBy('jenis.nm_perawatan')
            ->orderBy('template.urut')
            ->orderBy('hasil.id_template')
            ->get();
    }

    private function requestQuery(string $medicalRecordNumber): Builder
    {
        return $this->connection()
            ->table('permintaan_lab')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'permintaan_lab.no_rawat'
            )
            ->leftJoin(
                'dokter as dokter_perujuk',
                'dokter_perujuk.kd_dokter',
                '=',
                'permintaan_lab.dokter_perujuk'
            )
            ->leftJoin(
                'poliklinik',
                'poliklinik.kd_poli',
                '=',
                'reg_periksa.kd_poli'
            )
            ->select(
                'permintaan_lab.noorder',
                'permintaan_lab.no_rawat',
                'permintaan_lab.tgl_permintaan',
                'permintaan_lab.jam_permintaan',
                'permintaan_lab.tgl_sampel',
                'permintaan_lab.jam_sampel',
                'permintaan_lab.tgl_hasil',
                'permintaan_lab.jam_hasil',
                'permintaan_lab.dokter_perujuk',
                'permintaan_lab.status',
                'permintaan_lab.informasi_tambahan',
                'permintaan_lab.diagnosa_klinis',
                'dokter_perujuk.nm_dokter',
                'poliklinik.nm_poli'
            )
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('permintaan_detail_permintaan_lab as detail_diminta')
                    ->selectRaw('COUNT(DISTINCT detail_diminta.kd_jenis_prw)')
                    ->whereColumn(
                        'detail_diminta.noorder',
                        'permintaan_lab.noorder'
                    );
            }, 'jumlah_pemeriksaan')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('permintaan_detail_permintaan_lab as detail_diminta')
                    ->join(
                        'detail_periksa_lab as detail_hasil',
                        function (JoinClause $join): void {
                            $join
                                ->on(
                                    'detail_hasil.kd_jenis_prw',
                                    '=',
                                    'detail_diminta.kd_jenis_prw'
                                )
                                ->on(
                                    'detail_hasil.id_template',
                                    '=',
                                    'detail_diminta.id_template'
                                );
                        }
                    )
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'detail_diminta.noorder',
                        'permintaan_lab.noorder'
                    )
                    ->whereColumn('detail_hasil.no_rawat', 'permintaan_lab.no_rawat')
                    ->whereColumn('detail_hasil.tgl_periksa', 'permintaan_lab.tgl_hasil');
            }, 'jumlah_hasil')
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber);
    }

    private function applyResultStatus(Builder $query, string $resultStatus): void
    {
        if ($resultStatus === 'selesai') {
            $query
                ->whereNotNull('permintaan_lab.tgl_hasil')
                ->where('permintaan_lab.tgl_hasil', '<>', '0000-00-00');

            return;
        }

        $query->where(function (Builder $pendingResult): void {
            $pendingResult
                ->whereNull('permintaan_lab.tgl_hasil')
                ->orWhere('permintaan_lab.tgl_hasil', '0000-00-00');
        });

        if ($resultStatus === 'proses') {
            $query
                ->whereNotNull('permintaan_lab.tgl_sampel')
                ->where('permintaan_lab.tgl_sampel', '<>', '0000-00-00');

            return;
        }

        $query->where(function (Builder $pendingSample): void {
            $pendingSample
                ->whereNull('permintaan_lab.tgl_sampel')
                ->orWhere('permintaan_lab.tgl_sampel', '0000-00-00');
        });
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
