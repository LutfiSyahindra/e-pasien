<?php

namespace App\Services\epasien\menu;

use App\Models\User;
use App\Repositories\epasien\menu\OperasiRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OperasiService
{
    private const DAYS = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    private const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function __construct(
        private readonly OperasiRepository $operasiRepository
    ) {}

    public function patientForUser(User $user): ?object
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return null;
        }

        return $this->operasiRepository->findPatient($medicalRecordNumber);
    }

    public function operationsForUser(
        User $user,
        ?string $workflowStatus = null,
        ?string $careType = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $search = null,
        int $perPage = 8
    ): LengthAwarePaginator {
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $perPage = max(4, min($perPage, 20));

        if ($medicalRecordNumber === '') {
            return $this->emptyPaginator($perPage);
        }

        $operations = $this->operasiRepository->paginateOperations(
            $medicalRecordNumber,
            $workflowStatus,
            $careType,
            $startDate,
            $endDate,
            $this->nullableText($search),
            $perPage
        );
        $treatmentNumbers = $operations->getCollection()
            ->pluck('no_rawat')
            ->map(fn (mixed $number): string => trim((string) $number))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $packages = $this->operasiRepository
            ->bookingPackages($treatmentNumbers)
            ->groupBy(fn (object $row): string => $this->eventKey(
                $row->no_rawat ?? null,
                $row->tanggal_booking ?? null,
                $row->jam_mulai ?? null
            ));

        $operations->setCollection(
            $operations->getCollection()->map(
                fn (object $operation): array => $this->formatOperation(
                    $operation,
                    $packages->get(
                        $this->eventKey(
                            $operation->no_rawat ?? null,
                            $operation->tanggal_booking ?? null,
                            $operation->jam_mulai ?? null
                        ),
                        collect()
                    )
                )
            )
        );

        return $operations;
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
    public function countsForUser(User $user): array
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return $this->emptyCounts();
        }

        return $this->operasiRepository
            ->operationCounts($medicalRecordNumber);
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
    public function emptyCounts(): array
    {
        return [
            'all' => 0,
            'terjadwal' => 0,
            'proses' => 0,
            'menunggu_laporan' => 0,
            'selesai' => 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detailForUser(
        User $user,
        string $treatmentNumber,
        string $bookingDate,
        string $startTime
    ): ?array {
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $treatmentNumber = trim($treatmentNumber);
        $bookingDate = trim($bookingDate);
        $startTime = $this->timeKey($startTime);

        if (
            $medicalRecordNumber === ''
            || $treatmentNumber === ''
            || $bookingDate === ''
            || $startTime === ''
        ) {
            return null;
        }

        $operation = $this->operasiRepository->findOperationForPatient(
            $medicalRecordNumber,
            $treatmentNumber,
            $bookingDate,
            $startTime
        );

        if ($operation === null) {
            return null;
        }

        $packages = $this->operasiRepository
            ->bookingPackages([$treatmentNumber])
            ->filter(fn (object $row): bool => $this->eventKey(
                $row->no_rawat ?? null,
                $row->tanggal_booking ?? null,
                $row->jam_mulai ?? null
            ) === $this->eventKey(
                $treatmentNumber,
                $bookingDate,
                $startTime
            ))
            ->values();
        $formattedOperation = $this->formatOperation($operation, $packages);
        $performedRows = $this->rowsAtTimestamp(
            $this->operasiRepository->performedOperations($treatmentNumber),
            $this->selectedActualTimestamp($operation),
            'tgl_operasi'
        );
        $report = $this->rowAtTimestamp(
            $this->operasiRepository->operationReports($treatmentNumber),
            $this->selectedReportTimestamp($operation),
            'tanggal'
        );

        return [
            'booking' => $formattedOperation,
            'pelaksanaan' => $this->formatPerformedOperation($performedRows),
            'laporan' => $this->formatReport($report),
        ];
    }

    private function medicalRecordNumber(User $user): string
    {
        return trim((string) $user->username);
    }

    /**
     * @param  Collection<int, object>  $packages
     * @return array<string, mixed>
     */
    private function formatOperation(
        object $operation,
        Collection $packages
    ): array {
        $bookingAt = $this->dateValue(
            $operation->tanggal_booking ?? null
        );
        $actualAt = $this->dateTimeValue(
            $this->selectedActualTimestamp($operation)
        );
        $reportAt = $this->dateTimeValue(
            $this->selectedReportTimestamp($operation)
        );
        $workflowStatus = $this->workflowStatus(
            $operation,
            $actualAt,
            $reportAt
        );
        $formattedPackages = $this->formatPackages($packages);
        $careType = mb_strtolower(trim(
            (string) ($operation->status_layanan ?? '')
        ));

        return [
            'no_rawat' => trim((string) ($operation->no_rawat ?? '')),
            'tanggal_booking' => $bookingAt?->toDateString(),
            'tanggal_booking_lengkap' => $this->longDate($bookingAt),
            'jam_mulai' => $this->timeValue(
                $operation->jam_mulai ?? null
            ),
            'jam_mulai_key' => $this->timeKey(
                $operation->jam_mulai ?? null
            ),
            'jam_selesai' => $this->timeValue(
                $operation->jam_selesai ?? null
            ),
            'durasi_jadwal' => $this->scheduledDuration(
                $bookingAt,
                $operation->jam_mulai ?? null,
                $operation->jam_selesai ?? null
            ),
            'hari_short' => $bookingAt
                ? substr(self::DAYS[$bookingAt->dayOfWeek], 0, 3)
                : '-',
            'tanggal_angka' => $bookingAt?->format('d') ?? '-',
            'bulan_short' => $bookingAt
                ? substr(self::MONTHS[(int) $bookingAt->format('n')], 0, 3)
                : '-',
            'status' => $workflowStatus,
            'status_label' => match ($workflowStatus) {
                'selesai' => 'Laporan Tersedia',
                'menunggu_laporan' => 'Menunggu Laporan',
                'proses' => 'Sedang Operasi',
                default => 'Terjadwal',
            },
            'status_icon' => match ($workflowStatus) {
                'selesai' => 'bi-check-circle',
                'menunggu_laporan' => 'bi-file-earmark-medical',
                'proses' => 'bi-heart-pulse',
                default => 'bi-calendar2-check',
            },
            'status_booking' => trim(
                (string) ($operation->status_booking ?? '')
            ) ?: '-',
            'status_layanan' => $careType ?: '-',
            'jenis_layanan' => $careType === 'ranap'
                ? 'Rawat Inap'
                : 'Rawat Jalan',
            'layanan_tone' => $careType === 'ranap' ? 'ranap' : 'ralan',
            'dokter_operator' => trim(
                (string) ($operation->nm_dokter_operator ?? '')
            ) ?: '-',
            'ruang_operasi' => trim(
                (string) ($operation->nm_ruang_ok ?? '')
            ) ?: '-',
            'poli' => trim((string) ($operation->nm_poli ?? '')) ?: '-',
            'tanggal_pelaksanaan' => $actualAt?->toDateString(),
            'tanggal_pelaksanaan_lengkap' => $this->longDate($actualAt),
            'jam_pelaksanaan' => $actualAt?->format('H:i') ?? '-',
            'pelaksanaan_tercatat' => $actualAt !== null,
            'tanggal_laporan' => $reportAt?->toDateString(),
            'tanggal_laporan_lengkap' => $this->longDate($reportAt),
            'jam_laporan' => $reportAt?->format('H:i') ?? '-',
            'laporan_tersedia' => $reportAt !== null,
            'jumlah_tindakan' => count($formattedPackages),
            'judul' => $formattedPackages[0]['nama'] ?? 'Operasi',
            'kategori' => collect($formattedPackages)
                ->pluck('kategori')
                ->filter(fn (string $category): bool => $category !== '-')
                ->unique()
                ->implode(', ') ?: '-',
            'tindakan' => $formattedPackages,
        ];
    }

    /**
     * @param  Collection<int, object>  $packages
     * @return array<int, array{kode: string, nama: string, kategori: string}>
     */
    private function formatPackages(Collection $packages): array
    {
        return $packages
            ->map(function (object $package): array {
                $code = trim((string) ($package->kode_paket ?? ''));

                return [
                    'kode' => $code,
                    'nama' => trim(
                        (string) ($package->nm_perawatan ?? '')
                    ) ?: ($code ?: 'Tindakan operasi'),
                    'kategori' => trim(
                        (string) ($package->kategori ?? '')
                    ) ?: '-',
                ];
            })
            ->filter(fn (array $package): bool => $package['kode'] !== '')
            ->unique('kode')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    private function formatPerformedOperation(Collection $rows): array
    {
        $first = $rows->first();
        $performedAt = $this->dateTimeValue(
            $first?->tgl_operasi ?? null
        );
        $procedures = $rows
            ->map(function (object $row): array {
                $code = trim((string) ($row->kode_paket ?? ''));

                return [
                    'kode' => $code,
                    'nama' => trim(
                        (string) ($row->nm_perawatan ?? '')
                    ) ?: ($code ?: 'Tindakan operasi'),
                    'kategori' => trim(
                        (string) ($row->kategori ?? '')
                    ) ?: '-',
                ];
            })
            ->filter(fn (array $procedure): bool => $procedure['kode'] !== '')
            ->unique('kode')
            ->values()
            ->all();

        return [
            'tersedia' => $first !== null,
            'tanggal' => $performedAt?->toDateString(),
            'tanggal_lengkap' => $this->longDate($performedAt),
            'jam' => $performedAt?->format('H:i') ?? '-',
            'jenis_anestesi' => trim(
                (string) ($first?->jenis_anasthesi ?? '')
            ) ?: '-',
            'kategori' => trim(
                (string) ($first?->kategori ?? '')
            ) ?: '-',
            'operator_utama' => trim(
                (string) ($first?->nm_operator_utama ?? '')
            ) ?: '-',
            'dokter_anestesi' => trim(
                (string) ($first?->nm_dokter_anestesi ?? '')
            ) ?: '-',
            'status_layanan' => trim(
                (string) ($first?->status_layanan ?? '')
            ) ?: '-',
            'jumlah_tindakan' => count($procedures),
            'tindakan' => $procedures,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatReport(?object $report): array
    {
        $startedAt = $this->dateTimeValue($report?->tanggal ?? null);
        $finishedAt = $this->dateTimeValue(
            $report?->selesaioperasi ?? null
        );

        return [
            'tersedia' => $report !== null,
            'tanggal_mulai' => $startedAt?->toDateTimeString(),
            'tanggal_mulai_lengkap' => $this->longDateTime($startedAt),
            'tanggal_selesai' => $finishedAt?->toDateTimeString(),
            'tanggal_selesai_lengkap' => $this->longDateTime($finishedAt),
            'durasi' => $this->duration($startedAt, $finishedAt),
            'diagnosa_preoperasi' => trim(
                (string) ($report?->diagnosa_preop ?? '')
            ) ?: '-',
            'diagnosa_postoperasi' => trim(
                (string) ($report?->diagnosa_postop ?? '')
            ) ?: '-',
            'jaringan_dieksekusi' => trim(
                (string) ($report?->jaringan_dieksekusi ?? '')
            ) ?: '-',
            'permintaan_pa' => trim(
                (string) ($report?->permintaan_pa ?? '')
            ) ?: '-',
            'narasi' => trim(
                (string) ($report?->laporan_operasi ?? '')
            ) ?: '-',
        ];
    }

    private function workflowStatus(
        object $operation,
        ?Carbon $actualAt,
        ?Carbon $reportAt
    ): string {
        if ($reportAt !== null) {
            return 'selesai';
        }

        $bookingStatus = mb_strtolower(trim(
            (string) ($operation->status_booking ?? '')
        ));

        if ($actualAt !== null || $bookingStatus === 'selesai') {
            return 'menunggu_laporan';
        }

        if ($bookingStatus === 'proses operasi') {
            return 'proses';
        }

        return 'terjadwal';
    }

    private function selectedActualTimestamp(object $operation): ?string
    {
        $exact = trim((string) (
            $operation->pelaksanaan_tanggal_tepat ?? ''
        ));

        if (
            $exact !== ''
            && (int) ($operation->jumlah_booking_tanggal ?? 0) === 1
        ) {
            return $exact;
        }

        if (
            (int) ($operation->jumlah_booking_rawat ?? 0) === 1
            && (int) ($operation->jumlah_pelaksanaan_rawat ?? 0) === 1
        ) {
            return $this->nullableText(
                $operation->pelaksanaan_tanggal_terdekat ?? null
            );
        }

        return null;
    }

    private function selectedReportTimestamp(object $operation): ?string
    {
        $exact = trim((string) (
            $operation->laporan_tanggal_tepat ?? ''
        ));

        if (
            $exact !== ''
            && (int) ($operation->jumlah_booking_tanggal ?? 0) === 1
        ) {
            return $exact;
        }

        if (
            (int) ($operation->jumlah_booking_rawat ?? 0) === 1
            && (int) ($operation->jumlah_laporan_rawat ?? 0) === 1
        ) {
            return $this->nullableText(
                $operation->laporan_tanggal_terdekat ?? null
            );
        }

        return null;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function rowsAtTimestamp(
        Collection $rows,
        ?string $timestamp,
        string $column
    ): Collection {
        if ($timestamp === null) {
            return collect();
        }

        return $rows
            ->filter(fn (object $row): bool => trim(
                (string) ($row->{$column} ?? '')
            ) === $timestamp)
            ->values();
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function rowAtTimestamp(
        Collection $rows,
        ?string $timestamp,
        string $column
    ): ?object {
        return $this->rowsAtTimestamp($rows, $timestamp, $column)->first();
    }

    private function eventKey(
        mixed $treatmentNumber,
        mixed $bookingDate,
        mixed $startTime
    ): string {
        return trim((string) $treatmentNumber)
            .'|'.trim((string) $bookingDate)
            .'|'.$this->timeKey($startTime);
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function dateValue(mixed $date): ?Carbon
    {
        $value = trim((string) $date);

        if ($value === '' || $value === '0000-00-00') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function dateTimeValue(mixed $dateTime): ?Carbon
    {
        $value = trim((string) $dateTime);

        if (
            $value === ''
            || str_starts_with($value, '0000-00-00')
        ) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function longDate(?Carbon $date): string
    {
        if ($date === null) {
            return '-';
        }

        return self::DAYS[$date->dayOfWeek].', '
            .$date->format('d').' '
            .self::MONTHS[(int) $date->format('n')].' '
            .$date->format('Y');
    }

    private function longDateTime(?Carbon $dateTime): string
    {
        return $dateTime
            ? $this->longDate($dateTime).' · '.$dateTime->format('H:i').' WIB'
            : '-';
    }

    private function timeKey(mixed $time): string
    {
        $value = trim((string) $time);

        return $value !== '' ? substr($value, 0, 8) : '';
    }

    private function timeValue(mixed $time): string
    {
        $value = $this->timeKey($time);

        return $value !== '' && $value !== '00:00:00'
            ? substr($value, 0, 5)
            : '-';
    }

    private function scheduledDuration(
        ?Carbon $date,
        mixed $startTime,
        mixed $endTime
    ): string {
        if ($date === null) {
            return '-';
        }

        $start = $this->timeKey($startTime);
        $end = $this->timeKey($endTime);

        if ($start === '' || $end === '' || $end === '00:00:00') {
            return '-';
        }

        try {
            $startAt = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $date->toDateString().' '.$start
            );
            $endAt = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $date->toDateString().' '.$end
            );

            if ($endAt->lessThan($startAt)) {
                $endAt->addDay();
            }

            return $this->duration($startAt, $endAt);
        } catch (\Throwable) {
            return '-';
        }
    }

    private function duration(?Carbon $start, ?Carbon $end): string
    {
        if ($start === null || $end === null || $end->lessThan($start)) {
            return '-';
        }

        $minutes = (int) $start->diffInMinutes($end);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours === 0) {
            return $remainingMinutes.' menit';
        }

        return $hours.' jam'
            .($remainingMinutes > 0 ? ' '.$remainingMinutes.' menit' : '');
    }

    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            LengthAwarePaginator::resolveCurrentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }
}
