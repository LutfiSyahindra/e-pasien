<?php

namespace Tests\Unit\Bpjs;

use App\Repositories\epasien\bridging\AntrolRepository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AntrolRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'services.bpjs.consumer_id' => 'consumer-test',
            'services.bpjs.secret_key' => 'secret-test',
            'services.bpjs.antrol.base_url' => 'https://antrol.test/root/',
            'services.bpjs.antrol.user_key' => 'antrol-user-key',
            'services.bpjs.antrol.connect_timeout' => 5,
            'services.bpjs.antrol.timeout' => 15,
            'services.bpjs.antrol.database_logging' => false,
        ]);

        Http::preventStrayRequests();
    }

    public function test_it_adds_queue_with_signed_antrol_headers(): void
    {
        Http::fake([
            'https://antrol.test/*' => Http::response([
                'metadata' => [
                    'code' => 200,
                    'message' => 'Ok',
                ],
            ]),
        ]);
        $payload = [
            'kodebooking' => '20260727000001',
            'nomorkartu' => '0001234567890',
            'nomorantrean' => 'ANA-001',
        ];

        $result = (new AntrolRepository)->addQueue($payload);

        $this->assertSame(200, $result['metadata']['code']);
        Http::assertSent(function (Request $request) use ($payload): bool {
            $timestamp = (int) $request->header('X-timestamp')[0];
            $expectedSignature = base64_encode(hash_hmac(
                'sha256',
                "consumer-test&{$timestamp}",
                'secret-test',
                true
            ));

            return $request->method() === 'POST'
                && $request->url() === 'https://antrol.test/root/antrean/add'
                && $request->hasHeader('X-cons-id', 'consumer-test')
                && $request->hasHeader('X-signature', $expectedSignature)
                && $request->hasHeader('user_key', 'antrol-user-key')
                && $request->data() === $payload;
        });
    }

    public function test_it_cancels_queue_with_booking_code_and_reason(): void
    {
        Http::fake([
            'https://antrol.test/*' => Http::response([
                'metadata' => [
                    'code' => 200,
                    'message' => 'Ok',
                ],
            ]),
        ]);

        $result = (new AntrolRepository)->cancelQueue(
            ' 20260727000001 ',
            ' Pendaftaran dibatalkan oleh pasien. '
        );

        $this->assertSame(200, $result['metadata']['code']);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://antrol.test/root/antrean/batal'
                && $request->data() === [
                    'kodebooking' => '20260727000001',
                    'keterangan' => 'Pendaftaran dibatalkan oleh pasien.',
                ];
        });
    }

    public function test_it_returns_stable_metadata_when_antrol_is_unreachable(): void
    {
        Http::fake([
            'https://antrol.test/*' => Http::failedConnection('Connection refused'),
        ]);

        $result = (new AntrolRepository)->addQueue([
            'kodebooking' => '20260727000001',
        ]);

        $this->assertSame(504, $result['metadata']['code']);
        $this->assertSame(
            'Tidak dapat terhubung ke layanan Antrol BPJS.',
            $result['metadata']['message']
        );
    }
}
