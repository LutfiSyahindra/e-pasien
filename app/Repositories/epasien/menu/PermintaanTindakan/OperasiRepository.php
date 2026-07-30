<?php

namespace App\Repositories\epasien\menu\PermintaanTindakan;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperasiRepository
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

    public function paginateOperations(
        string $medicalRecordNumber,
        ?string $workflowStatus,
        ?string $careType,
        ?string $startDate,
        ?string $endDate,
        ?string $search,
        int $perPage
    ): LengthAwarePaginator {
        $query = $this->operationQuery($medicalRecordNumber, $search)
            ->when(
                $workflowStatus !== null,
                fn (Builder $query) => $this->applyWorkflowStatus(
                    $query,
                    $workflowStatus
                )
            )
            ->when(
                $careType !== null,
                fn (Builder $query) => $query->where(
                    'operation.status_layanan',
                    $careType
                )
            )
            ->when(
                $startDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'operation.tanggal_booking',
                    '>=',
                    $startDate
                )
            )
            ->when(
                $endDate !== null,
                fn (Builder $query) => $query->whereDate(
                    'operation.tanggal_booking',
                    '<=',
                    $endDate
                )
            )
            ->orderByDesc('operation.tanggal_booking')
            ->orderByDesc('operation.jam_mulai')
            ->orderByDesc('operation.no_rawat');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array{
     *     all: int,
     *     terjadwal: int,
     *     proses: int,
     *     menunggu_laporan: int,
     *     selesai: int
     * }
     */
    public function operationCounts(string $medicalRecordNumber): array
    {
        $reportAvailable = $this->reportAvailableSql();
        $actualAvailable = $this->actualAvailableSql();
        $counts = $this->operationQuery($medicalRecordNumber)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE
                    WHEN {$reportAvailable}
                    THEN 1 ELSE 0 END) as selesai"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN NOT {$reportAvailable}
                        AND (
                            operation.status_booking = 'Selesai'
                            OR {$actualAvailable}
                        )
                    THEN 1 ELSE 0 END) as menunggu_laporan"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN NOT {$reportAvailable}
                        AND NOT {$actualAvailable}
                        AND operation.status_booking = 'Proses Operasi'
                    THEN 1 ELSE 0 END) as proses"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN NOT {$reportAvailable}
                        AND NOT {$actualAvailable}
                        AND (
                            operation.status_booking IS NULL
                            OR operation.status_booking NOT IN (
                                'Proses Operasi',
                                'Selesai'
                            )
                        )
                    THEN 1 ELSE 0 END) as terjadwal"
            )
            ->first();

        return [
            'all' => (int) ($counts?->total ?? 0),
            'terjadwal' => (int) ($counts?->terjadwal ?? 0),
            'proses' => (int) ($counts?->proses ?? 0),
            'menunggu_laporan' => (int) ($counts?->menunggu_laporan ?? 0),
            'selesai' => (int) ($counts?->selesai ?? 0),
        ];
    }

    public function findOperationForPatient(
        string $medicalRecordNumber,
        string $treatmentNumber,
        string $bookingDate,
        string $startTime
    ): ?object {
        return $this->operationQuery($medicalRecordNumber)
            ->where('operation.no_rawat', $treatmentNumber)
            ->where('operation.tanggal_booking', $bookingDate)
            ->where('operation.jam_mulai', $startTime)
            ->first();
    }

    /**
     * @param  array<int, string>  $treatmentNumbers
     * @return Collection<int, object>
     */
    public function bookingPackages(array $treatmentNumbers): Collection
    {
        if ($treatmentNumbers === []) {
            return collect();
        }

        return $this->connection()
            ->table('booking_operasi as booking')
            ->leftJoin(
                'paket_operasi as paket',
                'paket.kode_paket',
                '=',
                'booking.kode_paket'
            )
            ->select(
                'booking.no_rawat',
                'booking.tanggal as tanggal_booking',
                'booking.jam_mulai',
                'booking.kode_paket',
                'paket.nm_perawatan',
                'paket.kategori'
            )
            ->whereIn('booking.no_rawat', $treatmentNumbers)
            ->orderBy('paket.nm_perawatan')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function performedOperations(string $treatmentNumber): Collection
    {
        return $this->connection()
            ->table('operasi as pelaksanaan')
            ->leftJoin(
                'paket_operasi as paket',
                'paket.kode_paket',
                '=',
                'pelaksanaan.kode_paket'
            )
            ->leftJoin(
                'dokter as operator_utama',
                'operator_utama.kd_dokter',
                '=',
                'pelaksanaan.operator1'
            )
            ->leftJoin(
                'dokter as dokter_anestesi',
                'dokter_anestesi.kd_dokter',
                '=',
                'pelaksanaan.dokter_anestesi'
            )
            ->select(
                'pelaksanaan.no_rawat',
                'pelaksanaan.tgl_operasi',
                'pelaksanaan.jenis_anasthesi',
                'pelaksanaan.kategori',
                'pelaksanaan.kode_paket',
                'pelaksanaan.status as status_layanan',
                'operator_utama.nm_dokter as nm_operator_utama',
                'dokter_anestesi.nm_dokter as nm_dokter_anestesi',
                'paket.nm_perawatan'
            )
            ->where('pelaksanaan.no_rawat', $treatmentNumber)
            ->orderBy('pelaksanaan.tgl_operasi')
            ->orderBy('paket.nm_perawatan')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function operationReports(string $treatmentNumber): Collection
    {
        return $this->connection()
            ->table('laporan_operasi')
            ->select(
                'no_rawat',
                'tanggal',
                'diagnosa_preop',
                'diagnosa_postop',
                'jaringan_dieksekusi',
                'selesaioperasi',
                'permintaan_pa',
                'laporan_operasi'
            )
            ->where('no_rawat', $treatmentNumber)
            ->orderBy('tanggal')
            ->get();
    }

    private function operationQuery(
        string $medicalRecordNumber,
        ?string $search = null
    ): Builder {
        return $this->connection()
            ->query()
            ->fromSub(
                $this->bookingEventQuery($medicalRecordNumber, $search),
                'operation'
            );
    }

    private function bookingEventQuery(
        string $medicalRecordNumber,
        ?string $search
    ): Builder {
        return $this->connection()
            ->table('booking_operasi as booking')
            ->join(
                'reg_periksa',
                'reg_periksa.no_rawat',
                '=',
                'booking.no_rawat'
            )
            ->leftJoin(
                'dokter as dokter_operator',
                'dokter_operator.kd_dokter',
                '=',
                'booking.kd_dokter'
            )
            ->leftJoin(
                'ruang_ok',
                'ruang_ok.kd_ruang_ok',
                '=',
                'booking.kd_ruang_ok'
            )
            ->leftJoin(
                'poliklinik',
                'poliklinik.kd_poli',
                '=',
                'reg_periksa.kd_poli'
            )
            ->select(
                'booking.no_rawat',
                'booking.tanggal as tanggal_booking',
                'booking.jam_mulai',
                'booking.jam_selesai',
                'booking.status as status_booking',
                'booking.kd_dokter',
                'booking.kd_ruang_ok',
                'dokter_operator.nm_dokter as nm_dokter_operator',
                'ruang_ok.nm_ruang_ok',
                'reg_periksa.status_lanjut as status_layanan',
                'poliklinik.nm_poli'
            )
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('booking_operasi as booking_count')
                    ->selectRaw(
                        "COUNT(DISTINCT CONCAT_WS(
                            '|',
                            booking_count.tanggal,
                            booking_count.jam_mulai
                        ))"
                    )
                    ->whereColumn(
                        'booking_count.no_rawat',
                        'booking.no_rawat'
                    );
            }, 'jumlah_booking_rawat')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('booking_operasi as booking_date_count')
                    ->selectRaw(
                        'COUNT(DISTINCT booking_date_count.jam_mulai)'
                    )
                    ->whereColumn(
                        'booking_date_count.no_rawat',
                        'booking.no_rawat'
                    )
                    ->whereColumn(
                        'booking_date_count.tanggal',
                        'booking.tanggal'
                    );
            }, 'jumlah_booking_tanggal')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('operasi as actual_count')
                    ->selectRaw('COUNT(DISTINCT actual_count.tgl_operasi)')
                    ->whereColumn('actual_count.no_rawat', 'booking.no_rawat');
            }, 'jumlah_pelaksanaan_rawat')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('laporan_operasi as report_count')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('report_count.no_rawat', 'booking.no_rawat');
            }, 'jumlah_laporan_rawat')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('operasi as actual_exact')
                    ->select('actual_exact.tgl_operasi')
                    ->whereColumn('actual_exact.no_rawat', 'booking.no_rawat')
                    ->whereRaw(
                        'DATE(actual_exact.tgl_operasi) = booking.tanggal'
                    )
                    ->orderByRaw(
                        "ABS(TIMESTAMPDIFF(
                            SECOND,
                            CONCAT(booking.tanggal, ' ', booking.jam_mulai),
                            actual_exact.tgl_operasi
                        ))"
                    )
                    ->limit(1);
            }, 'pelaksanaan_tanggal_tepat')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('operasi as actual_nearest')
                    ->select('actual_nearest.tgl_operasi')
                    ->whereColumn('actual_nearest.no_rawat', 'booking.no_rawat')
                    ->orderByRaw(
                        "ABS(TIMESTAMPDIFF(
                            SECOND,
                            CONCAT(booking.tanggal, ' ', booking.jam_mulai),
                            actual_nearest.tgl_operasi
                        ))"
                    )
                    ->limit(1);
            }, 'pelaksanaan_tanggal_terdekat')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('laporan_operasi as report_exact')
                    ->select('report_exact.tanggal')
                    ->whereColumn('report_exact.no_rawat', 'booking.no_rawat')
                    ->whereRaw('DATE(report_exact.tanggal) = booking.tanggal')
                    ->orderByRaw(
                        "ABS(TIMESTAMPDIFF(
                            SECOND,
                            CONCAT(booking.tanggal, ' ', booking.jam_mulai),
                            report_exact.tanggal
                        ))"
                    )
                    ->limit(1);
            }, 'laporan_tanggal_tepat')
            ->selectSub(function (Builder $query): void {
                $query
                    ->from('laporan_operasi as report_nearest')
                    ->select('report_nearest.tanggal')
                    ->whereColumn('report_nearest.no_rawat', 'booking.no_rawat')
                    ->orderByRaw(
                        "ABS(TIMESTAMPDIFF(
                            SECOND,
                            CONCAT(booking.tanggal, ' ', booking.jam_mulai),
                            report_nearest.tanggal
                        ))"
                    )
                    ->limit(1);
            }, 'laporan_tanggal_terdekat')
            ->where('reg_periksa.no_rkm_medis', $medicalRecordNumber)
            ->when(
                $search !== null,
                function (Builder $query) use ($search): void {
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('booking.no_rawat', 'like', '%'.$search.'%')
                            ->orWhere(
                                'dokter_operator.nm_dokter',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'ruang_ok.nm_ruang_ok',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhere(
                                'poliklinik.nm_poli',
                                'like',
                                '%'.$search.'%'
                            )
                            ->orWhereExists(
                                function (Builder $packageQuery) use ($search): void {
                                    $packageQuery
                                        ->selectRaw('1')
                                        ->from('booking_operasi as booking_search')
                                        ->leftJoin(
                                            'paket_operasi as package_search',
                                            'package_search.kode_paket',
                                            '=',
                                            'booking_search.kode_paket'
                                        )
                                        ->whereColumn(
                                            'booking_search.no_rawat',
                                            'booking.no_rawat'
                                        )
                                        ->whereColumn(
                                            'booking_search.tanggal',
                                            'booking.tanggal'
                                        )
                                        ->whereColumn(
                                            'booking_search.jam_mulai',
                                            'booking.jam_mulai'
                                        )
                                        ->where(function (Builder $packageName) use ($search): void {
                                            $packageName
                                                ->where(
                                                    'package_search.nm_perawatan',
                                                    'like',
                                                    '%'.$search.'%'
                                                )
                                                ->orWhere(
                                                    'package_search.kategori',
                                                    'like',
                                                    '%'.$search.'%'
                                                );
                                        });
                                }
                            );
                    });
                }
            )
            ->distinct();
    }

    private function applyWorkflowStatus(
        Builder $query,
        string $workflowStatus
    ): void {
        if ($workflowStatus === 'selesai') {
            $this->applyReportAvailability($query, true);

            return;
        }

        $this->applyReportAvailability($query, false);

        if ($workflowStatus === 'menunggu_laporan') {
            $query->where(function (Builder $awaitingReport): void {
                $awaitingReport
                    ->where('operation.status_booking', 'Selesai')
                    ->orWhere(function (Builder $hasActual): void {
                        $this->applyActualAvailability($hasActual, true);
                    });
            });

            return;
        }

        $this->applyActualAvailability($query, false);

        if ($workflowStatus === 'proses') {
            $query->where(
                'operation.status_booking',
                'Proses Operasi'
            );

            return;
        }

        $query->where(function (Builder $scheduled): void {
            $scheduled
                ->whereNull('operation.status_booking')
                ->orWhereNotIn('operation.status_booking', [
                    'Proses Operasi',
                    'Selesai',
                ]);
        });
    }

    private function applyReportAvailability(
        Builder $query,
        bool $available
    ): void {
        if ($available) {
            $query->where(function (Builder $hasReport): void {
                $hasReport
                    ->where(function (Builder $exactReport): void {
                        $exactReport
                            ->whereNotNull(
                                'operation.laporan_tanggal_tepat'
                            )
                            ->where(
                                'operation.jumlah_booking_tanggal',
                                1
                            );
                    })
                    ->orWhere(function (Builder $unambiguousReport): void {
                        $unambiguousReport
                            ->where('operation.jumlah_booking_rawat', 1)
                            ->where('operation.jumlah_laporan_rawat', 1);
                    });
            });

            return;
        }

        $query
            ->where(function (Builder $missingExactReport): void {
                $missingExactReport
                    ->whereNull('operation.laporan_tanggal_tepat')
                    ->orWhere(
                        'operation.jumlah_booking_tanggal',
                        '<>',
                        1
                    );
            })
            ->where(function (Builder $ambiguousOrMissing): void {
                $ambiguousOrMissing
                    ->where('operation.jumlah_booking_rawat', '<>', 1)
                    ->orWhere('operation.jumlah_laporan_rawat', '<>', 1);
            });
    }

    private function applyActualAvailability(
        Builder $query,
        bool $available
    ): void {
        if ($available) {
            $query->where(function (Builder $hasActual): void {
                $hasActual
                    ->where(function (Builder $exactActual): void {
                        $exactActual
                            ->whereNotNull(
                                'operation.pelaksanaan_tanggal_tepat'
                            )
                            ->where(
                                'operation.jumlah_booking_tanggal',
                                1
                            );
                    })
                    ->orWhere(function (Builder $unambiguousActual): void {
                        $unambiguousActual
                            ->where('operation.jumlah_booking_rawat', 1)
                            ->where('operation.jumlah_pelaksanaan_rawat', 1);
                    });
            });

            return;
        }

        $query
            ->where(function (Builder $missingExactActual): void {
                $missingExactActual
                    ->whereNull('operation.pelaksanaan_tanggal_tepat')
                    ->orWhere(
                        'operation.jumlah_booking_tanggal',
                        '<>',
                        1
                    );
            })
            ->where(function (Builder $ambiguousOrMissing): void {
                $ambiguousOrMissing
                    ->where('operation.jumlah_booking_rawat', '<>', 1)
                    ->orWhere(
                        'operation.jumlah_pelaksanaan_rawat',
                        '<>',
                        1
                    );
            });
    }

    private function reportAvailableSql(): string
    {
        return '((operation.laporan_tanggal_tepat IS NOT NULL
                AND operation.jumlah_booking_tanggal = 1)
            OR (
                operation.jumlah_booking_rawat = 1
                AND operation.jumlah_laporan_rawat = 1
            ))';
    }

    private function actualAvailableSql(): string
    {
        return '((operation.pelaksanaan_tanggal_tepat IS NOT NULL
                AND operation.jumlah_booking_tanggal = 1)
            OR (
                operation.jumlah_booking_rawat = 1
                AND operation.jumlah_pelaksanaan_rawat = 1
            ))';
    }

    private function connection(): Connection
    {
        return DB::connection('mysql_khanza');
    }
}
