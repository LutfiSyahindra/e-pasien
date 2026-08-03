<?php

namespace App\Services\epasien\bridging;

use App\Repositories\epasien\bridging\RencanaKontrolRepository;
use App\Repositories\epasien\bridging\RujukanRepository;
use Illuminate\Support\Carbon;

class RencanaKontrolService
{
    public function __construct(
        private readonly RencanaKontrolRepository $rencanaKontrolRepository,
        private readonly RujukanRepository $rujukanRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function listByCardNumber(
        string $plannedDate,
        string $cardNumber,
        int $filter = 2,
        bool $includeReferralFallback = true,
        int $monthsBefore = 1,
        int $monthsAfter = 0
    ): array {
        $registrationDate = Carbon::createFromFormat('Y-m-d', $plannedDate)->startOfDay();
        $cardNumber = trim($cardNumber);
        $monthsBefore = max(0, min($monthsBefore, 12));
        $monthsAfter = max(0, min($monthsAfter, 12));
        $searchDates = collect(range(-$monthsBefore, $monthsAfter))
            ->map(
                fn (int $offset): Carbon => $registrationDate
                    ->copy()
                    ->addMonthsNoOverflow($offset)
                    ->startOfMonth()
            )
            ->values()
            ->all();
        $periods = [];
        $controlLetters = [];
        $successfulResponseFound = false;
        $lastMetadata = [
            'code' => '201',
            'message' => 'Data tidak ditemukan.',
        ];
        $errorMetadata = null;

        foreach ($searchDates as $searchDate) {
            $response = $this->rencanaKontrolRepository->listByCardNumber(
                $searchDate->format('m'),
                $searchDate->format('Y'),
                $cardNumber,
                $filter
            );
            $metadata = $this->metadata($response);
            $responseControlLetters = $this->controlLetters($response);
            $lastMetadata = $metadata;

            if ($metadata['code'] === '200') {
                $successfulResponseFound = true;
            } elseif (
                ! in_array($metadata['code'], ['201', '204'], true)
                && $errorMetadata === null
            ) {
                $errorMetadata = $metadata;
            }

            $periods[] = [
                'bulan' => $searchDate->format('m'),
                'tahun' => $searchDate->format('Y'),
                'label' => $this->periodLabel($searchDate),
                'meta_data' => $metadata,
            ];
            $controlLetters = array_merge($controlLetters, $responseControlLetters);

            if (! $this->allowsReferralFallback($metadata)) {
                break;
            }
        }

        $formattedControlLetters = collect($controlLetters)
            ->filter(fn (mixed $controlLetter): bool => is_array($controlLetter))
            ->map(fn (array $controlLetter): array => $this->formatControlLetter($controlLetter))
            ->unique(fn (array $controlLetter): string => $controlLetter['no_surat_kontrol'] !== ''
                ? $controlLetter['no_surat_kontrol']
                : md5(json_encode($controlLetter)))
            ->values()
            ->all();

        $controlLetterMetadata = $errorMetadata
            ?? ($successfulResponseFound
                ? ['code' => '200', 'message' => 'Sukses']
                : $lastMetadata);
        $result = [
            'meta_data' => $controlLetterMetadata,
            'periode' => [
                'bulan_awal' => $searchDates[0]->format('m'),
                'tahun_awal' => $searchDates[0]->format('Y'),
                'bulan_akhir' => $searchDates[array_key_last($searchDates)]->format('m'),
                'tahun_akhir' => $searchDates[array_key_last($searchDates)]->format('Y'),
                'label' => $this->periodRangeLabel($searchDates),
                'jumlah_bulan' => count($searchDates),
            ],
            'periode_pencarian' => $periods,
            'filter' => $filter,
            'surat_kontrol' => $formattedControlLetters,
            'rujukan' => null,
            'daftar_rujukan' => [],
            'sumber_dokumen' => $formattedControlLetters !== []
                ? 'surat_kontrol'
                : null,
            'pencarian_rujukan' => [
                'pcare' => null,
                'rumah_sakit' => null,
            ],
        ];

        if (
            $formattedControlLetters !== []
            || ! $includeReferralFallback
            || ! $this->allowsReferralFallback($controlLetterMetadata)
        ) {
            return $result;
        }

        return $this->findReferral($result, $cardNumber);
    }

    /**
     * @return array<string, mixed>
     */
    public function detailByControlLetterNumber(string $controlLetterNumber): array
    {
        $response = $this->rencanaKontrolRepository->findByControlLetterNumber(
            trim($controlLetterNumber)
        );
        $metadata = $this->metadata($response);
        $responseData = $response['response'] ?? null;
        $controlLetter = is_array($responseData) && $responseData !== []
            ? $responseData
            : null;

        return [
            'meta_data' => $metadata,
            'surat_kontrol' => $controlLetter !== null
                ? $this->formatControlLetterDetail($controlLetter)
                : null,
        ];
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
            'message' => trim((string) ($metadata['message'] ?? 'Respons VClaim tidak valid.')),
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private function controlLetters(array $response): array
    {
        $responseData = is_array($response['response'] ?? null)
            ? $response['response']
            : [];
        $controlLetters = $responseData['list']
            ?? $responseData['suratKontrol']
            ?? [];

        return is_array($controlLetters) ? $controlLetters : [];
    }

    private function periodLabel(Carbon $date): string
    {
        return $date->locale('id')->translatedFormat('F Y');
    }

    /**
     * @param  array<int, Carbon>  $dates
     */
    private function periodRangeLabel(array $dates): string
    {
        $first = $dates[0];
        $last = $dates[array_key_last($dates)];

        if (count($dates) === 1) {
            return $this->periodLabel($first);
        }

        if (count($dates) === 2) {
            return $this->periodLabel($first).' dan '.$this->periodLabel($last);
        }

        return $this->periodLabel($first).' sampai '.$this->periodLabel($last);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function findReferral(array $result, string $cardNumber): array
    {
        $pcareResponse = $this->rujukanRepository->findPcareByCardNumber($cardNumber);
        $pcareMetadata = $this->metadata($pcareResponse);
        $pcareReferrals = $this->referrals($pcareResponse);

        $result['pencarian_rujukan']['pcare'] = $pcareMetadata;

        if ($pcareMetadata['code'] === '200' && $pcareReferrals !== []) {
            $formattedReferrals = array_map(
                fn (array $referral): array => $this->formatReferral($referral),
                $pcareReferrals
            );
            $result['meta_data'] = $pcareMetadata;
            $result['rujukan'] = $formattedReferrals[0];
            $result['daftar_rujukan'] = $formattedReferrals;
            $result['sumber_dokumen'] = 'rujukan_pcare';

            return $result;
        }

        if (! $this->allowsReferralFallback($pcareMetadata)) {
            $result['meta_data'] = $pcareMetadata;

            return $result;
        }

        $hospitalResponse = $this->rujukanRepository->findHospitalByCardNumber($cardNumber);
        $hospitalMetadata = $this->metadata($hospitalResponse);
        $hospitalReferrals = $this->referrals($hospitalResponse);

        $result['pencarian_rujukan']['rumah_sakit'] = $hospitalMetadata;
        $result['meta_data'] = $hospitalMetadata;

        if ($hospitalMetadata['code'] === '200' && $hospitalReferrals !== []) {
            $formattedReferrals = array_map(
                fn (array $referral): array => $this->formatReferral($referral),
                $hospitalReferrals
            );
            $result['rujukan'] = $formattedReferrals[0];
            $result['daftar_rujukan'] = $formattedReferrals;
            $result['sumber_dokumen'] = 'rujukan_rumah_sakit';
        } elseif ($hospitalMetadata['code'] === '200') {
            $result['meta_data'] = [
                'code' => '204',
                'message' => 'Surat kontrol dan rujukan tidak ditemukan.',
            ];
        }

        return $result;
    }

    /**
     * A successful response without referral data is also considered not found.
     *
     * @param  array{code: string, message: string}  $metadata
     */
    private function allowsReferralFallback(array $metadata): bool
    {
        return in_array($metadata['code'], ['200', '201', '204'], true);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private function referrals(array $response): array
    {
        $responseData = $response['response'] ?? null;
        $referrals = is_array($responseData)
            ? ($responseData['rujukan'] ?? null)
            : null;

        if (! is_array($referrals) || $referrals === []) {
            return [];
        }

        if (! array_is_list($referrals)) {
            return [$referrals];
        }

        return array_values(array_filter(
            $referrals,
            fn (mixed $referral): bool => is_array($referral) && $referral !== []
        ));
    }

    /**
     * @param  array<string, mixed>  $controlLetter
     * @return array<string, string>
     */
    private function formatControlLetter(array $controlLetter): array
    {
        return [
            'no_surat_kontrol' => $this->value($controlLetter, 'noSuratKontrol'),
            'jenis_pelayanan' => $this->value($controlLetter, 'jnsPelayanan'),
            'jenis_kontrol' => $this->value($controlLetter, 'jnsKontrol'),
            'nama_jenis_kontrol' => $this->value($controlLetter, 'namaJnsKontrol'),
            'tgl_rencana_kontrol' => $this->value($controlLetter, 'tglRencanaKontrol'),
            'tgl_terbit_kontrol' => $this->value($controlLetter, 'tglTerbitKontrol'),
            'no_sep_asal_kontrol' => $this->value($controlLetter, 'noSepAsalKontrol'),
            'poli_asal' => $this->value($controlLetter, 'poliAsal'),
            'nama_poli_asal' => $this->value($controlLetter, 'namaPoliAsal'),
            'poli_tujuan' => $this->value($controlLetter, 'poliTujuan'),
            'nama_poli_tujuan' => $this->value($controlLetter, 'namaPoliTujuan'),
            'tgl_sep' => $this->value($controlLetter, 'tglSEP'),
            'kode_dokter' => $this->value($controlLetter, 'kodeDokter'),
            'nama_dokter' => $this->value($controlLetter, 'namaDokter'),
            'no_kartu' => $this->value($controlLetter, 'noKartu'),
            'nama' => $this->value($controlLetter, 'nama'),
            'terbit_sep' => $this->value($controlLetter, 'terbitSEP'),
        ];
    }

    /**
     * @param  array<string, mixed>  $controlLetter
     * @return array<string, mixed>
     */
    private function formatControlLetterDetail(array $controlLetter): array
    {
        $sepData = $controlLetter['sep'] ?? null;
        $sep = is_array($sepData) && $sepData !== []
            ? $sepData
            : null;
        $participant = $sep !== null && is_array($sep['peserta'] ?? null)
            ? $sep['peserta']
            : null;
        $generalProvider = $sep !== null && is_array($sep['provUmum'] ?? null)
            ? $sep['provUmum']
            : null;
        $referringProvider = $sep !== null && is_array($sep['provPerujuk'] ?? null)
            ? $sep['provPerujuk']
            : null;
        $prbData = $controlLetter['formPRB'] ?? null;
        $prbForm = is_array($prbData) && $prbData !== []
            ? $prbData
            : null;

        return [
            'no_surat_kontrol' => $this->value($controlLetter, 'noSuratKontrol'),
            'tgl_rencana_kontrol' => $this->value($controlLetter, 'tglRencanaKontrol'),
            'tgl_terbit' => $this->value($controlLetter, 'tglTerbit'),
            'jenis_kontrol' => $this->value($controlLetter, 'jnsKontrol'),
            'nama_jenis_kontrol' => $this->value($controlLetter, 'namaJnsKontrol'),
            'poli_tujuan' => $this->value($controlLetter, 'poliTujuan'),
            'nama_poli_tujuan' => $this->value($controlLetter, 'namaPoliTujuan'),
            'kode_dokter' => $this->value($controlLetter, 'kodeDokter'),
            'nama_dokter' => $this->value($controlLetter, 'namaDokter'),
            'flag_kontrol' => $this->value($controlLetter, 'flagKontrol'),
            'kode_dokter_pembuat' => $this->value($controlLetter, 'kodeDokterPembuat'),
            'nama_dokter_pembuat' => $this->value($controlLetter, 'namaDokterPembuat'),
            'sep' => $sep !== null ? [
                'no_sep' => $this->value($sep, 'noSep'),
                'tgl_sep' => $this->value($sep, 'tglSep'),
                'jenis_pelayanan' => $this->value($sep, 'jnsPelayanan'),
                'poli' => $this->value($sep, 'poli'),
                'diagnosa' => $this->value($sep, 'diagnosa'),
                'peserta' => $participant !== null ? [
                    'no_kartu' => $this->value($participant, 'noKartu'),
                    'nama' => $this->value($participant, 'nama'),
                    'tgl_lahir' => $this->value($participant, 'tglLahir'),
                    'kelamin' => $this->value($participant, 'kelamin'),
                    'hak_kelas' => $this->value($participant, 'hakKelas'),
                ] : null,
                'provider_umum' => $generalProvider !== null ? [
                    'kode_provider' => $this->value($generalProvider, 'kdProvider'),
                    'nama_provider' => $this->value($generalProvider, 'nmProvider'),
                ] : null,
                'provider_perujuk' => $referringProvider !== null ? [
                    'kode_provider' => $this->value($referringProvider, 'kdProviderPerujuk'),
                    'nama_provider' => $this->value($referringProvider, 'nmProviderPerujuk'),
                    'asal_rujukan' => $this->value($referringProvider, 'asalRujukan'),
                    'no_rujukan' => $this->value($referringProvider, 'noRujukan'),
                    'tgl_rujukan' => $this->value($referringProvider, 'tglRujukan'),
                ] : null,
            ] : null,
            'form_prb' => $prbForm !== null ? [
                'kode_status_prb' => $this->nullableValue($prbForm, 'kdStatusPRB'),
                'data' => is_array($prbForm['data'] ?? null)
                    ? $prbForm['data']
                    : null,
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $referral
     * @return array<string, mixed>
     */
    private function formatReferral(array $referral): array
    {
        $diagnosis = is_array($referral['diagnosa'] ?? null)
            ? $referral['diagnosa']
            : [];
        $service = is_array($referral['pelayanan'] ?? null)
            ? $referral['pelayanan']
            : [];
        $participant = is_array($referral['peserta'] ?? null)
            ? $referral['peserta']
            : [];
        $participantClass = is_array($participant['hakKelas'] ?? null)
            ? $participant['hakKelas']
            : [];
        $participantType = is_array($participant['jenisPeserta'] ?? null)
            ? $participant['jenisPeserta']
            : [];
        $participantStatus = is_array($participant['statusPeserta'] ?? null)
            ? $participant['statusPeserta']
            : [];
        $participantMr = is_array($participant['mr'] ?? null)
            ? $participant['mr']
            : [];
        $generalProvider = is_array($participant['provUmum'] ?? null)
            ? $participant['provUmum']
            : [];
        $referredClinic = is_array($referral['poliRujukan'] ?? null)
            ? $referral['poliRujukan']
            : [];
        $referringProvider = is_array($referral['provPerujuk'] ?? null)
            ? $referral['provPerujuk']
            : [];

        return [
            'no_rujukan' => $this->value($referral, 'noKunjungan'),
            'tgl_kunjungan' => $this->value($referral, 'tglKunjungan'),
            'keluhan' => $this->value($referral, 'keluhan'),
            'diagnosa' => [
                'kode' => $this->value($diagnosis, 'kode'),
                'nama' => $this->value($diagnosis, 'nama'),
            ],
            'pelayanan' => [
                'kode' => $this->value($service, 'kode'),
                'nama' => $this->value($service, 'nama'),
            ],
            'poli_rujukan' => [
                'kode' => $this->value($referredClinic, 'kode'),
                'nama' => $this->value($referredClinic, 'nama'),
            ],
            'provider_perujuk' => [
                'kode' => $this->value($referringProvider, 'kode'),
                'nama' => $this->value($referringProvider, 'nama'),
            ],
            'peserta' => [
                'no_kartu' => $this->value($participant, 'noKartu'),
                'nik' => $this->value($participant, 'nik'),
                'nama' => $this->value($participant, 'nama'),
                'jenis_kelamin' => $this->value($participant, 'sex'),
                'tgl_lahir' => $this->value($participant, 'tglLahir'),
                'no_mr' => $this->value($participantMr, 'noMR'),
                'no_telepon' => $this->value($participantMr, 'noTelepon'),
                'hak_kelas' => [
                    'kode' => $this->value($participantClass, 'kode'),
                    'nama' => $this->value($participantClass, 'keterangan'),
                ],
                'jenis_peserta' => [
                    'kode' => $this->value($participantType, 'kode'),
                    'nama' => $this->value($participantType, 'keterangan'),
                ],
                'status' => [
                    'kode' => $this->value($participantStatus, 'kode'),
                    'nama' => $this->value($participantStatus, 'keterangan'),
                ],
                'provider_umum' => [
                    'kode' => $this->value($generalProvider, 'kdProvider'),
                    'nama' => $this->value($generalProvider, 'nmProvider'),
                ],
            ],
        ];
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
     */
    private function nullableValue(array $data, string $key): ?string
    {
        if (! isset($data[$key])) {
            return null;
        }

        return trim((string) $data[$key]);
    }
}
