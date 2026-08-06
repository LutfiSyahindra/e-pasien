<?php

namespace App\Services\epasien\settings;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DoctorPhotoStorageService
{
    private const DIRECTORY = 'doctor-photos';

    private const SIZE = 640;

    private const QUALITY = 86;

    private const MAX_BYTES = 2097152;

    public function storeUploaded(UploadedFile $photo, string $doctorCode): ?string
    {
        $sourcePath = $photo->getRealPath() ?: $photo->getPathname();
        $source = $this->createSourceFromPath($sourcePath);

        if ($source) {
            try {
                return $this->storeOptimizedSource($source, $doctorCode);
            } finally {
                imagedestroy($source);
            }
        }

        $extension = strtolower($photo->extension() ?: $photo->getClientOriginalExtension());

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        return $photo->storeAs(
            self::DIRECTORY,
            $this->filename($doctorCode, $extension === 'jpeg' ? 'jpg' : $extension),
            'public'
        );
    }

    public function storeDataUrl(string $dataUrl, string $doctorCode): ?string
    {
        $decoded = $this->decodeDataUrl($dataUrl);

        if (! $decoded) {
            return null;
        }

        [$binary, $extension] = $decoded;
        $source = $this->canOptimize() ? @imagecreatefromstring($binary) : false;

        if ($source) {
            try {
                return $this->storeOptimizedSource($source, $doctorCode);
            } finally {
                imagedestroy($source);
            }
        }

        $path = self::DIRECTORY.'/'.$this->filename($doctorCode, $extension);

        return Storage::disk('public')->put($path, $binary) ? $path : null;
    }

    public function delete(?string $path): void
    {
        $path = trim((string) $path);

        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }

    private function storeOptimizedSource(mixed $source, string $doctorCode): ?string
    {
        if (! $this->canOptimize()) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width < 1 || $height < 1) {
            return null;
        }

        $side = min($width, $height);
        $sourceX = (int) floor(($width - $side) / 2);
        $sourceY = (int) floor(($height - $side) / 2);
        $target = imagecreatetruecolor(self::SIZE, self::SIZE);

        if (! $target) {
            return null;
        }

        $relativePath = null;

        try {
            $background = imagecolorallocate($target, 255, 255, 255);
            imagefill($target, 0, 0, $background);

            if (! imagecopyresampled(
                $target,
                $source,
                0,
                0,
                $sourceX,
                $sourceY,
                self::SIZE,
                self::SIZE,
                $side,
                $side
            )) {
                return null;
            }

            $extension = function_exists('imagewebp') ? 'webp' : 'jpg';
            $relativePath = self::DIRECTORY.'/'.$this->filename($doctorCode, $extension);
            Storage::disk('public')->makeDirectory(self::DIRECTORY);
            $absolutePath = Storage::disk('public')->path($relativePath);
            $saved = $extension === 'webp'
                ? imagewebp($target, $absolutePath, self::QUALITY)
                : imagejpeg($target, $absolutePath, self::QUALITY);

            if (! $saved || ! Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);

                return null;
            }

            return $relativePath;
        } catch (Throwable $exception) {
            if ($relativePath) {
                Storage::disk('public')->delete($relativePath);
            }

            Log::warning('Gagal memproses foto dokter.', [
                'doctor_code' => $doctorCode,
                'message' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            imagedestroy($target);
        }
    }

    private function decodeDataUrl(string $dataUrl): ?array
    {
        $dataUrl = trim($dataUrl);

        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,([a-z0-9+\/=\s]+)$/i', $dataUrl, $matches)) {
            return null;
        }

        $binary = base64_decode(preg_replace('/\s+/', '', $matches[2]) ?? '', true);

        if (! is_string($binary) || $binary === '' || strlen($binary) > self::MAX_BYTES) {
            return null;
        }

        $imageInfo = @getimagesizefromstring($binary);
        $mime = is_array($imageInfo) ? ($imageInfo['mime'] ?? null) : null;
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (! isset($extensions[$mime])) {
            return null;
        }

        return [$binary, $extensions[$mime]];
    }

    private function createSourceFromPath(string $path): mixed
    {
        if (! $this->canOptimize() || $path === '' || ! is_file($path)) {
            return false;
        }

        $binary = @file_get_contents($path);

        return is_string($binary) ? @imagecreatefromstring($binary) : false;
    }

    private function canOptimize(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled')
            && (function_exists('imagewebp') || function_exists('imagejpeg'));
    }

    private function filename(string $doctorCode, string $extension): string
    {
        $identifier = Str::slug($doctorCode);
        $identifier = $identifier !== '' ? $identifier : substr(sha1($doctorCode), 0, 12);

        return $identifier.'-'.Str::uuid().'.'.$extension;
    }
}
