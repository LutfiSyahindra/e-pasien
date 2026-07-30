<?php

namespace App\Services\epasien\menu\FasilitasTarif;

use App\Repositories\epasien\menu\FasilitasTarif\RadiologiRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RadiologiService
{
    public function __construct(
        private readonly RadiologiRepository $radiologiRepository
    ) {}

    public function rates(
        ?string $class = null,
        ?string $search = null,
        int $perPage = 12
    ): LengthAwarePaginator {
        $perPage = max(6, min($perPage, 24));
        $rates = $this->radiologiRepository->paginateRates(
            $this->nullableText($class),
            $this->nullableText($search),
            $perPage
        );

        $rates->setCollection(
            $rates->getCollection()->map(
                fn (object $rate): array => $this->formatRate($rate)
            )
        );

        return $rates;
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    public function classes(): Collection
    {
        return $this->radiologiRepository
            ->classes()
            ->map(fn (mixed $class): array => [
                'value' => trim((string) $class),
                'label' => $this->classLabel($class),
            ])
            ->filter(fn (array $class): bool => $class['value'] !== '')
            ->values();
    }

    /**
     * @return array{
     *     total: int,
     *     minimum: float,
     *     maximum: float,
     *     minimum_formatted: string,
     *     maximum_formatted: string
     * }
     */
    public function summary(): array
    {
        $summary = $this->radiologiRepository->summary();
        $minimum = max(0, (float) ($summary?->minimum ?? 0));
        $maximum = max(0, (float) ($summary?->maximum ?? 0));

        return [
            'total' => (int) ($summary?->total ?? 0),
            'minimum' => $minimum,
            'maximum' => $maximum,
            'minimum_formatted' => $this->formatCurrency($minimum),
            'maximum_formatted' => $this->formatCurrency($maximum),
        ];
    }

    /**
     * @return array{
     *     total: int,
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
     *     class: string,
     *     class_label: string,
     *     tariff: float,
     *     tariff_formatted: string
     * }
     */
    private function formatRate(object $rate): array
    {
        $class = trim((string) ($rate->kelas ?? ''));
        $tariff = max(0, (float) ($rate->total_byr ?? 0));

        return [
            'code' => $this->text($rate->kd_jenis_prw ?? null),
            'name' => $this->text($rate->nm_perawatan ?? null),
            'class' => $class,
            'class_label' => $this->classLabel($class),
            'tariff' => $tariff,
            'tariff_formatted' => $this->formatCurrency($tariff),
        ];
    }

    private function classLabel(mixed $class): string
    {
        $class = trim((string) $class);

        return match ($class) {
            '-' => 'Tanpa kelas',
            '' => 'Kelas tidak tersedia',
            default => $class,
        };
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
