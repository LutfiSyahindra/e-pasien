<?php

namespace Tests\Unit\Bpjs;

use App\Support\Bpjs\LzString;
use App\Support\Bpjs\VClaimResponseDecoder;
use App\Traits\Bpjs\VClaimTrait;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VClaimTraitTest extends TestCase
{
    private const COMPRESSED_RESPONSE = 'N4IgzgrgTghgLgaQPYDs5SQGxALgNqgpIDK08yaG2OIADAIwBMAzAEq20DsXjAbAh0H0QAGhAoYAWxi4QABQCCxAJIBRAHIACAKoApZaJAAHLAEsAKhABWEGClnL15kAF8Aui6A';

    private VClaimTestClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'services.bpjs.consumer_id' => 'consumer-test',
            'services.bpjs.secret_key' => 'secret-test',
            'services.bpjs.vclaim.base_url' => 'https://vclaim.test/root/',
            'services.bpjs.vclaim.user_key' => 'user-key-test',
            'services.bpjs.vclaim.connect_timeout' => 5,
            'services.bpjs.vclaim.timeout' => 15,
            'services.bpjs.vclaim.database_logging' => false,
        ]);

        Http::preventStrayRequests();
        $this->client = new VClaimTestClient;
    }

    public function test_it_generates_the_expected_vclaim_signature(): void
    {
        $timestamp = 1_722_000_000;
        $headers = $this->client->headers($timestamp);

        $this->assertSame('consumer-test', $headers['X-cons-id']);
        $this->assertSame((string) $timestamp, $headers['X-timestamp']);
        $this->assertSame('user-key-test', $headers['user_key']);
        $this->assertSame(
            base64_encode(hash_hmac(
                'sha256',
                "consumer-test&{$timestamp}",
                'secret-test',
                true
            )),
            $headers['X-signature']
        );
    }

    public function test_it_sends_get_payload_as_query_parameters(): void
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

        $result = $this->client->get('RencanaKontrol/ListRencanaKontrol', [
            'bulan' => '07',
            'tahun' => '2026',
        ]);

        $this->assertSame('201', $result['metaData']['code']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://vclaim.test/root/RencanaKontrol/ListRencanaKontrol?bulan=07&tahun=2026'
                && $request->hasHeader('X-cons-id', 'consumer-test')
                && $request->hasHeader('user_key', 'user-key-test')
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded');
        });
    }

    public function test_it_decrypts_and_decompresses_a_vclaim_response(): void
    {
        Http::fake(function (Request $request) {
            $timestamp = (int) $request->header('X-timestamp')[0];
            $encryptionKey = 'consumer-test'.'secret-test'.$timestamp;
            $key = hash('sha256', $encryptionKey, true);
            $initializationVector = substr(hash('sha256', $encryptionKey, true), 0, 16);
            $encrypted = openssl_encrypt(
                self::COMPRESSED_RESPONSE,
                'AES-256-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $initializationVector
            );

            return Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => base64_encode($encrypted),
            ]);
        });

        $result = $this->client->get('RencanaKontrol/ListRencanaKontrol');

        $this->assertSame('200', $result['metaData']['code']);
        $this->assertSame(
            '0123R0070726K000001',
            $result['response']['suratKontrol'][0]['noSuratKontrol']
        );
        $this->assertSame('PASIEN UJI', $result['response']['suratKontrol'][0]['nama']);
    }

    public function test_it_returns_a_stable_error_when_bpjs_cannot_be_reached(): void
    {
        Http::fake([
            'https://vclaim.test/*' => Http::failedConnection('Connection refused'),
        ]);

        $result = $this->client->get('Peserta/nokartu/0000000000001');

        $this->assertSame(504, $result['metaData']['code']);
        $this->assertSame(
            'Tidak dapat terhubung ke layanan VClaim BPJS.',
            $result['metaData']['message']
        );
    }

    public function test_lz_string_fixture_matches_the_official_javascript_implementation(): void
    {
        $this->assertSame(
            '{"suratKontrol":[{"noSuratKontrol":"0123R0070726K000001","nama":"PASIEN UJI","poliTujuan":"INT"}]}',
            LzString::decompressFromEncodedURIComponent(self::COMPRESSED_RESPONSE)
        );
    }

    public function test_decoder_rejects_an_invalid_encrypted_response(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Respons VClaim bukan Base64 yang valid.');

        (new VClaimResponseDecoder)->decode(
            'not-base64!',
            'consumer-test',
            'secret-test',
            1_722_000_000
        );
    }

    public function test_it_redacts_patient_identifiers_and_clinical_data_from_logs(): void
    {
        $this->assertSame(
            'RencanaKontrol/noSuratKontrol/***************0001',
            $this->client->redactText(
                'RencanaKontrol/noSuratKontrol/0123R0070726K000001'
            )
        );
        $this->assertSame([
            'noKartu' => '********6789',
            'nama' => '[REDACTED]',
            'catatan' => '[REDACTED]',
            'filter' => 2,
        ], $this->client->redactPayload([
            'noKartu' => '000123456789',
            'nama' => 'PASIEN UJI',
            'catatan' => 'Kontrol penyakit kronis',
            'filter' => 2,
        ]));
    }
}

final class VClaimTestClient
{
    use VClaimTrait;

    /**
     * @return array<string, string>
     */
    public function headers(int $timestamp): array
    {
        return $this->generateVclaimHeaders($timestamp);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->getVclaim($endpoint, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function redactPayload(array $payload): array
    {
        return $this->redactVclaimPayload($payload);
    }

    public function redactText(string $value): string
    {
        return $this->redactSensitiveString($value);
    }
}
