<?php

namespace App\Services\epasien\menu\Surat;

use App\Models\User;
use App\Repositories\epasien\bridging\RujukanRepository;
use App\Repositories\epasien\menu\Surat\SuratRujukanRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class SuratRujukanService
{
    public function __construct(
        private readonly SuratRujukanRepository $suratRujukanRepository,
        private readonly RujukanRepository $rujukanRepository
    ) {}

    public function patientForUser(User $user): ?object
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return null;
        }

        return $this->suratRujukanRepository->findPatient(
            $medicalRecordNumber
        );
    }

    public function generalOutgoingForUser(
        User $user,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 8
    ): LengthAwarePaginator {
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $perPage = max(4, min($perPage, 20));

        if ($medicalRecordNumber === '') {
            return $this->emptyPaginator($perPage);
        }

        $referrals = $this->suratRujukanRepository
            ->paginateGeneralOutgoingReferrals(
                $medicalRecordNumber,
                $startDate,
                $endDate,
                $perPage
            );
        $referrals->setCollection(
            $referrals->getCollection()->map(
                fn (object $referral): array => $this->formatGeneralOutgoing(
                    $referral
                )
            )
        );

        return $referrals;
    }

    /**
     * Search both PCare and hospital referral sources for the signed-in patient.
     *
     * @return array<string, mixed>
     */
    public function incomingBpjsForPatient(object $patient): array
    {
        $cardNumber = $this->normalizedCardNumber(
            $patient->no_peserta ?? null
        );

        if ($cardNumber === '') {
            return $this->unavailableResult();
        }

        $responses = [
            'pcare' => $this->rujukanRepository
                ->findPcareByCardNumber($cardNumber),
            'rumah_sakit' => $this->rujukanRepository
                ->findHospitalByCardNumber($cardNumber),
        ];
        $sourceLabels = [
            'pcare' => 'PCare',
            'rumah_sakit' => 'Rumah Sakit',
        ];
        $sources = [];
        $referrals = [];
        $failedSources = 0;

        foreach ($responses as $source => $response) {
            $metadata = $this->metadata($response);
            $sourceReferrals = $this->incomingReferrals($response);
            $isTechnicalFailure = ! $this->isUsableMetadata($metadata);

            if ($isTechnicalFailure) {
                $failedSources++;
            }

            $formatted = array_map(
                fn (array $referral): array => $this->formatIncomingBpjs(
                    $referral,
                    $source,
                    $sourceLabels[$source]
                ),
                $sourceReferrals
            );
            $referrals = array_merge($referrals, $formatted);
            $sources[$source] = [
                'label' => $sourceLabels[$source],
                'state' => $isTechnicalFailure
                    ? 'error'
                    : ($formatted === [] ? 'empty' : 'success'),
                'count' => count($formatted),
                'meta_data' => $metadata,
            ];
        }

        $referrals = collect($referrals)
            ->unique(
                fn (array $referral): string => $referral['no_rujukan'] !== ''
                    ? $referral['source'].'|'.$referral['no_rujukan']
                    : $referral['id']
            )
            ->sortByDesc('tanggal_iso')
            ->values()
            ->all();
        $partial = $failedSources > 0 && $failedSources < count($responses);

        if ($referrals !== []) {
            $metadata = [
                'code' => '200',
                'message' => $partial
                    ? 'Rujukan ditemukan, tetapi salah satu sumber BPJS belum dapat diakses.'
                    : 'Sukses',
            ];
        } elseif ($failedSources < count($responses)) {
            $metadata = [
                'code' => '204',
                'message' => $partial
                    ? 'Rujukan belum ditemukan dan salah satu sumber BPJS belum dapat diakses.'
                    : 'Rujukan BPJS tidak ditemukan.',
            ];
        } else {
            $metadata = $this->firstFailureMetadata($sources);
        }

        return [
            'available' => true,
            'masked_card_number' => $this->maskCardNumber($cardNumber),
            'partial' => $partial,
            'meta_data' => $metadata,
            'sources' => $sources,
            'rujukan' => $referrals,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function bpjsOutgoingForPatient(
        object $patient,
        string $startDate,
        string $endDate
    ): array {
        $cardNumber = $this->normalizedCardNumber(
            $patient->no_peserta ?? null
        );

        if ($cardNumber === '') {
            return $this->unavailableResult();
        }

        $response = $this->rujukanRepository
            ->listOutgoingHospitalReferrals($startDate, $endDate);
        $metadata = $this->metadata($response);
        $referrals = collect($this->outgoingReferrals($response))
            ->filter(
                fn (array $referral): bool => $this->normalizedCardNumber(
                    $referral['noKartu'] ?? null
                ) === $cardNumber
            )
            ->map(
                fn (array $referral): array => $this->formatBpjsOutgoing(
                    $referral
                )
            )
            ->unique(
                fn (array $referral): string => $referral['no_rujukan'] !== ''
                    ? $referral['no_rujukan']
                    : $referral['id']
            )
            ->sortByDesc('tanggal_iso')
            ->values()
            ->all();

        if ($metadata['code'] === '200' && $referrals === []) {
            $metadata = [
                'code' => '204',
                'message' => 'Rujukan keluar BPJS tidak ditemukan pada rentang tanggal ini.',
            ];
        }

        return [
            'available' => true,
            'masked_card_number' => $this->maskCardNumber($cardNumber),
            'periode' => [
                'tanggal_mulai' => $startDate,
                'tanggal_akhir' => $endDate,
                'label' => $this->dateRangeLabel($startDate, $endDate),
            ],
            'meta_data' => $metadata,
            'rujukan' => $referrals,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatGeneralOutgoing(object $referral): array
    {
        $date = $this->dateParts(
            (string) ($referral->tgl_rujuk ?? ''),
            (string) ($referral->jam ?? '')
        );

        return [
            'id' => hash('sha256', implode('|', [
                (string) ($referral->no_rujuk ?? ''),
                (string) ($referral->no_rawat ?? ''),
                (string) ($referral->tgl_rujuk ?? ''),
            ])),
            'no_rujukan' => $this->text($referral->no_rujuk ?? null),
            'no_rawat' => $this->text($referral->no_rawat ?? null),
            'tujuan' => $this->text($referral->rujuk_ke ?? null),
            'tanggal' => $date,
            'diagnosa' => $this->text(
                $referral->keterangan_diagnosa ?? null
            ),
            'kode_dokter' => $this->text($referral->kd_dokter ?? null),
            'nama_dokter' => $this->text($referral->nm_dokter ?? null),
            'kategori' => $this->text($referral->kat_rujuk ?? null),
            'ambulans' => $this->text($referral->ambulance ?? null),
            'keterangan' => $this->text($referral->keterangan ?? null),
            'terapi' => $this->text($referral->terapi ?? null),
            'indikasi' => $this->text($referral->indikasi ?? null),
            'kode_penjamin' => $this->text($referral->kd_pj ?? null),
            'penjamin' => $this->text($referral->png_jawab ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $referral
     * @return array<string, mixed>
     */
    private function formatIncomingBpjs(
        array $referral,
        string $source,
        string $sourceLabel
    ): array {
        $diagnosis = $this->nestedArray($referral, 'diagnosa');
        $service = $this->nestedArray($referral, 'pelayanan');
        $participant = $this->nestedArray($referral, 'peserta');
        $participantStatus = $this->nestedArray(
            $participant,
            'statusPeserta'
        );
        $referredClinic = $this->nestedArray($referral, 'poliRujukan');
        $referringProvider = $this->nestedArray($referral, 'provPerujuk');
        $number = $this->value($referral, 'noKunjungan');
        $date = $this->value($referral, 'tglKunjungan');

        return [
            'id' => hash('sha256', $source.'|'.$number.'|'.$date),
            'source' => $source,
            'source_label' => $sourceLabel,
            'no_rujukan' => $number,
            'tanggal' => $this->dateParts($date),
            'tanggal_iso' => $this->dateIso($date),
            'keluhan' => $this->value($referral, 'keluhan'),
            'diagnosa' => [
                'kode' => $this->value($diagnosis, 'kode'),
                'nama' => $this->value($diagnosis, 'nama'),
            ],
            'pelayanan' => [
                'kode' => $this->value($service, 'kode'),
                'nama' => $this->value($service, 'nama'),
            ],
            'poli_tujuan' => [
                'kode' => $this->value($referredClinic, 'kode'),
                'nama' => $this->value($referredClinic, 'nama'),
            ],
            'perujuk' => [
                'kode' => $this->value($referringProvider, 'kode'),
                'nama' => $this->value($referringProvider, 'nama'),
            ],
            'peserta' => [
                'nama' => $this->value($participant, 'nama'),
                'status' => $this->value(
                    $participantStatus,
                    'keterangan'
                ),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $referral
     * @return array<string, mixed>
     */
    private function formatBpjsOutgoing(array $referral): array
    {
        $number = $this->value($referral, 'noRujukan');
        $date = $this->value($referral, 'tglRujukan');
        $service = $this->value($referral, 'jnsPelayanan');

        return [
            'id' => hash('sha256', $number.'|'.$date),
            'no_rujukan' => $number,
            'tanggal' => $this->dateParts($date),
            'tanggal_iso' => $this->dateIso($date),
            'jenis_pelayanan' => $service,
            'jenis_pelayanan_label' => match ($service) {
                '1' => 'Rawat Inap',
                '2' => 'Rawat Jalan',
                default => 'Pelayanan BPJS',
            },
            'no_sep' => $this->value($referral, 'noSep'),
            'nama' => $this->value($referral, 'nama'),
            'provider_tujuan' => [
                'kode' => $this->value($referral, 'ppkDirujuk'),
                'nama' => $this->value($referral, 'namaPpkDirujuk'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private function incomingReferrals(array $response): array
    {
        $responseData = is_array($response['response'] ?? null)
            ? $response['response']
            : [];

        return $this->listOfArrays($responseData['rujukan'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private function outgoingReferrals(array $response): array
    {
        $responseData = is_array($response['response'] ?? null)
            ? $response['response']
            : [];

        return $this->listOfArrays($responseData['list'] ?? null);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listOfArrays(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        if (! array_is_list($value)) {
            return [$value];
        }

        return array_values(array_filter(
            $value,
            fn (mixed $item): bool => is_array($item) && $item !== []
        ));
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array{code: string, message: string}
     */
    private function metadata(array $response): array
    {
        $metadata = $response['metaData'] ?? $response['metadata'] ?? [];

        return [
            'code' => trim((string) ($metadata['code'] ?? '500')),
            'message' => trim((string) (
                $metadata['message'] ?? 'Respons VClaim tidak valid.'
            )),
        ];
    }

    /**
     * @param  array{code: string, message: string}  $metadata
     */
    private function isUsableMetadata(array $metadata): bool
    {
        return in_array($metadata['code'], ['200', '201', '204'], true);
    }

    /**
     * @param  array<string, array<string, mixed>>  $sources
     * @return array{code: string, message: string}
     */
    private function firstFailureMetadata(array $sources): array
    {
        foreach ($sources as $source) {
            $metadata = $source['meta_data'] ?? null;

            if (is_array($metadata) && ! $this->isUsableMetadata($metadata)) {
                return [
                    'code' => (string) ($metadata['code'] ?? '502'),
                    'message' => (string) (
                        $metadata['message']
                        ?? 'Layanan rujukan BPJS belum dapat diakses.'
                    ),
                ];
            }
        }

        return [
            'code' => '502',
            'message' => 'Layanan rujukan BPJS belum dapat diakses.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailableResult(): array
    {
        return [
            'available' => false,
            'masked_card_number' => '',
            'partial' => false,
            'meta_data' => [
                'code' => '422',
                'message' => 'Nomor kartu BPJS belum tersimpan pada data pasien.',
            ],
            'sources' => [],
            'rujukan' => [],
        ];
    }

    /**
     * @return array{raw: string, iso_date: string, date_label: string, time_label: string}
     */
    private function dateParts(string $date, string $time = ''): array
    {
        $date = trim($date);
        $time = trim($time);

        if ($date === '' || str_starts_with($date, '0000-00-00')) {
            return [
                'raw' => '',
                'iso_date' => '',
                'date_label' => 'Tanggal belum tersedia',
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
                'time_label' => $time,
            ];
        }

        return [
            'raw' => $date,
            'iso_date' => $value->toDateString(),
            'date_label' => $value->locale('id')->translatedFormat('d F Y'),
            'time_label' => $time !== '' && $time !== '00:00:00'
                ? substr($time, 0, 5).' WIB'
                : '',
        ];
    }

    private function dateIso(string $date): string
    {
        return $this->dateParts($date)['iso_date'];
    }

    private function dateRangeLabel(string $startDate, string $endDate): string
    {
        $start = $this->dateParts($startDate)['date_label'];
        $end = $this->dateParts($endDate)['date_label'];

        return $startDate === $endDate ? $start : $start.' – '.$end;
    }

    private function medicalRecordNumber(User $user): string
    {
        return trim((string) $user->username);
    }

    private function normalizedCardNumber(mixed $value): string
    {
        return preg_replace('/\D+/', '', trim((string) $value)) ?? '';
    }

    private function maskCardNumber(string $cardNumber): string
    {
        $visibleDigits = min(4, strlen($cardNumber));

        return str_repeat('•', max(0, strlen($cardNumber) - $visibleDigits))
            .substr($cardNumber, -$visibleDigits);
    }

    private function text(mixed $value): string
    {
        return trim((string) $value);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function value(array $data, string $key): string
    {
        return trim((string) ($data[$key] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function nestedArray(array $data, string $key): array
    {
        return is_array($data[$key] ?? null) ? $data[$key] : [];
    }

    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            LengthAwarePaginator::resolveCurrentPage('umum_page'),
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'umum_page',
            ]
        );
    }
}
