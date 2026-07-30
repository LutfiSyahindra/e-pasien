<?php

namespace App\Services\epasien\menu;

use App\Models\User;
use App\Repositories\epasien\menu\RiwayatMcuRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RiwayatMcuService
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
        private readonly RiwayatMcuRepository $riwayatMcuRepository
    ) {}

    public function patientForUser(User $user): ?object
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return null;
        }

        return $this->riwayatMcuRepository->findPatient(
            $medicalRecordNumber
        );
    }

    public function assessmentsForUser(
        User $user,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $doctorCode = null,
        ?string $search = null,
        int $perPage = 8
    ): LengthAwarePaginator {
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $perPage = max(4, min($perPage, 20));

        if ($medicalRecordNumber === '') {
            return $this->emptyPaginator($perPage);
        }

        $assessments = $this->riwayatMcuRepository->paginateAssessments(
            $medicalRecordNumber,
            $startDate,
            $endDate,
            $this->nullableText($doctorCode),
            $this->nullableText($search),
            $perPage
        );

        $assessments->setCollection(
            $assessments->getCollection()->map(
                fn (object $assessment): array => $this->formatListItem(
                    $assessment
                )
            )
        );

        return $assessments;
    }

    /**
     * @return Collection<int, array{code: string, name: string}>
     */
    public function doctorsForUser(User $user): Collection
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return collect();
        }

        return $this->riwayatMcuRepository
            ->assessmentDoctors($medicalRecordNumber)
            ->map(fn (object $doctor): array => [
                'code' => trim((string) ($doctor->kd_dokter ?? '')),
                'name' => $this->text($doctor->nm_dokter ?? null),
            ]);
    }

    /**
     * @return array{
     *     all: int,
     *     current_year: int,
     *     latest_at: ?string,
     *     latest_label: string
     * }
     */
    public function summaryForUser(User $user): array
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return $this->emptySummary();
        }

        $summary = $this->riwayatMcuRepository->assessmentSummary(
            $medicalRecordNumber,
            now()->year
        );
        $latestAt = $this->dateTimeValue($summary['latest_at']);

        return [
            ...$summary,
            'latest_label' => $this->longDate($latestAt),
        ];
    }

    /**
     * @return array{
     *     all: int,
     *     current_year: int,
     *     latest_at: ?string,
     *     latest_label: string
     * }
     */
    public function emptySummary(): array
    {
        return [
            'all' => 0,
            'current_year' => 0,
            'latest_at' => null,
            'latest_label' => '-',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detailForUser(
        User $user,
        string $treatmentNumber
    ): ?array {
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $treatmentNumber = trim($treatmentNumber);

        if ($medicalRecordNumber === '' || $treatmentNumber === '') {
            return null;
        }

        $assessment = $this->riwayatMcuRepository
            ->findAssessmentForPatient(
                $medicalRecordNumber,
                $treatmentNumber
            );

        if ($assessment === null) {
            return null;
        }

        return $this->formatDetail($assessment);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatListItem(object $assessment): array
    {
        $assessedAt = $this->dateTimeValue($assessment->tanggal ?? null);

        return [
            'no_rawat' => trim(
                (string) ($assessment->no_rawat ?? '')
            ),
            'tanggal' => $assessedAt?->toDateString(),
            'tanggal_lengkap' => $this->longDate($assessedAt),
            'jam' => $assessedAt?->format('H:i') ?? '-',
            'hari_short' => $assessedAt
                ? substr(self::DAYS[$assessedAt->dayOfWeek], 0, 3)
                : '-',
            'tanggal_angka' => $assessedAt?->format('d') ?? '-',
            'bulan_short' => $assessedAt
                ? substr(
                    self::MONTHS[(int) $assessedAt->format('n')],
                    0,
                    3
                )
                : '-',
            'dokter' => $this->text($assessment->nm_dokter ?? null),
            'poli' => $this->text($assessment->nm_poli ?? null),
            'keadaan' => $this->text($assessment->keadaan ?? null),
            'kesadaran' => $this->text($assessment->kesadaran ?? null),
            'kesimpulan' => $this->text(
                $assessment->kesimpulan ?? null
            ),
            'anjuran' => $this->text($assessment->anjuran ?? null),
            'vitals' => [
                'tekanan_darah' => $this->measurement(
                    $assessment->td ?? null,
                    'mmHg'
                ),
                'nadi' => $this->measurement(
                    $assessment->nadi ?? null,
                    'x/menit'
                ),
                'suhu' => $this->measurement(
                    $assessment->suhu ?? null,
                    '°C'
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDetail(object $assessment): array
    {
        $assessedAt = $this->dateTimeValue($assessment->tanggal ?? null);

        return [
            'meta' => [
                'no_rawat' => trim(
                    (string) ($assessment->no_rawat ?? '')
                ),
                'no_rkm_medis' => trim(
                    (string) ($assessment->no_rkm_medis ?? '')
                ),
                'pasien' => $this->text(
                    $assessment->nm_pasien ?? null
                ),
                'dokter' => $this->text(
                    $assessment->nm_dokter ?? null
                ),
                'poli' => $this->text(
                    $assessment->nm_poli ?? null
                ),
                'tanggal' => $assessedAt?->toDateString(),
                'tanggal_lengkap' => $this->longDate($assessedAt),
                'jam' => $assessedAt?->format('H:i') ?? '-',
                'jenis_layanan' => $this->careType(
                    $assessment->status_lanjut ?? null
                ),
            ],
            'hasil_akhir' => [
                'kesimpulan' => $this->text(
                    $assessment->kesimpulan ?? null
                ),
                'anjuran' => $this->text(
                    $assessment->anjuran ?? null
                ),
            ],
            'vitals' => $this->vitalItems($assessment),
            'sections' => [
                $this->section(
                    'anamnesis',
                    'Anamnesis dan Riwayat',
                    'bi-chat-square-heart',
                    [
                        $this->item(
                            'Sumber Informasi',
                            $assessment->informasi ?? null
                        ),
                        $this->item(
                            'Riwayat Penyakit Sekarang',
                            $assessment->rps ?? null,
                            true
                        ),
                        $this->item(
                            'Riwayat Penyakit Dahulu',
                            $assessment->rpd ?? null,
                            true
                        ),
                        $this->item(
                            'Riwayat Penyakit Keluarga',
                            $assessment->rpk ?? null,
                            true
                        ),
                        $this->item(
                            'Alergi',
                            $assessment->alergi ?? null
                        ),
                    ]
                ),
                $this->section(
                    'umum',
                    'Kondisi Umum dan Kelenjar',
                    'bi-person-check',
                    [
                        $this->item(
                            'Keadaan Umum',
                            $assessment->keadaan ?? null
                        ),
                        $this->item(
                            'Kesadaran',
                            $assessment->kesadaran ?? null
                        ),
                        $this->item(
                            'Submandibula',
                            $assessment->submandibula ?? null
                        ),
                        $this->item(
                            'Axilla',
                            $assessment->axilla ?? null
                        ),
                        $this->item(
                            'Supraklavikula',
                            $assessment->supraklavikula ?? null
                        ),
                        $this->item(
                            'Leher',
                            $assessment->leher ?? null
                        ),
                        $this->item(
                            'Inguinal',
                            $assessment->inguinal ?? null
                        ),
                        $this->item(
                            'Oedema',
                            $assessment->oedema ?? null
                        ),
                    ]
                ),
                $this->section(
                    'mata',
                    'Mata dan Sinus',
                    'bi-eye',
                    [
                        $this->item(
                            'Sinus Frontalis',
                            $assessment->sinus_frontalis ?? null
                        ),
                        $this->item(
                            'Sinus Maxilaris',
                            $assessment->sinus_maxilaris ?? null
                        ),
                        $this->item(
                            'Palpebra',
                            $assessment->palpebra ?? null
                        ),
                        $this->item(
                            'Sklera',
                            $assessment->sklera ?? null
                        ),
                        $this->item(
                            'Cornea',
                            $assessment->cornea ?? null
                        ),
                        $this->item(
                            'Buta Warna',
                            $assessment->buta_warna ?? null
                        ),
                        $this->item(
                            'Konjungtiva',
                            $assessment->konjungtiva ?? null
                        ),
                        $this->item(
                            'Lensa',
                            $assessment->lensa ?? null
                        ),
                        $this->item(
                            'Pupil',
                            $assessment->pupil ?? null
                        ),
                    ]
                ),
                $this->section(
                    'tht',
                    'Telinga, Hidung, Mulut, dan Leher',
                    'bi-emoji-smile',
                    [
                        $this->item(
                            'Lubang Telinga',
                            $assessment->lubang_telinga ?? null
                        ),
                        $this->item(
                            'Daun Telinga',
                            $assessment->daun_telinga ?? null
                        ),
                        $this->item(
                            'Selaput Pendengaran',
                            $assessment->selaput_pendengaran ?? null
                        ),
                        $this->item(
                            'Processus Mastoideus',
                            $assessment->proc_mastoideus ?? null
                        ),
                        $this->item(
                            'Septum Nasi',
                            $assessment->septum_nasi ?? null
                        ),
                        $this->item(
                            'Lubang Hidung',
                            $assessment->lubang_hidung ?? null
                        ),
                        $this->item(
                            'Bibir',
                            $assessment->bibir ?? null
                        ),
                        $this->item(
                            'Karies',
                            $assessment->caries ?? null
                        ),
                        $this->item(
                            'Lidah',
                            $assessment->lidah ?? null
                        ),
                        $this->item(
                            'Faring',
                            $assessment->faring ?? null
                        ),
                        $this->item(
                            'Tonsil',
                            $assessment->tonsil ?? null
                        ),
                        $this->item(
                            'Kelenjar Limfe',
                            $assessment->kelenjar_limfe ?? null
                        ),
                        $this->item(
                            'Kelenjar Gondok',
                            $assessment->kelenjar_gondok ?? null
                        ),
                    ]
                ),
                $this->section(
                    'dada',
                    'Dada dan Jantung',
                    'bi-heart-pulse',
                    [
                        $this->item(
                            'Gerakan Dada',
                            $assessment->gerakan_dada ?? null
                        ),
                        $this->item(
                            'Vocal Fremitus',
                            $assessment->vocal_femitus ?? null
                        ),
                        $this->item(
                            'Perkusi Dada',
                            $assessment->perkusi_dada ?? null
                        ),
                        $this->item(
                            'Bunyi Napas',
                            $assessment->bunyi_napas ?? null
                        ),
                        $this->item(
                            'Bunyi Tambahan',
                            $assessment->bunyi_tambahan ?? null
                        ),
                        $this->item(
                            'Ictus Cordis',
                            $assessment->ictus_cordis ?? null
                        ),
                        $this->item(
                            'Bunyi Jantung',
                            $assessment->bunyi_jantung ?? null
                        ),
                        $this->item(
                            'Batas Jantung',
                            $assessment->batas ?? null
                        ),
                    ]
                ),
                $this->section(
                    'abdomen',
                    'Abdomen dan Ginjal',
                    'bi-activity',
                    [
                        $this->item(
                            'Inspeksi',
                            $assessment->inspeksi ?? null
                        ),
                        $this->item(
                            'Palpasi',
                            $assessment->palpasi ?? null
                        ),
                        $this->item(
                            'Hepar',
                            $assessment->hepar ?? null
                        ),
                        $this->item(
                            'Perkusi Abdomen',
                            $assessment->perkusi_abdomen ?? null
                        ),
                        $this->item(
                            'Auskultasi',
                            $assessment->auskultasi ?? null
                        ),
                        $this->item(
                            'Limpa',
                            $assessment->limpa ?? null
                        ),
                        $this->item(
                            'Costovertebral',
                            $assessment->costovertebral ?? null
                        ),
                    ]
                ),
                $this->section(
                    'fisik',
                    'Kulit dan Ekstremitas',
                    'bi-person-arms-up',
                    [
                        $this->item(
                            'Kondisi Kulit',
                            $assessment->kondisi_kulit ?? null
                        ),
                        $this->item(
                            'Ekstremitas Atas',
                            $this->withNote(
                                $assessment->ekstrimitas_atas ?? null,
                                $assessment->ekstrimitas_atas_ket ?? null
                            )
                        ),
                        $this->item(
                            'Ekstremitas Bawah',
                            $this->withNote(
                                $assessment->ekstrimitas_bawah ?? null,
                                $assessment->ekstrimitas_bawah_ket ?? null
                            )
                        ),
                    ]
                ),
                $this->section(
                    'penunjang',
                    'Pemeriksaan Penunjang',
                    'bi-clipboard2-data',
                    [
                        $this->item(
                            'Laboratorium',
                            $assessment->laborat ?? null,
                            true
                        ),
                        $this->item(
                            'Radiologi',
                            $assessment->radiologi ?? null,
                            true
                        ),
                        $this->item(
                            'EKG',
                            $assessment->ekg ?? null,
                            true
                        ),
                        $this->item(
                            'Spirometri',
                            $assessment->spirometri ?? null,
                            true
                        ),
                        $this->item(
                            'Audiometri',
                            $assessment->audiometri ?? null,
                            true
                        ),
                        $this->item(
                            'Treadmill',
                            $assessment->treadmill ?? null,
                            true
                        ),
                        $this->item(
                            'Lain-lain',
                            $assessment->lainlain ?? null,
                            true
                        ),
                    ]
                ),
                $this->section(
                    'kebiasaan',
                    'Kebiasaan',
                    'bi-shield-check',
                    [
                        $this->item(
                            'Merokok',
                            $assessment->merokok ?? null
                        ),
                        $this->item(
                            'Alkohol',
                            $assessment->alkohol ?? null
                        ),
                    ]
                ),
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, unit: string}>
     */
    private function vitalItems(object $assessment): array
    {
        $height = $this->numericValue($assessment->tb ?? null);
        $weight = $this->numericValue($assessment->bb ?? null);
        $bmi = $height && $weight
            ? $weight / (($height / 100) ** 2)
            : null;

        return [
            $this->vital(
                'Tekanan Darah',
                $assessment->td ?? null,
                'mmHg'
            ),
            $this->vital(
                'Nadi',
                $assessment->nadi ?? null,
                'x/menit'
            ),
            $this->vital(
                'Pernapasan',
                $assessment->rr ?? null,
                'x/menit'
            ),
            $this->vital(
                'Suhu',
                $assessment->suhu ?? null,
                '°C'
            ),
            $this->vital(
                'Tinggi Badan',
                $assessment->tb ?? null,
                'cm'
            ),
            $this->vital(
                'Berat Badan',
                $assessment->bb ?? null,
                'kg'
            ),
            [
                'label' => 'Indeks Massa Tubuh',
                'value' => $bmi !== null
                    ? number_format($bmi, 1, ',', '.')
                    : '-',
                'unit' => 'kg/m²',
            ],
        ];
    }

    /**
     * @return array{label: string, value: string, unit: string}
     */
    private function vital(
        string $label,
        mixed $value,
        string $unit
    ): array {
        return [
            'label' => $label,
            'value' => $this->text($value),
            'unit' => $unit,
        ];
    }

    /**
     * @param  array<int, array{label: string, value: string, wide: bool}>  $items
     * @return array<string, mixed>
     */
    private function section(
        string $key,
        string $title,
        string $icon,
        array $items
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'icon' => $icon,
            'items' => $items,
        ];
    }

    /**
     * @return array{label: string, value: string, wide: bool}
     */
    private function item(
        string $label,
        mixed $value,
        bool $wide = false
    ): array {
        return [
            'label' => $label,
            'value' => $this->text($value),
            'wide' => $wide,
        ];
    }

    private function careType(mixed $value): string
    {
        return mb_strtolower(trim((string) $value)) === 'ranap'
            ? 'Rawat Inap'
            : 'Rawat Jalan';
    }

    private function withNote(mixed $value, mixed $note): string
    {
        $value = $this->text($value);
        $note = $this->text($note);

        return $note !== '-' ? $value.' — '.$note : $value;
    }

    private function measurement(mixed $value, string $unit): string
    {
        $value = $this->text($value);

        return $value === '-' ? $value : $value.' '.$unit;
    }

    private function numericValue(mixed $value): ?float
    {
        $normalized = str_replace(',', '.', trim((string) $value));

        return is_numeric($normalized) && (float) $normalized > 0
            ? (float) $normalized
            : null;
    }

    private function medicalRecordNumber(User $user): string
    {
        return trim((string) $user->username);
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function text(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : '-';
    }

    private function dateTimeValue(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        try {
            return Carbon::parse($value);
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
