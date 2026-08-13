<?php

namespace App\Services\epasien\settings;

use App\Models\PatientGuarantorConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PatientGuarantorConfigurationService
{
    private bool $configurationLoaded = false;

    /** @var list<string>|null */
    private ?array $allowedCodes = null;

    /**
     * Filter data penjamin Khanza untuk pilihan pasien.
     *
     * @param  array<int, array{kd_pj: string, png_jawab: string}>  $options
     * @return array<int, array{kd_pj: string, png_jawab: string}>
     */
    public function filterOptions(array $options): array
    {
        $allowedCodes = $this->allowedCodes();

        if ($allowedCodes === null) {
            return array_values($options);
        }

        return array_values(array_filter(
            $options,
            fn (array $option): bool => in_array(
                $this->normalizeCode($option['kd_pj'] ?? ''),
                $allowedCodes,
                true
            )
        ));
    }

    /**
     * @param  array<int, array{kd_pj: string, png_jawab: string}>  $options
     * @return list<string>
     */
    public function selectedCodes(array $options): array
    {
        return array_values(array_map(
            fn (array $option): string => (string) $option['kd_pj'],
            $this->filterOptions($options)
        ));
    }

    public function allows(string $guarantorCode): bool
    {
        $allowedCodes = $this->allowedCodes();

        return $allowedCodes === null
            || in_array($this->normalizeCode($guarantorCode), $allowedCodes, true);
    }

    public function isConfigured(): bool
    {
        $this->allowedCodes();

        return $this->configurationLoaded && $this->allowedCodes !== null;
    }

    /**
     * @param  array<int, string>  $guarantorCodes
     */
    public function sync(array $guarantorCodes, User $configuredBy): PatientGuarantorConfiguration
    {
        $codes = $this->normalizeCodes($guarantorCodes);

        $configuration = PatientGuarantorConfiguration::query()->updateOrCreate(
            ['key' => PatientGuarantorConfiguration::DEFAULT_KEY],
            [
                'allowed_guarantor_codes' => $codes,
                'configured_by' => $configuredBy->getKey(),
            ]
        );

        $this->configurationLoaded = true;
        $this->allowedCodes = $codes;

        return $configuration;
    }

    /**
     * Null berarti konfigurasi belum pernah disimpan sehingga perilaku lama tetap digunakan.
     *
     * @return list<string>|null
     */
    private function allowedCodes(): ?array
    {
        if ($this->configurationLoaded) {
            return $this->allowedCodes;
        }

        $this->configurationLoaded = true;

        if (! $this->hasConfigurationTable()) {
            return null;
        }

        $configuration = PatientGuarantorConfiguration::query()
            ->where('key', PatientGuarantorConfiguration::DEFAULT_KEY)
            ->first();

        if (! $configuration) {
            return null;
        }

        $this->allowedCodes = $this->normalizeCodes(
            (array) $configuration->allowed_guarantor_codes
        );

        return $this->allowedCodes;
    }

    private function hasConfigurationTable(): bool
    {
        try {
            return Schema::connection(config('database.default'))
                ->hasTable((new PatientGuarantorConfiguration)->getTable());
        } catch (Throwable) {
            // Instalasi yang belum dimigrasikan atau test tanpa driver database
            // tetap menggunakan perilaku lama: seluruh penjamin tersedia.
            return false;
        }
    }

    /**
     * @param  array<int, mixed>  $codes
     * @return list<string>
     */
    private function normalizeCodes(array $codes): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (mixed $code): string => $this->normalizeCode((string) $code),
            $codes
        ))));
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }
}
