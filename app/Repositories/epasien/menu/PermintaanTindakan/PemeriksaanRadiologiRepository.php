<?php

namespace App\Repositories\epasien\menu\PermintaanTindakan;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PemeriksaanRadiologiRepository
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
                fn (Builder $query) => $query->where(
                    'permintaan_radiologi.status',
                    $careType
                )
            )
            ->when(
                $startDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'permintaan_radiologi.tgl_permintaan',
                    '>=',
                    $startDate
                )
            )
            ->when(
                $endDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'permintaan_radiologi.tgl_permintaan',
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
                                'permintaan_radiologi.noorder',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'permintaan_radiologi.no_rawat',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'dokter_perujuk.nm_dokter',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'poliklinik.nm_poli',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'permintaan_radiologi.diagnosa_klinis',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhereExists(function (Builder $detailQuery) use ($search): void {
                                $detailQuery
                                    ->selectRaw('1')
                                    ->from(
                                        'permintaan_pemeriksaan_radiologi as detail_cari'
                                    )
                                    ->leftJoin(
                                        'jns_perawatan_radiologi as jenis_cari',
                                        'jenis_cari.kd_jenis_prw',
                                        '=',
                                        'detail_cari.kd_jenis_prw'
                                    )
                                    ->whereColumn(
                                        'detail_cari.noorder',
                                        'permintaan_radiologi.noorder'
                                    )
                                    ->where(
                                        'jenis_cari.nm_perawatan',
                                        'like',
                                        '%'.$search.'%'
                                    );
                            });
                    });
                }
            )
            ->orderByDesc('permintaan_radiologi.tgl_permintaan')
            ->orderByDesc('permintaan_radiologi.jam_permintaan')
            ->orderByDesc('permintaan_radiologi.noorder')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{all: int, menunggu: int, proses: int, selesai: int}
     */
    public function requestCounts(string $medicalRecordNumber): array
    {
        $counts = $this->connection()
            ->table('permintaan_radiologi')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'permintaan_radiologi.no_rawat'
            )
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE
                    WHEN permintaan_radiologi.tgl_hasil IS NOT NULL
                        AND permintaan_radiologi.tgl_hasil <> '0000-00-00'
                    THEN 1 ELSE 0 END) as selesai"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN (permintaan_radiologi.tgl_hasil IS NULL
                        OR permintaan_radiologi.tgl_hasil = '0000-00-00')
                        AND permintaan_radiologi.tgl_sampel IS NOT NULL
                        AND permintaan_radiologi.tgl_sampel <> '0000-00-00'
                    THEN 1 ELSE 0 END) as proses"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN (permintaan_radiologi.tgl_hasil IS NULL
                        OR permintaan_radiologi.tgl_hasil = '0000-00-00')
                        AND (permintaan_radiologi.tgl_sampel IS NULL
                        OR permintaan_radiologi.tgl_sampel = '0000-00-00')
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
            ->where('permintaan_radiologi.noorder', $orderNumber)
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
            ->table('permintaan_pemeriksaan_radiologi as detail_permintaan')
            ->leftJoin(
                'jns_perawatan_radiologi as jenis',
                'jenis.kd_jenis_prw',
                '=',
                'detail_permintaan.kd_jenis_prw'
            )
            ->select(
                'detail_permintaan.noorder',
                'detail_permintaan.kd_jenis_prw',
                'detail_permintaan.stts_bayar',
                'jenis.nm_perawatan'
            )
            ->whereIn('detail_permintaan.noorder', $orderNumbers)
            ->orderBy('jenis.nm_perawatan')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function resultRows(string $treatmentNumber): Collection
    {
        return $this->connection()
            ->table('hasil_radiologi')
            ->select('no_rawat', 'tgl_periksa', 'jam', 'hasil')
            ->where('no_rawat', $treatmentNumber)
            ->orderByDesc('tgl_periksa')
            ->orderByDesc('jam')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function imageRows(string $treatmentNumber): Collection
    {
        return $this->connection()
            ->table('gambar_radiologi')
            ->select('no_rawat', 'tgl_periksa', 'jam', 'lokasi_gambar')
            ->where('no_rawat', $treatmentNumber)
            ->orderBy('tgl_periksa')
            ->orderBy('jam')
            ->orderBy('lokasi_gambar')
            ->get();
    }

    private function requestQuery(string $medicalRecordNumber): Builder
    {
        return $this->connection()
            ->table('permintaan_radiologi')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'permintaan_radiologi.no_rawat'
            )
            ->leftJoin(
                'dokter as dokter_perujuk',
                'dokter_perujuk.kd_dokter',
                '=',
                'permintaan_radiologi.dokter_perujuk'
            )
            ->leftJoin(
                'poliklinik',
                'poliklinik.kd_poli',
                '=',
                'reg_periksa.kd_poli'
            )
            ->select(
                'permintaan_radiologi.noorder',
                'permintaan_radiologi.no_rawat',
                'permintaan_radiologi.tgl_permintaan',
                'permintaan_radiologi.jam_permintaan',
                'permintaan_radiologi.tgl_sampel',
                'permintaan_radiologi.jam_sampel',
                'permintaan_radiologi.tgl_hasil',
                'permintaan_radiologi.jam_hasil',
                'permintaan_radiologi.dokter_perujuk',
                'permintaan_radiologi.status',
                'permintaan_radiologi.informasi_tambahan',
                'permintaan_radiologi.diagnosa_klinis',
                'dokter_perujuk.nm_dokter',
                'poliklinik.nm_poli'
            )
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('permintaan_pemeriksaan_radiologi as detail_diminta')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'detail_diminta.noorder',
                        'permintaan_radiologi.noorder'
                    );
            }, 'jumlah_pemeriksaan')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('hasil_radiologi as hasil')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'hasil.no_rawat',
                        'permintaan_radiologi.no_rawat'
                    );
            }, 'jumlah_hasil')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('gambar_radiologi as gambar')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'gambar.no_rawat',
                        'permintaan_radiologi.no_rawat'
                    );
            }, 'jumlah_gambar')
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber);
    }

    private function applyResultStatus(Builder $query, string $resultStatus): void
    {
        if ($resultStatus === 'selesai') {
            $query
                ->whereNotNull('permintaan_radiologi.tgl_hasil')
                ->where('permintaan_radiologi.tgl_hasil', '<>', '0000-00-00');

            return;
        }

        $query->where(function (Builder $pendingResult): void {
            $pendingResult
                ->whereNull('permintaan_radiologi.tgl_hasil')
                ->orWhere('permintaan_radiologi.tgl_hasil', '0000-00-00');
        });

        if ($resultStatus === 'proses') {
            $query
                ->whereNotNull('permintaan_radiologi.tgl_sampel')
                ->where('permintaan_radiologi.tgl_sampel', '<>', '0000-00-00');

            return;
        }

        $query->where(function (Builder $pendingExamination): void {
            $pendingExamination
                ->whereNull('permintaan_radiologi.tgl_sampel')
                ->orWhere('permintaan_radiologi.tgl_sampel', '0000-00-00');
        });
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
