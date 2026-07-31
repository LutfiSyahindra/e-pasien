<?php

namespace Tests\Unit\Bpjs;

use App\Repositories\epasien\bridging\RujukanRepository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RujukanRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'services.bpjs.consumer_id' => 'consumer-test',
            'services.bpjs.secret_key' => 'secret-test',
            'services.bpjs.vclaim.base_url' => 'https://vclaim.test/root',
            'services.bpjs.vclaim.user_key' => 'user-key-test',
            'services.bpjs.vclaim.database_logging' => false,
        ]);

        Http::preventStrayRequests();
    }

    public function test_it_searches_pcare_referral_by_card_number(): void
    {
        Http::fake([
            'https://vclaim.test/*' => Http::response($this->successfulResponse()),
        ]);

        $result = (new RujukanRepository)->findPcareByCardNumber(
            ' 0000416382632 '
        );

        $this->assertSame(
            '030107010217Y001465',
            $result['response']['rujukan']['noKunjungan']
        );

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://vclaim.test/root/Rujukan/Peserta/0000416382632';
        });
    }

    public function test_it_searches_hospital_referral_by_card_number(): void
    {
        Http::fake([
            'https://vclaim.test/*' => Http::response($this->successfulResponse()),
        ]);

        (new RujukanRepository)->findHospitalByCardNumber('0105986780439');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://vclaim.test/root/Rujukan/RS/Peserta/0105986780439';
        });
    }

    public function test_it_lists_outgoing_hospital_referrals_by_date_range(): void
    {
        Http::fake([
            'https://vclaim.test/*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'Sukses'],
                'response' => ['list' => []],
            ]),
        ]);

        (new RujukanRepository)->listOutgoingHospitalReferrals(
            '2026-07-01',
            '2026-07-31'
        );

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://vclaim.test/root/Rujukan/Keluar/List/tglMulai/2026-07-01/tglAkhir/2026-07-31';
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function successfulResponse(): array
    {
        return [
            'metaData' => [
                'code' => '200',
                'message' => 'OK',
            ],
            'response' => [
                'rujukan' => [
                    'noKunjungan' => '030107010217Y001465',
                ],
            ],
        ];
    }
}
