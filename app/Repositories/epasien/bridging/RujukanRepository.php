<?php

namespace App\Repositories\epasien\bridging;

use App\Traits\Bpjs\VClaimTrait;

class RujukanRepository
{
    use VClaimTrait;

    /**
     * @return array<string, mixed>
     */
    public function findPcareByCardNumber(string $cardNumber): array
    {
        return $this->getVclaim(
            'Rujukan/Peserta/'.rawurlencode(trim($cardNumber))
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function findHospitalByCardNumber(string $cardNumber): array
    {
        return $this->getVclaim(
            'Rujukan/RS/Peserta/'.rawurlencode(trim($cardNumber))
        );
    }
}
