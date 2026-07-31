<?php

namespace App\Services\epasien\menu\Surat;

use App\Models\User;
use App\Repositories\epasien\menu\Surat\SuratKontrolRepository;
use App\Services\epasien\bridging\RencanaKontrolService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class SuratKontrolService
{
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
        private readonly SuratKontrolRepository $suratKontrolRepository,
        private readonly RencanaKontrolService $rencanaKontrolService
    ) {}

    public function patientForUser(User $user): ?object
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return null;
        }

        return $this->suratKontrolRepository->findPatient($medicalRecordNumber);
    }

    public function generalLettersForUser(
        User $user,
        ?string $status = null,
        int $perPage = 8
    ): LengthAwarePaginator {
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $perPage = max(4, min($perPage, 20));

        if ($medicalRecordNumber === '') {
            return $this->emptyPaginator($perPage);
        }

        $letters = $this->suratKontrolRepository->paginateGeneralControlLetters(
            $medicalRecordNumber,
            $status,
            $perPage
        );
        $letters->setCollection(
            $letters->getCollection()->map(
                fn (object $letter): array => $this->formatGeneralLetter($letter)
            )
        );

        return $letters;
    }

    /**
     * @return array{all: int, waiting: int, examined: int, cancelled: int}
     */
    public function generalCountsForUser(User $user): array
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return $this->emptyCounts();
        }

        return $this->suratKontrolRepository
            ->generalControlLetterCounts($medicalRecordNumber);
    }

    /**
     * @return array<string, mixed>
     */
    public function bpjsLettersForPatient(
        object $patient,
        string $period,
        bool $automaticSearch = false
    ): array {
        $cardNumber = preg_replace('/\D+/', '', trim((string) ($patient->no_peserta ?? ''))) ?? '';

        if ($cardNumber === '') {
            return [
                'available' => false,
                'masked_card_number' => '',
                'meta_data' => [
                    'code' => '422',
                    'message' => 'Nomor kartu BPJS belum tersimpan pada data pasien.',
                ],
                'periode' => null,
                'surat_kontrol' => [],
            ];
        }

        $plannedDate = Carbon::createFromFormat('Y-m-d', $period.'-01')
            ->startOfMonth()
            ->toDateString();
        $result = $automaticSearch
            ? $this->rencanaKontrolService->listByCardNumber(
                $plannedDate,
                $cardNumber,
                2,
                false,
                3,
                3
            )
            : $this->rencanaKontrolService->listByCardNumber(
                $plannedDate,
                $cardNumber,
                2,
                false
            );

        return [
            'available' => true,
            'masked_card_number' => $this->maskCardNumber($cardNumber),
            'search_mode' => $automaticSearch ? 'automatic' : 'period',
            'meta_data' => $result['meta_data'],
            'periode' => $result['periode'],
            'surat_kontrol' => $result['surat_kontrol'],
        ];
    }

    /**
     * @return array{all: int, waiting: int, examined: int, cancelled: int}
     */
    public function emptyCounts(): array
    {
        return [
            'all' => 0,
            'waiting' => 0,
            'examined' => 0,
            'cancelled' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatGeneralLetter(object $letter): array
    {
        $arrival = $this->dateParts((string) ($letter->tanggal_datang ?? ''));
        $referral = $this->dateParts((string) ($letter->tanggal_rujukan ?? ''));
        $status = trim((string) ($letter->status ?? ''));

        return [
            'id' => hash('sha256', implode('|', [
                (string) ($letter->tahun ?? ''),
                (string) ($letter->no_rkm_medis ?? ''),
                (string) ($letter->tanggal_datang ?? ''),
                (string) ($letter->tanggal_rujukan ?? ''),
                (string) ($letter->kd_dokter ?? ''),
            ])),
            'tahun' => trim((string) ($letter->tahun ?? '')),
            'no_rkm_medis' => trim((string) ($letter->no_rkm_medis ?? '')),
            'diagnosa' => $this->text($letter->diagnosa ?? null),
            'terapi' => $this->text($letter->terapi ?? null),
            'alasan1' => $this->text($letter->alasan1 ?? null),
            'alasan2' => $this->text($letter->alasan2 ?? null),
            'rtl1' => $this->text($letter->rtl1 ?? null),
            'rtl2' => $this->text($letter->rtl2 ?? null),
            'tanggal_datang' => $arrival,
            'tanggal_rujukan' => $referral,
            'kd_dokter' => trim((string) ($letter->kd_dokter ?? '')),
            'nama_dokter' => $this->text($letter->nm_dokter ?? null),
            'status' => $status !== '' ? $status : 'Belum diketahui',
            'status_tone' => match ($status) {
                'Menunggu' => 'waiting',
                'Sudah Periksa' => 'examined',
                'Batal Periksa' => 'cancelled',
                default => 'neutral',
            },
            'is_upcoming' => $arrival['iso_date'] !== ''
                && $status === 'Menunggu'
                && Carbon::parse($arrival['iso_date'])->startOfDay()
                    ->greaterThanOrEqualTo(today()->startOfDay()),
        ];
    }

    /**
     * @return array{raw: string, iso_date: string, date_label: string, short_label: string, time_label: string}
     */
    private function dateParts(string $date): array
    {
        $date = trim($date);

        if ($date === '' || str_starts_with($date, '0000-00-00')) {
            return [
                'raw' => '',
                'iso_date' => '',
                'date_label' => 'Belum ditentukan',
                'short_label' => '-',
                'time_label' => '',
            ];
        }

        try {
            $value = Carbon::parse($date);
        } catch (\Throwable) {
            return [
                'raw' => $date,
                'iso_date' => '',
                'date_label' => $date,
                'short_label' => $date,
                'time_label' => '',
            ];
        }

        return [
            'raw' => $date,
            'iso_date' => $value->toDateString(),
            'date_label' => $value->format('d').' '
                .self::MONTHS[(int) $value->format('n')].' '
                .$value->format('Y'),
            'short_label' => $value->format('d M Y'),
            'time_label' => $value->format('H:i') !== '00:00'
                ? $value->format('H:i').' WIB'
                : '',
        ];
    }

    private function medicalRecordNumber(User $user): string
    {
        return trim((string) $user->username);
    }

    private function text(mixed $value): string
    {
        return trim((string) $value);
    }

    private function maskCardNumber(string $cardNumber): string
    {
        $visibleDigits = min(4, strlen($cardNumber));

        return str_repeat('•', max(0, strlen($cardNumber) - $visibleDigits))
            .substr($cardNumber, -$visibleDigits);
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
