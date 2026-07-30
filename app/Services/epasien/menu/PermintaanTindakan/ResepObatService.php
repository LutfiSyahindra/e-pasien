<?php

namespace App\Services\epasien\menu\PermintaanTindakan;

use App\Models\User;
use App\Repositories\epasien\menu\PermintaanTindakan\ResepObatRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ResepObatService
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
        private readonly ResepObatRepository $resepObatRepository
    ) {}

    public function patientForUser(User $user): ?object
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return null;
        }

        return $this->resepObatRepository->findPatient($medicalRecordNumber);
    }

    public function prescriptionsForUser(
        User $user,
        ?string $prescriptionType = null,
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

        $prescriptions = $this->resepObatRepository->paginatePrescriptions(
            $medicalRecordNumber,
            $prescriptionType,
            $careType,
            $startDate,
            $endDate,
            $this->nullableText($search),
            $perPage
        );
        $doctorPrescriptionNumbers = $this->numbersForSource(
            $prescriptions->getCollection(),
            'dokter'
        );
        $dischargeRequestNumbers = $this->numbersForSource(
            $prescriptions->getCollection(),
            'pulang'
        );
        $medicineItems = $this->resepObatRepository
            ->medicineItems($doctorPrescriptionNumbers)
            ->groupBy(fn (object $row): string => $this->text($row->no_resep ?? null));
        $compoundedItems = $this->resepObatRepository
            ->compoundedItems($doctorPrescriptionNumbers);
        $formattedCompoundedItems = $compoundedItems
            ->groupBy(fn (object $row): string => $this->text($row->no_resep ?? null))
            ->map(
                fn (Collection $items): Collection => $items->map(
                    fn (object $item): array => $this->formatCompoundedItem($item)
                )
            );
        $dischargeItems = $this->resepObatRepository
            ->dischargeItems($dischargeRequestNumbers)
            ->groupBy(
                fn (object $row): string => $this->text(
                    $row->no_permintaan ?? null
                )
            );

        $prescriptions->setCollection(
            $prescriptions->getCollection()->map(function (object $prescription) use (
                $medicineItems,
                $formattedCompoundedItems,
                $dischargeItems
            ): array {
                $number = $this->text($prescription->nomor_resep ?? null);
                $source = $this->text($prescription->sumber ?? null);
                $items = $source === 'pulang'
                    ? $dischargeItems->get($number, collect())
                        ->map(fn (object $item): array => $this->formatDischargeItem($item))
                    : $medicineItems->get($number, collect())
                        ->map(fn (object $item): array => $this->formatMedicineItem($item));
                $compounded = $source === 'dokter'
                    ? $formattedCompoundedItems->get($number, collect())
                    : collect();

                return $this->formatPrescription(
                    $prescription,
                    $items,
                    $compounded
                );
            })
        );

        return $prescriptions;
    }

    /**
     * @return array{all: int, ralan: int, ranap: int, dokter: int, pulang: int}
     */
    public function countsForUser(User $user): array
    {
        $medicalRecordNumber = $this->medicalRecordNumber($user);

        if ($medicalRecordNumber === '') {
            return $this->emptyCounts();
        }

        return $this->resepObatRepository
            ->prescriptionCounts($medicalRecordNumber);
    }

    /**
     * @return array{all: int, ralan: int, ranap: int, dokter: int, pulang: int}
     */
    public function emptyCounts(): array
    {
        return [
            'all' => 0,
            'ralan' => 0,
            'ranap' => 0,
            'dokter' => 0,
            'pulang' => 0,
        ];
    }

    /**
     * @param  Collection<int, object>  $prescriptions
     * @return array<int, string>
     */
    private function numbersForSource(
        Collection $prescriptions,
        string $source
    ): array {
        return $prescriptions
            ->filter(
                fn (object $row): bool => $this->text($row->sumber ?? null) === $source
            )
            ->pluck('nomor_resep')
            ->map(fn (mixed $number): string => $this->text($number))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @param  Collection<int, array<string, mixed>>  $compoundedItems
     * @return array<string, mixed>
     */
    private function formatPrescription(
        object $prescription,
        Collection $items,
        Collection $compoundedItems
    ): array {
        $prescribedAt = $this->dateValue($prescription->tanggal ?? null);
        $completedAt = $this->dateValue($prescription->tanggal_selesai ?? null);
        $source = $this->text($prescription->sumber ?? null);
        $careType = strtolower($this->text(
            $prescription->status_layanan ?? null
        ));
        $status = strtolower($this->text(
            $prescription->status_proses ?? null
        ));
        $isCompleted = $status === 'selesai';

        return [
            'nomor_resep' => $this->text($prescription->nomor_resep ?? null),
            'no_rawat' => $this->text($prescription->no_rawat ?? null),
            'sumber' => $source,
            'jenis_resep' => $source === 'pulang'
                ? 'Resep Pulang'
                : 'Resep Dokter',
            'jenis_resep_icon' => $source === 'pulang'
                ? 'bi-house-heart'
                : 'bi-prescription2',
            'tanggal' => $prescribedAt?->toDateString(),
            'tanggal_lengkap' => $this->longDate($prescribedAt),
            'jam' => $this->timeValue($prescription->jam ?? null),
            'hari_short' => $prescribedAt
                ? substr(self::DAYS[$prescribedAt->dayOfWeek], 0, 3)
                : '-',
            'tanggal_angka' => $prescribedAt?->format('d') ?? '-',
            'bulan_short' => $prescribedAt
                ? substr(self::MONTHS[(int) $prescribedAt->format('n')], 0, 3)
                : '-',
            'tanggal_selesai' => $completedAt?->toDateString(),
            'tanggal_selesai_lengkap' => $this->longDate($completedAt),
            'jam_selesai' => $this->timeValue(
                $prescription->jam_selesai ?? null
            ),
            'status' => $status,
            'status_label' => $this->statusLabel($source, $isCompleted),
            'status_icon' => $isCompleted
                ? 'bi-check-circle-fill'
                : ($status === 'menunggu'
                    ? 'bi-clock-history'
                    : 'bi-hourglass-split'),
            'status_layanan' => $careType,
            'jenis_layanan' => $careType === 'ranap'
                ? 'Rawat Inap'
                : 'Rawat Jalan',
            'layanan_tone' => $careType === 'ranap' ? 'ranap' : 'ralan',
            'dokter' => $this->fallbackText(
                $prescription->nm_dokter ?? null,
                'Dokter belum tercatat'
            ),
            'poli' => $this->fallbackText(
                $prescription->nm_poli ?? null,
                $careType === 'ranap' ? 'Rawat Inap' : 'Poliklinik'
            ),
            'obat' => $items->values()->all(),
            'racikan' => $compoundedItems->values()->all(),
            'jumlah_obat' => $items->count(),
            'jumlah_racikan' => $compoundedItems->count(),
            'jumlah_item' => $items->count() + $compoundedItems->count(),
            'selesai' => $isCompleted,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMedicineItem(object $item): array
    {
        $code = $this->text($item->kode_brng ?? null);
        $fallback = $code !== '' ? $code : 'Obat tidak dikenal';

        return [
            'kode' => $code,
            'nama' => $this->fallbackText($item->nama_brng ?? null, $fallback),
            'jumlah' => $this->quantity($item->jml ?? null),
            'satuan' => $this->text($item->kode_sat ?? null),
            'aturan_pakai' => $this->fallbackText(
                $item->aturan_pakai ?? null,
                'Aturan pakai belum dicatat'
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDischargeItem(object $item): array
    {
        $code = $this->text($item->kode_brng ?? null);
        $fallback = $code !== '' ? $code : 'Obat tidak dikenal';

        return [
            'kode' => $code,
            'nama' => $this->fallbackText($item->nama_brng ?? null, $fallback),
            'jumlah' => $this->quantity($item->jml ?? null),
            'satuan' => $this->text($item->kode_sat ?? null),
            'aturan_pakai' => $this->fallbackText(
                $item->dosis ?? null,
                'Dosis belum dicatat'
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCompoundedItem(object $item): array
    {
        $number = $this->text($item->no_racik ?? null);

        return [
            'nomor' => $number,
            'nama' => $this->fallbackText(
                $item->nama_racik ?? null,
                'Racikan '.$number
            ),
            'metode' => $this->fallbackText(
                $item->nm_racik ?? null,
                'Metode racik belum dicatat'
            ),
            'jumlah' => $this->quantity($item->jml_dr ?? null),
            'aturan_pakai' => $this->fallbackText(
                $item->aturan_pakai ?? null,
                'Aturan pakai belum dicatat'
            ),
            'keterangan' => $this->text($item->keterangan ?? null),
        ];
    }

    private function statusLabel(string $source, bool $isCompleted): string
    {
        if ($source === 'pulang') {
            return $isCompleted ? 'Sudah Divalidasi' : 'Menunggu Validasi';
        }

        return $isCompleted ? 'Sudah Diserahkan' : 'Sedang Disiapkan';
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

    private function fallbackText(mixed $value, string $fallback): string
    {
        $value = $this->text($value);

        return $value !== '' ? $value : $fallback;
    }

    private function text(mixed $value): string
    {
        return trim((string) $value);
    }

    private function quantity(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '-';
        }

        $number = (float) $value;

        if (floor($number) === $number) {
            return number_format($number, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($number, 3, ',', '.'), '0'), ',');
    }

    private function dateValue(mixed $value): ?Carbon
    {
        $value = $this->text($value);

        if ($value === '' || $value === '0000-00-00') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function timeValue(mixed $value): string
    {
        $value = $this->text($value);

        if ($value === '' || $value === '00:00:00') {
            return '-';
        }

        return substr($value, 0, 5);
    }

    private function longDate(?Carbon $date): string
    {
        if ($date === null) {
            return '-';
        }

        return self::DAYS[$date->dayOfWeek]
            .', '.$date->format('d')
            .' '.self::MONTHS[(int) $date->format('n')]
            .' '.$date->format('Y');
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
