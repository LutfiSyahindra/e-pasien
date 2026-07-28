<?php

namespace Tests\Unit\Bpjs;

use App\Repositories\epasien\bridging\RencanaKontrolRepository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RencanaKontrolRepositoryTest extends TestCase
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

    public function test_it_calls_the_control_plan_endpoint_from_the_trustmark_documentation(): void
    {
        Http::fake([
            'https://vclaim.test/*' => Http::response([
                'metaData' => [
                    'code' => '201',
                    'message' => 'Data tidak ditemukan.',
                ],
                'response' => null,
            ]),
        ]);

        $result = (new RencanaKontrolRepository)->listByCardNumber(
            '07',
            '2026',
            '0002035874204',
            2
        );

        $this->assertSame('201', $result['metaData']['code']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://vclaim.test/root/RencanaKontrol/ListRencanaKontrol/Bulan/07/Tahun/2026/Nokartu/0002035874204/filter/2'
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded');
        });
    }

    public function test_it_calls_the_new_control_letter_detail_endpoint(): void
    {
        Http::fake([
            'https://vclaim.test/*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'Sukses',
                ],
                'response' => [
                    'noSuratKontrol' => '0301R0111125K000002',
                ],
            ]),
        ]);

        $result = (new RencanaKontrolRepository)->findByControlLetterNumber(
            ' 0301R0111125K000002 '
        );

        $this->assertSame(
            '0301R0111125K000002',
            $result['response']['noSuratKontrol']
        );

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://vclaim.test/root/RencanaKontrol/noSuratKontrol/0301R0111125K000002'
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded');
        });
    }
}
