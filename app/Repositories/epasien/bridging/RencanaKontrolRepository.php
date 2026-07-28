<?php

namespace App\Repositories\epasien\bridging;

use App\Traits\Bpjs\VClaimTrait;

class RencanaKontrolRepository
{
    use VClaimTrait;

    /**
     * @return array<string, mixed>
     */
    public function findByControlLetterNumber(string $controlLetterNumber): array
    {
        $endpoint = 'RencanaKontrol/noSuratKontrol/'
            .rawurlencode(trim($controlLetterNumber));

        return $this->getVclaim($endpoint);
    }

    /**
     * @return array<string, mixed>
     */
    public function listByCardNumber(
        string $month,
        string $year,
        string $cardNumber,
        int $filter = 2
    ): array {
        $endpoint = sprintf(
            'RencanaKontrol/ListRencanaKontrol/Bulan/%s/Tahun/%s/Nokartu/%s/filter/%d',
            rawurlencode($month),
            rawurlencode($year),
            rawurlencode($cardNumber),
            $filter
        );

        return $this->getVclaim($endpoint);
    }
}
