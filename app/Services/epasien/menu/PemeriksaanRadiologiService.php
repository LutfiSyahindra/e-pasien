<?php

namespace App\Services\epasien\menu;

use App\Models\User;
use App\Repositories\epasien\menu\PemeriksaanRadiologiRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PemeriksaanRadiologiService
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
        private readonly PemeriksaanRadiologiRepository $pemeriksaanRadiologiRepository
    ) {}

    public function patientForUser(User $user): ?object
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return null;
        }

        return $this->pemeriksaanRadiologiRepository
            ->findPatient($medicalRecordNumber);
    }

    public function requestsForUser(
        User $user,
        ?string $resultStatus = null,
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

        $requests = $this->pemeriksaanRadiologiRepository->paginateRequests(
            $medicalRecordNumber,
            $resultStatus,
            $careType,
            $startDate,
            $endDate,
            $this->nullableText($search),
            $perPage
        );
        $orderNumbers = $requests->getCollection()
            ->pluck('noorder')
            ->map(fn (mixed $number): string => trim((string) $number))
            ->filter()
            ->values()
            ->all();
        $requestedExaminations = $this->pemeriksaanRadiologiRepository
            ->requestedExaminations($orderNumbers)
            ->groupBy(fn (object $row): string => trim(
                (string) ($row->noorder ?? '')
            ));

        $requests->setCollection(
            $requests->getCollection()->map(
                fn (object $request): array => $this->formatRequest(
                    $request,
                    $requestedExaminations->get(
                        trim((string) ($request->noorder ?? '')),
                        collect()
                    )
                )
            )
        );

        return $requests;
    }

    /**
     * @return array{all: int, menunggu: int, proses: int, selesai: int}
     */
    public function countsForUser(User $user): array
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return $this->emptyCounts();
        }

        return $this->pemeriksaanRadiologiRepository
            ->requestCounts($medicalRecordNumber);
    }

    /**
     * @return array{all: int, menunggu: int, proses: int, selesai: int}
     */
    public function emptyCounts(): array
    {
        return [
            'all' => 0,
            'menunggu' => 0,
            'proses' => 0,
            'selesai' => 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resultForUser(User $user, string $orderNumber): ?array
    {
        $request = $this->ownedRequest($user, $orderNumber);

        if ($request === null) {
            return null;
        }

        $orderNumber = trim((string) $request->noorder);
        $treatmentNumber = trim((string) $request->no_rawat);
        $examinations = $this->pemeriksaanRadiologiRepository
            ->requestedExaminations([$orderNumber]);
        $resultRows = $this->rowsForRequest(
            $this->pemeriksaanRadiologiRepository->resultRows($treatmentNumber),
            $request
        );
        $imageRows = $this->rowsForRequest(
            $this->pemeriksaanRadiologiRepository->imageRows($treatmentNumber),
            $request
        );
        $formattedRequest = $this->formatRequest($request, $examinations);
        $formattedExaminations = $this->formatExaminations($examinations);

        return [
            'permintaan' => $formattedRequest,
            'ringkasan' => [
                'jumlah_pemeriksaan' => count($formattedExaminations),
                'jumlah_hasil' => $resultRows->count(),
                'jumlah_gambar' => $imageRows->count(),
            ],
            'pemeriksaan' => $formattedExaminations,
            'hasil' => $resultRows
                ->map(fn (object $row): array => [
                    'tanggal' => $this->dateValue(
                        $row->tgl_periksa ?? null
                    )?->toDateString(),
                    'tanggal_lengkap' => $this->longDate(
                        $this->dateValue($row->tgl_periksa ?? null)
                    ),
                    'jam' => $this->timeValue($row->jam ?? null),
                    'narasi' => trim((string) ($row->hasil ?? '')) ?: '-',
                ])
                ->values()
                ->all(),
            'gambar' => $imageRows
                ->values()
                ->map(fn (object $row, int $index): array => [
                    'index' => $index,
                    'nama_file' => basename(str_replace(
                        '\\',
                        '/',
                        trim((string) ($row->lokasi_gambar ?? ''))
                    )),
                    'tanggal_lengkap' => $this->longDate(
                        $this->dateValue($row->tgl_periksa ?? null)
                    ),
                    'jam' => $this->timeValue($row->jam ?? null),
                    'url' => route('pemeriksaanRadiologi.image', [
                        'noorder' => $orderNumber,
                        'image' => $index,
                    ]),
                ])
                ->all(),
        ];
    }

    /**
     * @return array{path: string, filename: string}|null
     */
    public function imageForUser(
        User $user,
        string $orderNumber,
        int $imageIndex
    ): ?array {
        if ($imageIndex < 0) {
            return null;
        }

        $request = $this->ownedRequest($user, $orderNumber);

        if ($request === null) {
            return null;
        }

        $images = $this->rowsForRequest(
            $this->pemeriksaanRadiologiRepository->imageRows(
                trim((string) $request->no_rawat)
            ),
            $request
        )->values();
        $image = $images->get($imageIndex);

        if ($image === null) {
            return null;
        }

        $path = str_replace(
            '\\',
            '/',
            trim((string) ($image->lokasi_gambar ?? ''))
        );

        if (! $this->isSafeImagePath($path)) {
            return null;
        }

        return [
            'path' => ltrim($path, '/'),
            'filename' => basename($path),
        ];
    }

    private function ownedRequest(User $user, string $orderNumber): ?object
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $orderNumber = trim($orderNumber);

        if ($medicalRecordNumber === '' || $orderNumber === '') {
            return null;
        }

        return $this->pemeriksaanRadiologiRepository
            ->findRequestForPatient($medicalRecordNumber, $orderNumber);
    }

    private function medicalRecordNumber(User $user): string
    {
        return trim((string) $user->username);
    }

    /**
     * @param  Collection<int, object>  $requestedExaminations
     * @return array<string, mixed>
     */
    private function formatRequest(
        object $request,
        Collection $requestedExaminations
    ): array {
        $requestedAt = $this->dateValue($request->tgl_permintaan ?? null);
        $examinationAt = $this->dateValue($request->tgl_sampel ?? null);
        $resultAt = $this->dateValue($request->tgl_hasil ?? null);
        $careType = mb_strtolower(trim((string) ($request->status ?? '')));
        $status = $resultAt !== null
            ? 'selesai'
            : ($examinationAt !== null ? 'proses' : 'menunggu');
        $requestedItems = $this->formatExaminations($requestedExaminations);

        return [
            'noorder' => trim((string) ($request->noorder ?? '')),
            'no_rawat' => trim((string) ($request->no_rawat ?? '')),
            'tanggal_permintaan' => $requestedAt?->toDateString(),
            'tanggal_permintaan_lengkap' => $this->longDate($requestedAt),
            'jam_permintaan' => $this->timeValue($request->jam_permintaan ?? null),
            'hari_short' => $requestedAt
                ? substr(self::DAYS[$requestedAt->dayOfWeek], 0, 3)
                : '-',
            'tanggal_angka' => $requestedAt?->format('d') ?? '-',
            'bulan_short' => $requestedAt
                ? substr(self::MONTHS[(int) $requestedAt->format('n')], 0, 3)
                : '-',
            'tanggal_pemeriksaan' => $examinationAt?->toDateString(),
            'tanggal_pemeriksaan_lengkap' => $this->longDate($examinationAt),
            'jam_pemeriksaan' => $examinationAt
                ? $this->timeValue($request->jam_sampel ?? null)
                : '-',
            'tanggal_hasil' => $resultAt?->toDateString(),
            'tanggal_hasil_lengkap' => $this->longDate($resultAt),
            'jam_hasil' => $resultAt
                ? $this->timeValue($request->jam_hasil ?? null)
                : '-',
            'status' => $status,
            'status_label' => match ($status) {
                'selesai' => 'Hasil Tersedia',
                'proses' => 'Sedang Diperiksa',
                default => 'Menunggu Pemeriksaan',
            },
            'status_icon' => match ($status) {
                'selesai' => 'bi-check-circle',
                'proses' => 'bi-hourglass-split',
                default => 'bi-clock-history',
            },
            'status_layanan' => $careType ?: '-',
            'jenis_layanan' => $careType === 'ranap'
                ? 'Rawat Inap'
                : 'Rawat Jalan',
            'layanan_tone' => $careType === 'ranap' ? 'ranap' : 'ralan',
            'dokter_perujuk' => trim(
                (string) ($request->nm_dokter ?? '')
            ) ?: '-',
            'poli' => trim((string) ($request->nm_poli ?? '')) ?: '-',
            'diagnosa_klinis' => trim(
                (string) ($request->diagnosa_klinis ?? '')
            ) ?: '-',
            'informasi_tambahan' => trim(
                (string) ($request->informasi_tambahan ?? '')
            ) ?: '-',
            'jumlah_pemeriksaan' => max(
                count($requestedItems),
                (int) ($request->jumlah_pemeriksaan ?? 0)
            ),
            'jumlah_hasil' => (int) ($request->jumlah_hasil ?? 0),
            'jumlah_gambar' => (int) ($request->jumlah_gambar ?? 0),
            'hasil_tersedia' => $resultAt !== null,
            'pemeriksaan_diminta' => $requestedItems,
        ];
    }

    /**
     * @param  Collection<int, object>  $examinations
     * @return array<int, array<string, string>>
     */
    private function formatExaminations(Collection $examinations): array
    {
        return $examinations
            ->map(function (object $item): array {
                $code = trim((string) ($item->kd_jenis_prw ?? ''));

                return [
                    'kode' => $code,
                    'nama' => trim(
                        (string) ($item->nm_perawatan ?? '')
                    ) ?: $code,
                    'status_bayar' => trim(
                        (string) ($item->stts_bayar ?? '')
                    ) ?: '-',
                ];
            })
            ->filter(fn (array $item): bool => $item['kode'] !== '')
            ->unique('kode')
            ->values()
            ->all();
    }

    /**
     * Prefer the exact result timestamp, then the result date, then the
     * examination date. Older Khanza records sometimes only share no_rawat.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function rowsForRequest(Collection $rows, object $request): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $resultDate = $this->dateValue(
            $request->tgl_hasil ?? null
        )?->toDateString();
        $resultTime = $this->rawTime($request->jam_hasil ?? null);

        if ($resultDate !== null && $resultTime !== null) {
            $exactRows = $rows->filter(
                fn (object $row): bool => trim(
                    (string) ($row->tgl_periksa ?? '')
                ) === $resultDate
                    && $this->rawTime($row->jam ?? null) === $resultTime
            );

            if ($exactRows->isNotEmpty()) {
                return $exactRows->values();
            }
        }

        if ($resultDate !== null) {
            $sameDateRows = $rows->filter(
                fn (object $row): bool => trim(
                    (string) ($row->tgl_periksa ?? '')
                ) === $resultDate
            );

            if ($sameDateRows->isNotEmpty()) {
                return $sameDateRows->values();
            }
        }

        $examinationDate = $this->dateValue(
            $request->tgl_sampel ?? null
        )?->toDateString();

        if ($examinationDate !== null) {
            $examinationRows = $rows->filter(
                fn (object $row): bool => trim(
                    (string) ($row->tgl_periksa ?? '')
                ) === $examinationDate
            );

            if ($examinationRows->isNotEmpty()) {
                return $examinationRows->values();
            }
        }

        return $rows->values();
    }

    private function isSafeImagePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0")) {
            return false;
        }

        if (
            str_contains($path, '../')
            || str_starts_with($path, '//')
            || preg_match('/^[a-z][a-z0-9+.-]*:/i', $path) === 1
        ) {
            return false;
        }

        return true;
    }

    private function nullableText(?string $value): ?string
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

    private function rawTime(mixed $time): ?string
    {
        $value = trim((string) $time);

        if ($value === '' || $value === '00:00:00') {
            return null;
        }

        return substr($value, 0, 8);
    }

    private function timeValue(mixed $time): string
    {
        $value = trim((string) $time);

        return $value !== '' && $value !== '00:00:00'
            ? substr($value, 0, 5)
            : '-';
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
