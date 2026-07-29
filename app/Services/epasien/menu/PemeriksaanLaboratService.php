<?php

namespace App\Services\epasien\menu;

use App\Models\User;
use App\Repositories\epasien\menu\PemeriksaanLaboratRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PemeriksaanLaboratService
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
        private readonly PemeriksaanLaboratRepository $pemeriksaanLaboratRepository
    ) {}

    public function patientForUser(User $user): ?object
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return null;
        }

        return $this->pemeriksaanLaboratRepository->findPatient($medicalRecordNumber);
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

        $requests = $this->pemeriksaanLaboratRepository->paginateRequests(
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
        $requestedExaminations = $this->pemeriksaanLaboratRepository
            ->requestedExaminations($orderNumbers)
            ->groupBy(fn (object $row): string => trim((string) ($row->noorder ?? '')));

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

        return $this->pemeriksaanLaboratRepository
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
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $orderNumber = trim($orderNumber);

        if ($medicalRecordNumber === '' || $orderNumber === '') {
            return null;
        }

        $request = $this->pemeriksaanLaboratRepository
            ->findRequestForPatient($medicalRecordNumber, $orderNumber);

        if ($request === null) {
            return null;
        }

        $requestedExaminations = $this->pemeriksaanLaboratRepository
            ->requestedExaminations([$orderNumber]);
        $resultDate = $this->dateValue($request->tgl_hasil ?? null);
        $resultRows = $resultDate !== null
            ? $this->pemeriksaanLaboratRepository->resultRows(
                $orderNumber,
                trim((string) ($request->no_rawat ?? '')),
                $resultDate->toDateString()
            )
            : collect();
        $formattedRequest = $this->formatRequest($request, $requestedExaminations);
        $actualResultTimes = $resultRows
            ->pluck('jam')
            ->map(fn (mixed $time): string => $this->timeValue($time))
            ->filter(fn (string $time): bool => $time !== '-')
            ->unique()
            ->values()
            ->all();
        $formattedRequest['jam_hasil_aktual'] = $actualResultTimes !== []
            ? implode(', ', $actualResultTimes)
            : $formattedRequest['jam_hasil'];

        return [
            'permintaan' => $formattedRequest,
            'ringkasan' => [
                'jumlah_jenis' => $resultRows
                    ->pluck('kd_jenis_prw')
                    ->filter()
                    ->unique()
                    ->count(),
                'jumlah_parameter' => $resultRows->count(),
                'jumlah_catatan' => $resultRows
                    ->filter(fn (object $row): bool => $this->hasResultNote(
                        $row->keterangan ?? null
                    ))
                    ->count(),
            ],
            'kelompok_hasil' => $this->formatResultGroups($resultRows),
        ];
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
        $sampleAt = $this->dateValue($request->tgl_sampel ?? null);
        $resultAt = $this->dateValue($request->tgl_hasil ?? null);
        $careType = trim((string) ($request->status ?? ''));
        $status = $resultAt !== null
            ? 'selesai'
            : ($sampleAt !== null ? 'proses' : 'menunggu');
        $requestedItems = $requestedExaminations
            ->map(function (object $item): array {
                $code = trim((string) ($item->kd_jenis_prw ?? ''));

                return [
                    'kode' => $code,
                    'nama' => trim((string) ($item->nm_perawatan ?? '')) ?: $code,
                ];
            })
            ->filter(fn (array $item): bool => $item['kode'] !== '')
            ->unique('kode')
            ->values()
            ->all();

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
            'tanggal_sampel' => $sampleAt?->toDateString(),
            'tanggal_sampel_lengkap' => $this->longDate($sampleAt),
            'jam_sampel' => $sampleAt
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
                'proses' => 'Sedang Diproses',
                default => 'Menunggu Sampel',
            },
            'status_icon' => match ($status) {
                'selesai' => 'bi-check-circle',
                'proses' => 'bi-hourglass-split',
                default => 'bi-clock-history',
            },
            'status_layanan' => $careType ?: '-',
            'jenis_layanan' => $careType === 'Ranap' ? 'Rawat Inap' : 'Rawat Jalan',
            'layanan_tone' => $careType === 'Ranap' ? 'ranap' : 'ralan',
            'dokter_perujuk' => trim((string) ($request->nm_dokter ?? '')) ?: '-',
            'poli' => trim((string) ($request->nm_poli ?? '')) ?: '-',
            'diagnosa_klinis' => trim((string) ($request->diagnosa_klinis ?? '')) ?: '-',
            'informasi_tambahan' => trim(
                (string) ($request->informasi_tambahan ?? '')
            ) ?: '-',
            'jumlah_pemeriksaan' => max(
                count($requestedItems),
                (int) ($request->jumlah_pemeriksaan ?? 0)
            ),
            'jumlah_hasil' => (int) ($request->jumlah_hasil ?? 0),
            'hasil_tersedia' => (int) ($request->jumlah_hasil ?? 0) > 0,
            'pemeriksaan_diminta' => $requestedItems,
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function formatResultGroups(Collection $rows): array
    {
        return $rows
            ->groupBy(fn (object $row): string => trim(
                (string) ($row->kd_jenis_prw ?? '')
            ))
            ->map(function (Collection $group, string $code): array {
                $first = $group->first();

                return [
                    'kode' => $code,
                    'nama' => trim((string) ($first->nm_perawatan ?? '')) ?: $code,
                    'jumlah_parameter' => $group->count(),
                    'parameter' => $group->map(function (object $row): array {
                        $note = trim((string) ($row->keterangan ?? ''));

                        return [
                            'id_template' => (string) ($row->id_template ?? ''),
                            'nama' => trim(
                                (string) ($row->nama_parameter ?? '')
                            ) ?: 'Parameter pemeriksaan',
                            'nilai' => trim((string) ($row->nilai ?? '')) ?: '-',
                            'satuan' => trim((string) ($row->satuan ?? '')) ?: '-',
                            'nilai_rujukan' => trim(
                                (string) ($row->nilai_rujukan ?? '')
                            ) ?: '-',
                            'keterangan' => $note ?: '-',
                            'memiliki_catatan' => $this->hasResultNote($note),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function hasResultNote(mixed $note): bool
    {
        $value = mb_strtolower(trim((string) $note));

        return $value !== '' && $value !== '-' && $value !== 'normal';
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

    private function timeValue(mixed $time): string
    {
        $value = trim((string) $time);

        return $value !== '' ? substr($value, 0, 5) : '-';
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
