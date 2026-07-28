<?php

namespace App\Support\Bpjs;

use JsonException;
use RuntimeException;

final class VClaimResponseDecoder
{
    /**
     * Decrypt and decompress an encrypted VClaim response.
     *
     * @return array<string, mixed>
     */
    public function decode(
        string $encryptedResponse,
        string $consumerId,
        string $secretKey,
        int $timestamp
    ): array {
        $encryptionKey = $consumerId.$secretKey.$timestamp;
        $key = hash('sha256', $encryptionKey, true);
        $initializationVector = substr(hash('sha256', $encryptionKey, true), 0, 16);
        $ciphertext = base64_decode($encryptedResponse, true);

        if ($ciphertext === false) {
            throw new RuntimeException('Respons VClaim bukan Base64 yang valid.');
        }

        $decrypted = openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $initializationVector
        );

        if ($decrypted === false) {
            throw new RuntimeException('Respons VClaim gagal didekripsi.');
        }

        $decompressed = LzString::decompressFromEncodedURIComponent($decrypted);

        if ($decompressed === null) {
            throw new RuntimeException('Respons VClaim gagal didekompresi.');
        }

        try {
            $decoded = json_decode($decompressed, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Isi respons VClaim bukan JSON yang valid.', 0, $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Isi respons VClaim tidak berbentuk object atau array.');
        }

        return $decoded;
    }
}
