<?php

namespace App\Services\epasien\menu\FasilitasTarif;

use App\Repositories\epasien\menu\FasilitasTarif\LaboratoriumRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LaboratoriumService
{
    private const CACHE_FRESH_SECONDS = 900;

    private const CACHE_STALE_SECONDS = 86400;

    public function __construct(
        private readonly LaboratoriumRepository $laboratoriumRepository
    ) {}

    public function items(
        ?string $group = null,
        ?string $search = null,
        int $perPage = 16
    ): LengthAwarePaginator {
        $perPage = max(8, min($perPage, 32));
        $group = $this->nullableText($group);
        $search = $this->nullableText($search);
        $items = Cache::flexible(
            $this->itemsCacheKey($group, $search, $perPage),
            [self::CACHE_FRESH_SECONDS, self::CACHE_STALE_SECONDS],
            function () use ($group, $search, $perPage): LengthAwarePaginator {
                $items = $this->laboratoriumRepository->paginateItems(
                    $group,
                    $search,
                    $perPage
                );
                $items->setCollection(
                    $items->getCollection()->map(
                        fn (object $item): array => $this->formatItem($item)
                    )
                );

                return $items;
            }
        );
        $items->setPath(LengthAwarePaginator::resolveCurrentPath());

        return $items;
    }

    /**
     * @return Collection<int, array{
     *     value: string,
     *     label: string,
     *     total_items: int
     * }>
     */
    public function groups(): Collection
    {
        return Cache::flexible(
            'epasien:khanza:laboratory:groups:v1',
            [self::CACHE_FRESH_SECONDS, self::CACHE_STALE_SECONDS],
            fn (): Collection => $this->laboratoriumRepository
                ->groups()
                ->map(fn (object $group): array => [
                    'value' => trim((string) ($group->kd_jenis_prw ?? '')),
                    'label' => $this->text($group->nm_perawatan ?? null),
                    'total_items' => (int) ($group->total_items ?? 0),
                ])
                ->filter(fn (array $group): bool => $group['value'] !== '')
                ->values()
        );
    }

    /**
     * @return array{
     *     groups: int,
     *     items: int,
     *     priced: int,
     *     minimum: float,
     *     maximum: float,
     *     minimum_formatted: string,
     *     maximum_formatted: string
     * }
     */
    public function summary(): array
    {
        $summary = Cache::flexible(
            'epasien:khanza:laboratory:summary:v1',
            [self::CACHE_FRESH_SECONDS, self::CACHE_STALE_SECONDS],
            fn (): ?object => $this->laboratoriumRepository->summary()
        );
        $minimum = max(0, (float) ($summary?->minimum ?? 0));
        $maximum = max(0, (float) ($summary?->maximum ?? 0));

        return [
            'groups' => (int) ($summary?->groups_count ?? 0),
            'items' => (int) ($summary?->items_count ?? 0),
            'priced' => (int) ($summary?->priced_count ?? 0),
            'minimum' => $minimum,
            'maximum' => $maximum,
            'minimum_formatted' => $this->formatCurrency($minimum),
            'maximum_formatted' => $this->formatCurrency($maximum),
        ];
    }

    private function itemsCacheKey(
        ?string $group,
        ?string $search,
        int $perPage
    ): string {
        $filters = json_encode([
            'group' => $group,
            'search' => $search,
            'per_page' => $perPage,
            'page' => LengthAwarePaginator::resolveCurrentPage(),
        ], JSON_THROW_ON_ERROR);

        return 'epasien:khanza:laboratory:list:v2:'.hash('sha256', $filters);
    }

    /**
     * @return array{
     *     groups: int,
     *     items: int,
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
            'groups' => 0,
            'items' => 0,
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
     *     template_id: string,
     *     name: string,
     *     group_name: string,
     *     unit: string,
     *     has_unit: bool,
     *     tariff: float,
     *     has_tariff: bool,
     *     tariff_formatted: string
     * }
     */
    private function formatItem(object $item): array
    {
        $unit = trim((string) ($item->satuan ?? ''));
        $tariff = max(0, (float) ($item->biaya_item ?? 0));
        $hasTariff = $tariff > 0;

        return [
            'code' => $this->text($item->kd_jenis_prw ?? null),
            'template_id' => $this->text($item->id_template ?? null),
            'name' => $this->text($item->nama_pemeriksaan ?? null),
            'group_name' => $this->text($item->nm_perawatan ?? null),
            'unit' => $unit,
            'has_unit' => $unit !== '',
            'tariff' => $tariff,
            'has_tariff' => $hasTariff,
            'tariff_formatted' => $hasTariff
                ? $this->formatCurrency($tariff)
                : 'Konfirmasi tarif',
        ];
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
