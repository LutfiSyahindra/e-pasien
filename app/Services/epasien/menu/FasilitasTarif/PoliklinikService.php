<?php

namespace App\Services\epasien\menu\FasilitasTarif;

use App\Repositories\epasien\menu\FasilitasTarif\PoliklinikRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class PoliklinikService
{
    public function __construct(
        private readonly PoliklinikRepository $poliklinikRepository
    ) {}

    public function clinics(
        ?string $search = null,
        int $perPage = 12
    ): LengthAwarePaginator {
        $perPage = max(6, min($perPage, 24));
        $clinics = $this->poliklinikRepository->paginateClinics(
            $this->nullableText($search),
            $perPage
        );

        $clinics->setCollection(
            $clinics->getCollection()->map(
                fn (object $clinic): array => $this->formatClinic($clinic)
            )
        );

        return $clinics;
    }

    /**
     * @return array{
     *     total: int,
     *     priced: int,
     *     minimum: float,
     *     maximum: float,
     *     minimum_formatted: string,
     *     maximum_formatted: string
     * }
     */
    public function summary(): array
    {
        $summary = $this->poliklinikRepository->summary();
        $minimum = max(0, (float) ($summary?->minimum ?? 0));
        $maximum = max(0, (float) ($summary?->maximum ?? 0));

        return [
            'total' => (int) ($summary?->total ?? 0),
            'priced' => (int) ($summary?->priced ?? 0),
            'minimum' => $minimum,
            'maximum' => $maximum,
            'minimum_formatted' => $this->formatCurrency($minimum),
            'maximum_formatted' => $this->formatCurrency($maximum),
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     priced: int,
     *     minimum: float,
     *     maximum: float,
     *     minimum_formatted: string,
     *     maximum_formatted: string
     * }
     */
    public function emptySummary(): array
    {
        return [
            'total' => 0,
            'priced' => 0,
            'minimum' => 0,
            'maximum' => 0,
            'minimum_formatted' => 'Rp 0',
            'maximum_formatted' => 'Rp 0',
        ];
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     icon: string,
     *     new_fee: float,
     *     new_fee_available: bool,
     *     new_fee_formatted: string,
     *     returning_fee: float,
     *     returning_fee_available: bool,
     *     returning_fee_formatted: string
     * }
     */
    private function formatClinic(object $clinic): array
    {
        $code = $this->text($clinic->kd_poli ?? null);
        $name = $this->clinicName($clinic->nm_poli ?? null);
        $newFee = max(0, (float) ($clinic->registrasi ?? 0));
        $returningFee = max(0, (float) ($clinic->registrasilama ?? 0));

        return [
            'code' => $code,
            'name' => $name,
            'icon' => $this->clinicIcon($code, $name),
            'new_fee' => $newFee,
            'new_fee_available' => $newFee > 0,
            'new_fee_formatted' => $this->patientFee($newFee),
            'returning_fee' => $returningFee,
            'returning_fee_available' => $returningFee > 0,
            'returning_fee_formatted' => $this->patientFee($returningFee),
        ];
    }

    private function clinicIcon(string $code, string $name): string
    {
        $value = mb_strtolower($code.' '.$name);

        return match (true) {
            str_contains($value, 'anak') => 'bi-emoji-smile',
            str_contains($value, 'bedah') => 'bi-bandaid',
            str_contains($value, 'kandungan'),
            str_contains($value, ' obg'),
            str_contains($value, ' vk') => 'bi-gender-female',
            str_contains($value, 'mata') => 'bi-eye',
            str_contains($value, 'orthopedi') => 'bi-person-standing',
            str_contains($value, 'paru') => 'bi-lungs',
            str_contains($value, 'penyakit dalam') => 'bi-heart-pulse',
            str_contains($value, 'rehab'),
            str_contains($value, 'fisioterapi') => 'bi-activity',
            str_contains($value, 'syaraf') => 'bi-diagram-3',
            str_contains($value, ' tht') => 'bi-ear',
            str_contains($value, 'urologi') => 'bi-droplet',
            str_contains($value, 'laboratorium') => 'bi-droplet-half',
            str_contains($value, 'radiologi') => 'bi-radioactive',
            str_contains($value, 'apotek') => 'bi-capsule-pill',
            str_contains($value, 'mcu') => 'bi-clipboard2-pulse',
            str_contains($value, 'igd') => 'bi-hospital',
            default => 'bi-building',
        };
    }

    private function patientFee(float $value): string
    {
        return $value > 0
            ? $this->formatCurrency($value)
            : 'Konfirmasi tarif';
    }

    private function clinicName(mixed $value): string
    {
        $name = $this->text($value);

        return $name === mb_strtolower($name)
            ? mb_convert_case($name, MB_CASE_TITLE, 'UTF-8')
            : $name;
    }

    private function formatCurrency(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
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
}
