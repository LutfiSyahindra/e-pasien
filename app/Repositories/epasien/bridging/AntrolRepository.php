<?php

namespace App\Repositories\epasien\bridging;

use App\Models\BpjsApiLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

class AntrolRepository
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addQueue(array $payload): array
    {
        return $this->request('antrean/add', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $payload): array
    {
        $requestId = (string) Str::uuid();
        $startedAt = hrtime(true);
        $timestamp = time();
        $httpCode = null;
        $metadata = null;
        $errorMessage = null;

        try {
            $response = Http::withHeaders($this->headers($timestamp))
                ->acceptJson()
                ->asJson()
                ->connectTimeout(max(1, (int) config('services.bpjs.antrol.connect_timeout', 10)))
                ->timeout(max(1, (int) config('services.bpjs.antrol.timeout', 30)))
                ->post($this->url($endpoint), $payload);
            $httpCode = $response->status();

            try {
                $result = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new RuntimeException('Antrol BPJS mengembalikan respons yang bukan JSON.');
            }

            if (! is_array($result)) {
                throw new RuntimeException('Struktur respons Antrol BPJS tidak valid.');
            }

            $metadata = $this->metadata($result);

            if (! $response->successful() && $metadata === null) {
                return $this->errorResponse(
                    $httpCode,
                    "Antrol BPJS mengembalikan HTTP {$httpCode}."
                );
            }

            return $result;
        } catch (Throwable $exception) {
            $errorMessage = $this->redact($exception->getMessage());
            $statusCode = $exception instanceof ConnectionException ? 504 : 500;
            $result = $this->errorResponse(
                $statusCode,
                $statusCode === 504
                    ? 'Tidak dapat terhubung ke layanan Antrol BPJS.'
                    : 'Terjadi kesalahan saat memproses respons Antrol BPJS.'
            );
            $metadata = $result['metadata'];

            Log::error('Permintaan BPJS Antrol gagal.', [
                'request_id' => $requestId,
                'endpoint' => $endpoint,
                'error' => $errorMessage,
            ]);

            return $result;
        } finally {
            $this->writeLog([
                'request_id' => $requestId,
                'service' => 'antrol',
                'endpoint' => $endpoint,
                'method' => 'POST',
                'request_payload' => $this->redactPayload($payload),
                'response_metadata' => $metadata,
                'http_code' => $httpCode,
                'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
                'error_message' => $errorMessage,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(int $timestamp): array
    {
        $consumerId = $this->credential('consumer_id');
        $secretKey = $this->credential('secret_key');

        return [
            'X-cons-id' => $consumerId,
            'X-timestamp' => (string) $timestamp,
            'X-signature' => base64_encode(
                hash_hmac('sha256', "{$consumerId}&{$timestamp}", $secretKey, true)
            ),
            'user_key' => $this->credential('antrol.user_key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    private function url(string $endpoint): string
    {
        return rtrim($this->credential('antrol.base_url'), '/').'/'.ltrim($endpoint, '/');
    }

    private function credential(string $key): string
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
    private function metadata(array $response): ?array
    {
        $metadata = $response['metadata'] ?? $response['metaData'] ?? null;

        return is_array($metadata) ? $metadata : null;
    }

    /**
     * @return array{metadata: array{code: int, message: string}}
     */
    private function errorResponse(int $code, string $message): array
    {
        return [
            'metadata' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redactPayload(array $payload): array
    {
        $redacted = $payload;

        foreach (['nomorkartu', 'nik', 'nohp', 'norm', 'nomorreferensi'] as $key) {
            if (array_key_exists($key, $redacted)) {
                $redacted[$key] = $this->mask((string) $redacted[$key]);
            }
        }

        foreach (['namapoli', 'namadokter', 'keterangan'] as $key) {
            if (array_key_exists($key, $redacted)) {
                $redacted[$key] = '[REDACTED]';
            }
        }

        return $redacted;
    }

    private function redact(string $value): string
    {
        return preg_replace_callback(
            '/\d{8,}/',
            fn (array $matches): string => $this->mask($matches[0]),
            $value
        ) ?? $value;
    }

    private function mask(string $value): string
    {
        return strlen($value) <= 4
            ? str_repeat('*', strlen($value))
            : str_repeat('*', strlen($value) - 4).substr($value, -4);
    }

    /**
     * Database logging is best-effort and must never break the BPJS request.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function writeLog(array $attributes): void
    {
        Log::info('Permintaan BPJS Antrol selesai.', [
            'request_id' => $attributes['request_id'],
            'endpoint' => $attributes['endpoint'],
            'http_code' => $attributes['http_code'],
            'duration_ms' => $attributes['duration_ms'],
            'metadata' => $attributes['response_metadata'],
        ]);

        if (! config('services.bpjs.antrol.database_logging', true)) {
            return;
        }

        try {
            BpjsApiLog::query()->create($attributes);
        } catch (Throwable $exception) {
            Log::warning('Log database BPJS Antrol gagal disimpan.', [
                'request_id' => $attributes['request_id'],
                'error' => $this->redact($exception->getMessage()),
            ]);
        }
    }
}
