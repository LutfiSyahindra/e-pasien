<?php

namespace App\Traits\Bpjs;

use App\Models\BpjsApiLog;
use App\Support\Bpjs\VClaimResponseDecoder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

trait VClaimTrait
{
    protected function baseUrl(string $type): string
    {
        $baseUrl = match (strtolower($type)) {
            'vclaim' => config('services.bpjs.vclaim.base_url'),
            default => throw new InvalidArgumentException("Unknown BPJS service type: {$type}"),
        };

        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            throw new RuntimeException("Base URL BPJS {$type} belum dikonfigurasi.");
        }

        return rtrim($baseUrl, '/');
    }

    protected function buildUrl(string $base, string $endpoint): string
    {
        return rtrim($base, '/').'/'.ltrim($endpoint, '/');
    }

    /**
     * @return array<string, string>
     */
    protected function generateVclaimHeaders(?int $timestamp = null): array
    {
        $consumerId = $this->vclaimCredential('consumer_id');
        $secretKey = $this->vclaimCredential('secret_key');
        $userKey = $this->vclaimCredential('vclaim.user_key');
        $timestamp ??= time();

        $signature = base64_encode(
            hash_hmac('sha256', "{$consumerId}&{$timestamp}", $secretKey, true)
        );

        return [
            'X-cons-id' => $consumerId,
            'X-timestamp' => (string) $timestamp,
            'X-signature' => $signature,
            'user_key' => $userKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Send a VClaim request and return the decoded BPJS response.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function bpjsRequestVclaim(
        string $endpoint,
        string $method = 'GET',
        array $payload = []
    ): array {
        $method = strtoupper($method);

        if (! in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
            throw new InvalidArgumentException("Unsupported VClaim HTTP method: {$method}");
        }

        $requestId = (string) Str::uuid();
        $startedAt = hrtime(true);
        $timestamp = time();
        $httpCode = null;
        $responseMetadata = null;
        $errorMessage = null;

        try {
            $url = $this->buildUrl($this->baseUrl('vclaim'), $endpoint);
            $headers = $this->generateVclaimHeaders($timestamp);

            $request = Http::withHeaders($headers)
                ->acceptJson()
                ->connectTimeout(max(1, (int) config('services.bpjs.vclaim.connect_timeout', 10)))
                ->timeout(max(1, (int) config('services.bpjs.vclaim.timeout', 30)));
            $request = $method === 'GET'
                ? $request->asForm()
                : $request->asJson();

            $options = $method === 'GET'
                ? ['query' => $payload]
                : ['json' => $payload];

            $response = $request->send($method, $url, $options);
            $httpCode = $response->status();
            $result = $this->parseVclaimResponse($response, $timestamp);
            $responseMetadata = $this->extractVclaimMetadata($result);

            if (! $response->successful() && $responseMetadata === null) {
                $result = $this->vclaimErrorResponse(
                    $httpCode,
                    "VClaim mengembalikan HTTP {$httpCode}."
                );
                $responseMetadata = $result['metaData'];
            }

            return $result;
        } catch (Throwable $exception) {
            $errorMessage = $this->redactSensitiveString($exception->getMessage());
            $statusCode = $exception instanceof ConnectionException
                ? 504
                : 500;
            $result = $this->vclaimErrorResponse(
                $statusCode,
                $statusCode === 504
                    ? 'Tidak dapat terhubung ke layanan VClaim BPJS.'
                    : 'Terjadi kesalahan saat memproses respons VClaim BPJS.'
            );
            $responseMetadata = $result['metaData'];

            Log::error('Permintaan BPJS VClaim gagal.', [
                'request_id' => $requestId,
                'endpoint' => $this->redactSensitiveString($endpoint),
                'method' => $method,
                'error' => $errorMessage,
            ]);

            return $result;
        } finally {
            $this->writeVclaimLog([
                'request_id' => $requestId,
                'service' => 'vclaim',
                'endpoint' => $this->redactSensitiveString($endpoint),
                'method' => $method,
                'request_payload' => $this->redactVclaimPayload($payload),
                'response_metadata' => $responseMetadata,
                'http_code' => $httpCode,
                'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
                'error_message' => $errorMessage,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getVclaim(string $endpoint, array $query = []): array
    {
        return $this->bpjsRequestVclaim($endpoint, 'GET', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function postVclaim(string $endpoint, array $payload): array
    {
        return $this->bpjsRequestVclaim($endpoint, 'POST', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function putVclaim(string $endpoint, array $payload): array
    {
        return $this->bpjsRequestVclaim($endpoint, 'PUT', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function deleteVclaim(string $endpoint, array $payload = []): array
    {
        return $this->bpjsRequestVclaim($endpoint, 'DELETE', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseVclaimResponse(Response $response, int $timestamp): array
    {
        try {
            $body = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('VClaim mengembalikan respons yang bukan JSON.');
        }

        if (! is_array($body)) {
            throw new RuntimeException('Struktur respons VClaim tidak valid.');
        }

        if (! isset($body['response']) || ! is_string($body['response']) || $body['response'] === '') {
            return $body;
        }

        $body['response'] = app(VClaimResponseDecoder::class)->decode(
            $body['response'],
            $this->vclaimCredential('consumer_id'),
            $this->vclaimCredential('secret_key'),
            $timestamp
        );

        return $body;
    }

    private function vclaimCredential(string $key): string
    {
        $value = config("services.bpjs.{$key}");

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException("Kredensial BPJS {$key} belum dikonfigurasi.");
        }

        return trim($value);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>|null
     */
    private function extractVclaimMetadata(array $response): ?array
    {
        $metadata = $response['metaData'] ?? $response['metadata'] ?? null;

        if (! is_array($metadata)) {
            return null;
        }

        return [
            'code' => $metadata['code'] ?? null,
            'message' => isset($metadata['message'])
                ? $this->redactSensitiveString((string) $metadata['message'])
                : null,
        ];
    }

    /**
     * @return array{metaData: array{code: int, message: string}}
     */
    private function vclaimErrorResponse(int $code, string $message): array
    {
        return [
            'metaData' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redactVclaimPayload(array $payload): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $redacted[$key] = $this->redactVclaimPayload($value);

                continue;
            }

            if (preg_match('/nama|alamat|diagnosa|catatan|user/i', (string) $key)) {
                $redacted[$key] = '[REDACTED]';

                continue;
            }

            if (preg_match('/nik|kartu|peserta|no.?mr|no.?sep|surat.?kontrol|rujukan|telepon|telp/i', (string) $key)) {
                $redacted[$key] = is_scalar($value) || $value === null
                    ? $this->maskIdentifier((string) $value)
                    : '[REDACTED]';

                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    private function redactSensitiveString(string $value): string
    {
        $value = preg_replace_callback(
            '/(?:(?<=\/)|(?<==))(?=[A-Za-z0-9-]{8,}(?:[\/?&]|$))(?=[A-Za-z0-9-]*\d)[A-Za-z0-9-]{8,}/',
            fn (array $matches): string => $this->maskIdentifier($matches[0]),
            $value
        ) ?? $value;

        return preg_replace_callback(
            '/\d{8,}/',
            fn (array $matches): string => $this->maskIdentifier($matches[0]),
            $value
        ) ?? $value;
    }

    private function maskIdentifier(string $value): string
    {
        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).substr($value, -4);
    }

    /**
     * Database logging is best-effort and must never break the BPJS request.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function writeVclaimLog(array $attributes): void
    {
        Log::info('Permintaan BPJS VClaim selesai.', [
            'request_id' => $attributes['request_id'],
            'endpoint' => $attributes['endpoint'],
            'method' => $attributes['method'],
            'http_code' => $attributes['http_code'],
            'duration_ms' => $attributes['duration_ms'],
            'metadata' => $attributes['response_metadata'],
        ]);

        if (! config('services.bpjs.vclaim.database_logging', true)) {
            return;
        }

        try {
            BpjsApiLog::query()->create($attributes);
        } catch (Throwable $exception) {
            Log::warning('Log database BPJS VClaim gagal disimpan.', [
                'request_id' => $attributes['request_id'],
                'error' => $this->redactSensitiveString($exception->getMessage()),
            ]);
        }
    }
}
