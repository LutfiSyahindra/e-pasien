<?php

namespace App\Services\epasien\Profile;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProfilePhotoService
{
    private const DIRECTORY = 'profile-photos';

    private const SIZE = 320;

    private const QUALITY = 82;

    private const MAX_DATA_URL_BYTES = 2097152;

    public function store(UploadedFile $photo, int|string $userId): string
    {
        return $this->storeOptimized($photo, $userId)
            ?? $photo->store(self::DIRECTORY, 'public');
    }

    public function storeDataUrl(string $dataUrl, int|string $userId): ?string
    {
        $decoded = $this->decodeDataUrl($dataUrl);

        if (! $decoded) {
            return null;
        }

        [$binary, $mime, $extension] = $decoded;
        $temporaryPath = tempnam(sys_get_temp_dir(), 'profile-photo-');

        if (! is_string($temporaryPath) || $temporaryPath === '') {
            return null;
        }

        if (file_put_contents($temporaryPath, $binary) === false) {
            @unlink($temporaryPath);

            return null;
        }

        $photo = new UploadedFile($temporaryPath, 'profile-photo.'.$extension, $mime, null, true);

        try {
            return $this->store($photo, $userId);
        } finally {
            @unlink($temporaryPath);
        }
    }

    public function delete(?string $path): void
    {
        $path = $this->storedPath($path);

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function storeOptimized(UploadedFile $photo, int|string $userId): ?string
    {
        $output = $this->outputFormat();

        if (! $output) {
            return null;
        }

        $sourcePath = $this->sourcePath($photo);
        $imageSize = @getimagesize($sourcePath);

        if (! is_array($imageSize) || ! isset($imageSize[0], $imageSize[1], $imageSize[2])) {
            return null;
        }

        $source = $this->createSourceImage($sourcePath, (int) $imageSize[2]);

        if (! $source) {
            return null;
        }

        $relativePath = null;

        try {
            $source = $this->applyJpegOrientation($source, $sourcePath, (int) $imageSize[2]);
            $target = $this->createAvatarCanvas($output);

            if (! $target) {
                return null;
            }

            [$srcX, $srcY, $srcWidth, $srcHeight] = $this->squareCrop($source);

            $copied = imagecopyresampled(
                $target,
                $source,
                0,
                0,
                $srcX,
                $srcY,
                self::SIZE,
                self::SIZE,
                $srcWidth,
                $srcHeight
            );

            if (! $copied) {
                imagedestroy($target);

                return null;
            }

            Storage::disk('public')->makeDirectory(self::DIRECTORY);

            $relativePath = self::DIRECTORY.'/'.((string) $userId).'-'.((string) Str::uuid()).'.'.$output;
            $absolutePath = Storage::disk('public')->path($relativePath);
            $saved = $output === 'webp'
                ? imagewebp($target, $absolutePath, self::QUALITY)
                : imagejpeg($target, $absolutePath, self::QUALITY);

            imagedestroy($target);

            if (! $saved || ! Storage::disk('public')->exists($relativePath) || Storage::disk('public')->size($relativePath) < 1) {
                Storage::disk('public')->delete($relativePath);

                return null;
            }

            return $relativePath;
        } catch (Throwable $exception) {
            if ($relativePath) {
                Storage::disk('public')->delete($relativePath);
            }

            Log::warning('Gagal mengoptimalkan foto profil.', [
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            imagedestroy($source);
        }
    }

    private function outputFormat(): ?string
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatetruecolor') || ! function_exists('imagecopyresampled')) {
            return null;
        }

        if (function_exists('imagewebp')) {
            return 'webp';
        }

        if (function_exists('imagejpeg')) {
            return 'jpg';
        }

        return null;
    }

    private function sourcePath(UploadedFile $photo): string
    {
        $path = $photo->getRealPath();

        return is_string($path) && $path !== '' ? $path : $photo->getPathname();
    }

    private function storedPath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            $urlPath = parse_url($path, PHP_URL_PATH);
            $path = is_string($urlPath) ? $urlPath : '';
        }

        $path = ltrim($path, '/');
        $path = Str::replaceStart('public/', '', $path);
        $path = Str::replaceStart('storage/', '', $path);

        return $path !== '' ? $path : null;
    }

    private function decodeDataUrl(string $dataUrl): ?array
    {
        $dataUrl = trim($dataUrl);

        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,([a-z0-9+\/=\s]+)$/i', $dataUrl, $matches)) {
            return null;
        }

        $payload = preg_replace('/\s+/', '', $matches[2]);

        if (! is_string($payload)) {
            return null;
        }

        $binary = base64_decode($payload, true);

        if ($binary === false || $binary === '' || strlen($binary) > self::MAX_DATA_URL_BYTES) {
            return null;
        }

        $imageSize = @getimagesizefromstring($binary);
        $mime = is_array($imageSize) ? (string) ($imageSize['mime'] ?? '') : '';

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        return [
            $binary,
            $mime,
            $mime === 'image/jpeg' ? 'jpg' : Str::after($mime, 'image/'),
        ];
    }

    private function createSourceImage(string $path, int $type): mixed
    {
        return match ($type) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
            IMAGETYPE_PNG => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        } ?: null;
    }

    private function applyJpegOrientation(mixed $image, string $path, int $type): mixed
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        if ($orientation === 3) {
            return $this->rotate($image, 180);
        }

        if ($orientation === 6) {
            return $this->rotate($image, -90);
        }

        if ($orientation === 8) {
            return $this->rotate($image, 90);
        }

        return $image;
    }

    private function rotate(mixed $image, int $angle): mixed
    {
        $rotated = imagerotate($image, $angle, 0);

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function createAvatarCanvas(string $output): mixed
    {
        $target = imagecreatetruecolor(self::SIZE, self::SIZE);

        if (! $target) {
            return null;
        }

        if ($output === 'webp') {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $background = imagecolorallocatealpha($target, 0, 0, 0, 127);
        } else {
            $background = imagecolorallocate($target, 255, 255, 255);
        }

        if ($background !== false) {
            imagefilledrectangle($target, 0, 0, self::SIZE, self::SIZE, $background);
        }

        return $target;
    }

    private function squareCrop(mixed $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);

        return [
            (int) floor(($width - $side) / 2),
            (int) floor(($height - $side) / 2),
            $side,
            $side,
        ];
    }
}
