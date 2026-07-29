<?php

namespace App\Services\epasien\menu;

use App\Models\User;
use App\Repositories\epasien\menu\RiwayatPemeriksaanRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class RiwayatPemeriksaanService
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
        private readonly RiwayatPemeriksaanRepository $riwayatPemeriksaanRepository
    ) {}

    public function patientForUser(User $user): ?object
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return null;
        }

        return $this->riwayatPemeriksaanRepository->findPatient($medicalRecordNumber);
    }

    public function completedHistory(
        User $user,
        ?string $careType = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $doctorCode = null,
        int $perPage = 8
    ): LengthAwarePaginator {
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $perPage = max(4, min($perPage, 20));

        if ($medicalRecordNumber === '') {
            return $this->emptyPaginator($perPage);
        }

        $examinations = $this->riwayatPemeriksaanRepository
            ->paginateCompletedExaminations(
                $medicalRecordNumber,
                $careType,
                $startDate,
                $endDate,
                $doctorCode,
                $perPage
            );

        $examinations->setCollection(
            $examinations->getCollection()->map(
                fn (object $examination): array => $this->formatExamination($examination)
            )
        );

        return $examinations;
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    public function completedDoctors(User $user): array
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return [];
        }

        return $this->riwayatPemeriksaanRepository
            ->completedExaminationDoctors($medicalRecordNumber)
            ->map(function (object $doctor): array {
                $code = trim((string) ($doctor->kd_dokter ?? ''));

                return [
                    'code' => $code,
                    'name' => trim((string) ($doctor->nm_dokter ?? '')) ?: $code,
                ];
            })
            ->filter(fn (array $doctor): bool => $doctor['code'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array{all: int, Ralan: int, Ranap: int}
     */
    public function completedCounts(User $user): array
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return $this->emptyCounts();
        }

        return $this->riwayatPemeriksaanRepository
            ->completedExaminationCounts($medicalRecordNumber);
    }

    /**
     * @return array{all: int, Ralan: int, Ranap: int}
     */
    public function emptyCounts(): array
    {
        return [
            'all' => 0,
            'Ralan' => 0,
            'Ranap' => 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resumeForUser(User $user, string $noRawat, string $careType): ?array
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $noRawat = trim($noRawat);

        if (
            $medicalRecordNumber === ''
            || $noRawat === ''
            || ! in_array($careType, ['Ralan', 'Ranap'], true)
        ) {
            return null;
        }

        $resume = $this->riwayatPemeriksaanRepository
            ->findResumeForCompletedVisit($medicalRecordNumber, $noRawat, $careType);

        if ($resume === null) {
            return null;
        }

        return $this->formatResume($resume, $noRawat, $careType);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function paymentForUser(User $user, string $noRawat): ?array
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);
        $noRawat = trim($noRawat);

        if ($medicalRecordNumber === '' || $noRawat === '') {
            return null;
        }

        $billing = $this->riwayatPemeriksaanRepository
            ->findBillingForCompletedVisit($medicalRecordNumber, $noRawat);

        if ($billing === null) {
            return null;
        }

        return $this->formatPayment($billing['visit'], $billing['rows']);
    }

    private function medicalRecordNumber(User $user): string
    {
        return trim((string) $user->username);
    }

    /**
     * @return array<string, string>
     */
    private function formatExamination(object $examination): array
    {
        $date = Carbon::parse($examination->tgl_registrasi)->startOfDay();
        $careType = trim((string) ($examination->status_lanjut ?? ''));

        return [
            'no_reg' => trim((string) ($examination->no_reg ?? '')),
            'no_rawat' => trim((string) ($examination->no_rawat ?? '')),
            'tanggal' => $date->toDateString(),
            'tanggal_lengkap' => self::DAYS[$date->dayOfWeek].', '
                .$date->format('d').' '.self::MONTHS[(int) $date->format('n')].' '
                .$date->format('Y'),
            'hari_short' => substr(self::DAYS[$date->dayOfWeek], 0, 3),
            'tanggal_angka' => $date->format('d'),
            'bulan_short' => substr(self::MONTHS[(int) $date->format('n')], 0, 3),
            'jam' => $this->timeValue($examination->jam_reg ?? null),
            'status_lanjut' => $careType !== '' ? $careType : '-',
            'jenis_layanan' => $careType === 'Ranap' ? 'Rawat Inap' : 'Rawat Jalan',
            'layanan_tone' => $careType === 'Ranap' ? 'ranap' : 'ralan',
            'dokter' => trim((string) ($examination->nm_dokter ?? '')) ?: '-',
            'kd_dokter' => trim((string) ($examination->kd_dokter ?? '')) ?: '-',
            'poli' => trim((string) ($examination->nm_poli ?? '')) ?: '-',
            'kd_poli' => trim((string) ($examination->kd_poli ?? '')) ?: '-',
            'penjamin' => trim((string) ($examination->png_jawab ?? '')) ?: '-',
            'kd_pj' => trim((string) ($examination->kd_pj ?? '')) ?: '-',
            'status_bayar' => trim((string) ($examination->status_bayar ?? '')) ?: '-',
            'status_daftar' => trim((string) ($examination->stts_daftar ?? '')) ?: '-',
        ];
    }

    private function timeValue(mixed $time): string
    {
        $value = trim((string) $time);

        if ($value === '') {
            return '-';
        }

        return substr($value, 0, 5);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatResume(object $resume, string $noRawat, string $careType): array
    {
        $sections = $careType === 'Ranap'
            ? $this->ranapResumeSections($resume)
            : $this->ralanResumeSections($resume);

        return [
            'no_rawat' => $noRawat,
            'status_lanjut' => $careType,
            'jenis_layanan' => $careType === 'Ranap' ? 'Rawat Inap' : 'Rawat Jalan',
            'dokter' => trim((string) ($resume->nm_dokter ?? '')) ?: '-',
            'sections' => $sections,
        ];
    }

    /**
     * @param  iterable<int, object>  $billingRows
     * @return array<string, mixed>
     */
    private function formatPayment(object $visit, iterable $billingRows): array
    {
        $rows = collect($billingRows)
            ->map(fn (object $row): array => $this->formatBillingRow($row))
            ->values();
        $detailRows = $rows->where('type', 'detail');
        $billingDate = trim((string) ($rows->first()['tgl_byr'] ?? ''))
            ?: trim((string) ($visit->tgl_registrasi ?? ''));
        $careType = trim((string) ($visit->status_lanjut ?? ''));

        $subtotal = $detailRows
            ->filter(
                fn (array $row): bool => $row['status'] !== 'Tambahan'
                    && (float) $row['totalbiaya'] > 0
            )
            ->sum(fn (array $row): float => (float) $row['totalbiaya']);
        $additional = $detailRows
            ->where('status', 'Tambahan')
            ->sum(fn (array $row): float => (float) $row['totalbiaya']);
        $deduction = abs($detailRows
            ->filter(fn (array $row): bool => (float) $row['totalbiaya'] < 0)
            ->sum(fn (array $row): float => (float) $row['totalbiaya']));
        $grandTotal = $detailRows
            ->sum(fn (array $row): float => (float) $row['totalbiaya']);

        return [
            'no_rawat' => trim((string) ($visit->no_rawat ?? '')),
            'no_rkm_medis' => trim((string) ($visit->no_rkm_medis ?? '')),
            'nomor_nota' => $this->billingInformationValue($rows->all(), 'No.Nota'),
            'tanggal_bayar' => $billingDate,
            'tanggal_bayar_lengkap' => $this->longDate($billingDate),
            'jam_registrasi' => $this->timeValue($visit->jam_reg ?? null),
            'pasien' => trim((string) ($visit->nm_pasien ?? '')) ?: '-',
            'dokter' => trim((string) ($visit->nm_dokter ?? '')) ?: '-',
            'poli' => trim((string) ($visit->nm_poli ?? '')) ?: '-',
            'penjamin' => trim((string) ($visit->png_jawab ?? '')) ?: '-',
            'status_bayar' => trim((string) ($visit->status_bayar ?? '')) ?: '-',
            'status_lanjut' => $careType ?: '-',
            'jenis_layanan' => $careType === 'Ranap' ? 'Rawat Inap' : 'Rawat Jalan',
            'summary' => [
                'subtotal' => round($subtotal, 2),
                'tambahan' => round($additional, 2),
                'pengurang' => round($deduction, 2),
                'total' => round($grandTotal, 2),
                'jumlah_item' => $detailRows->count(),
                'jumlah_baris' => $rows->count(),
            ],
            'rows' => $rows->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBillingRow(object $row): array
    {
        $number = trim((string) ($row->no ?? ''));
        $description = trim((string) ($row->nm_perawatan ?? ''));
        $separator = trim((string) ($row->pemisah ?? ''));
        $status = trim((string) ($row->status ?? ''));
        $total = (float) ($row->totalbiaya ?? 0);
        $cleanDescription = trim((string) preg_replace('/^\s*:\s*/u', '', $description));

        if ($status === '-') {
            $type = 'information';
        } elseif ($separator === ':' || $total !== 0.0) {
            $type = 'detail';
        } elseif (str_starts_with($status, 'Ttl')) {
            $type = 'subtotal';
        } elseif ($number !== '') {
            $type = 'heading';
        } else {
            $type = 'note';
        }

        return [
            'noindex' => (string) ($row->noindex ?? ''),
            'no_rawat' => trim((string) ($row->no_rawat ?? '')),
            'tgl_byr' => trim((string) ($row->tgl_byr ?? '')),
            'no' => $number,
            'nm_perawatan' => $description,
            'pemisah' => $separator,
            'biaya' => (float) ($row->biaya ?? 0),
            'jumlah' => (float) ($row->jumlah ?? 0),
            'tambahan' => (float) ($row->tambahan ?? 0),
            'totalbiaya' => $total,
            'status' => $status,
            'type' => $type,
            'label' => $number !== '' ? $number : ($cleanDescription ?: '-'),
            'description' => $number !== '' && $cleanDescription !== ''
                ? $cleanDescription
                : ($number === '' ? ($cleanDescription ?: '-') : ''),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function billingInformationValue(array $rows, string $label): string
    {
        foreach ($rows as $row) {
            if (strcasecmp((string) $row['no'], $label) === 0) {
                return trim((string) $row['description']) ?: '-';
            }
        }

        return '-';
    }

    private function longDate(string $date): string
    {
        if ($date === '') {
            return '-';
        }

        try {
            $value = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return $date;
        }

        return self::DAYS[$value->dayOfWeek].', '
            .$value->format('d').' '.self::MONTHS[(int) $value->format('n')].' '
            .$value->format('Y');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ralanResumeSections(object $resume): array
    {
        return $this->availableSections([
            $this->resumeSection('Ringkasan Klinis', 'bi-clipboard2-pulse', [
                $this->resumeItem($resume, 'Keluhan Utama', 'keluhan_utama'),
                $this->resumeItem($resume, 'Jalannya Penyakit', 'jalannya_penyakit'),
                $this->resumeItem($resume, 'Pemeriksaan Penunjang', 'pemeriksaan_penunjang'),
                $this->resumeItem($resume, 'Hasil Laboratorium', 'hasil_laborat'),
            ]),
            $this->diagnosisSection($resume),
            $this->procedureSection($resume),
            $this->resumeSection('Rencana Pulang', 'bi-house-check', [
                $this->resumeItem($resume, 'Kondisi Pulang', 'kondisi_pulang'),
                $this->resumeItem($resume, 'Obat Pulang', 'obat_pulang'),
            ]),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ranapResumeSections(object $resume): array
    {
        return $this->availableSections([
            $this->resumeSection('Ringkasan Perawatan', 'bi-clipboard2-pulse', [
                $this->resumeItem($resume, 'Diagnosis Awal', 'diagnosa_awal'),
                $this->resumeItem($resume, 'Alasan Dirawat', 'alasan'),
                $this->resumeItem($resume, 'Keluhan Utama', 'keluhan_utama'),
                $this->resumeItem($resume, 'Pemeriksaan Fisik', 'pemeriksaan_fisik'),
                $this->resumeItem($resume, 'Jalannya Penyakit', 'jalannya_penyakit'),
            ]),
            $this->resumeSection('Penunjang dan Terapi', 'bi-activity', [
                $this->resumeItem($resume, 'Pemeriksaan Penunjang', 'pemeriksaan_penunjang'),
                $this->resumeItem($resume, 'Hasil Laboratorium', 'hasil_laborat'),
                $this->resumeItem($resume, 'Tindakan dan Operasi', 'tindakan_dan_operasi'),
                $this->resumeItem($resume, 'Obat Selama di Rumah Sakit', 'obat_di_rs'),
            ]),
            $this->diagnosisSection($resume),
            $this->procedureSection($resume),
            $this->resumeSection('Kondisi dan Tindak Lanjut', 'bi-arrow-right-circle', [
                $this->resumeItem($resume, 'Alergi', 'alergi'),
                $this->resumeItem($resume, 'Diet', 'diet'),
                $this->resumeItem($resume, 'Laboratorium Belum Selesai', 'lab_belum'),
                $this->resumeItem($resume, 'Edukasi', 'edukasi'),
                $this->resumeItem($resume, 'Cara Keluar', 'cara_keluar'),
                $this->resumeItem($resume, 'Keterangan Cara Keluar', 'ket_keluar'),
                $this->resumeItem($resume, 'Keadaan Saat Keluar', 'keadaan'),
                $this->resumeItem($resume, 'Keterangan Keadaan', 'ket_keadaan'),
                $this->resumeItem($resume, 'Perawatan Dilanjutkan', 'dilanjutkan'),
                $this->resumeItem($resume, 'Keterangan Tindak Lanjut', 'ket_dilanjutkan'),
                $this->resumeItem($resume, 'Rencana Kontrol', 'kontrol'),
                $this->resumeItem($resume, 'Obat Pulang', 'obat_pulang'),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function diagnosisSection(object $resume): ?array
    {
        return $this->resumeSection('Diagnosis', 'bi-file-medical', [
            $this->resumeItem($resume, 'Diagnosis Utama', 'diagnosa_utama', 'kd_diagnosa_utama'),
            $this->resumeItem($resume, 'Diagnosis Sekunder 1', 'diagnosa_sekunder', 'kd_diagnosa_sekunder'),
            $this->resumeItem($resume, 'Diagnosis Sekunder 2', 'diagnosa_sekunder2', 'kd_diagnosa_sekunder2'),
            $this->resumeItem($resume, 'Diagnosis Sekunder 3', 'diagnosa_sekunder3', 'kd_diagnosa_sekunder3'),
            $this->resumeItem($resume, 'Diagnosis Sekunder 4', 'diagnosa_sekunder4', 'kd_diagnosa_sekunder4'),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function procedureSection(object $resume): ?array
    {
        return $this->resumeSection('Prosedur', 'bi-bandaid', [
            $this->resumeItem($resume, 'Prosedur Utama', 'prosedur_utama', 'kd_prosedur_utama'),
            $this->resumeItem($resume, 'Prosedur Sekunder 1', 'prosedur_sekunder', 'kd_prosedur_sekunder'),
            $this->resumeItem($resume, 'Prosedur Sekunder 2', 'prosedur_sekunder2', 'kd_prosedur_sekunder2'),
            $this->resumeItem($resume, 'Prosedur Sekunder 3', 'prosedur_sekunder3', 'kd_prosedur_sekunder3'),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>|null>  $items
     * @return array<string, mixed>|null
     */
    private function resumeSection(string $title, string $icon, array $items): ?array
    {
        $availableItems = array_values(array_filter($items));

        if ($availableItems === []) {
            return null;
        }

        return [
            'title' => $title,
            'icon' => $icon,
            'items' => $availableItems,
        ];
    }

    /**
     * @return array<string, string>|null
     */
    private function resumeItem(
        object $resume,
        string $label,
        string $valueColumn,
        ?string $codeColumn = null
    ): ?array {
        $value = trim((string) ($resume->{$valueColumn} ?? ''));
        $code = $codeColumn === null
            ? ''
            : trim((string) ($resume->{$codeColumn} ?? ''));

        if ($value === '' && $code === '') {
            return null;
        }

        return [
            'label' => $label,
            'value' => $value !== '' ? $value : '-',
            'code' => $code,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>|null>  $sections
     * @return array<int, array<string, mixed>>
     */
    private function availableSections(array $sections): array
    {
        return array_values(array_filter($sections));
    }

    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage, LengthAwarePaginator::resolveCurrentPage(), [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
    }
}
