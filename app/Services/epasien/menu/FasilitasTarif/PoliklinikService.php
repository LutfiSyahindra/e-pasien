<?php

namespace App\Services\epasien\menu\FasilitasTarif;

use App\Repositories\epasien\menu\FasilitasTarif\PoliklinikRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PoliklinikService
{
    private const CACHE_FRESH_SECONDS = 900;

    private const CACHE_STALE_SECONDS = 86400;

    public function __construct(
        private readonly PoliklinikRepository $poliklinikRepository
    ) {}

    public function clinics(
        ?string $search = null,
        int $perPage = 12
    ): LengthAwarePaginator {
        $perPage = max(6, min($perPage, 24));
        $search = $this->nullableText($search);
        $clinics = Cache::flexible(
            $this->clinicsCacheKey($search, $perPage),
            [self::CACHE_FRESH_SECONDS, self::CACHE_STALE_SECONDS],
            function () use ($search, $perPage): LengthAwarePaginator {
                $clinics = $this->poliklinikRepository->paginateClinics(
                    $search,
                    $perPage
                );
                $clinics->setCollection(
                    $clinics->getCollection()->map(
                        fn (object $clinic): array => $this->formatClinic($clinic)
                    )
                );

                return $clinics;
            }
        );
        $clinics->setPath(LengthAwarePaginator::resolveCurrentPath());

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
        $summary = Cache::flexible(
            'epasien:khanza:clinics:summary:v1',
            [self::CACHE_FRESH_SECONDS, self::CACHE_STALE_SECONDS],
            fn (): ?object => $this->poliklinikRepository->summary()
        );
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

    private function clinicsCacheKey(
        ?string $search,
        int $perPage
    ): string {
        $filters = json_encode([
            'search' => $search,
            'per_page' => $perPage,
            'page' => LengthAwarePaginator::resolveCurrentPage(),
        ], JSON_THROW_ON_ERROR);

        return 'epasien:khanza:clinics:list:v2:'.hash('sha256', $filters);
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
