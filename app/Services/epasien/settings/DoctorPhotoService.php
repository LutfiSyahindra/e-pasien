<?php

namespace App\Services\epasien\settings;

use App\Models\DoctorPhoto;
use App\Repositories\epasien\settings\DoctorDirectoryRepository;
use App\Repositories\epasien\settings\DoctorPhotoRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Throwable;

class DoctorPhotoService
{
    public function __construct(
        private readonly DoctorDirectoryRepository $doctorDirectoryRepository,
        private readonly DoctorPhotoRepository $doctorPhotoRepository,
        private readonly DoctorPhotoStorageService $doctorPhotoStorageService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function page(?string $search, int $perPage = 12): array
    {
        $search = trim((string) $search);
        $doctors = $this->doctorDirectoryRepository->paginate(
            $search !== '' ? $search : null,
            max(8, min($perPage, 24))
        );
        $photoUrls = $this->doctorPhotoRepository->urlsForCodes(
            $doctors->getCollection()->pluck('kd_dokter')->all()
        );

        $doctors->setCollection($doctors->getCollection()->map(function (object $doctor) use ($photoUrls): array {
            $doctorCode = trim((string) ($doctor->kd_dokter ?? ''));
            $doctorName = trim((string) ($doctor->nm_dokter ?? ''));

            return [
                'doctor_code' => $doctorCode,
                'doctor_name' => $doctorName !== '' ? $doctorName : '-',
                'doctor_initials' => $this->initials($doctorName),
                'gender' => ($doctor->jk ?? null) === 'P' ? 'Perempuan' : 'Laki-laki',
                'photo_url' => $photoUrls[$doctorCode] ?? null,
            ];
        }));

        return [
            'search' => $search,
            'doctors' => $doctors,
            'configuredPhotos' => count($photoUrls),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyPage(?string $search): array
    {
        return [
            'search' => trim((string) $search),
            'doctors' => new LengthAwarePaginator(
                [],
                0,
                12,
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            ),
            'configuredPhotos' => 0,
        ];
    }

    public function update(
        string $doctorCode,
        ?UploadedFile $photo,
        ?string $croppedPhoto,
        ?int $uploadedBy
    ): DoctorPhoto {
        $doctorCode = trim($doctorCode);

        if ($this->doctorDirectoryRepository->findActive($doctorCode) === null) {
            throw ValidationException::withMessages([
                'doctor_code' => 'Dokter tidak ditemukan atau sudah tidak aktif.',
            ]);
        }

        $croppedPhoto = trim((string) $croppedPhoto);

        if ($croppedPhoto !== '' && strlen($croppedPhoto) > 3145728) {
            throw ValidationException::withMessages([
                'doctor_photo_cropped' => 'Hasil crop foto dokter maksimal 2 MB.',
            ]);
        }

        $newPath = $croppedPhoto !== ''
            ? $this->doctorPhotoStorageService->storeDataUrl($croppedPhoto, $doctorCode)
            : ($photo ? $this->doctorPhotoStorageService->storeUploaded($photo, $doctorCode) : null);

        if (! $newPath) {
            throw ValidationException::withMessages([
                'doctor_photo' => 'Foto dokter tidak valid atau gagal diproses.',
            ]);
        }

        $oldPhoto = $this->doctorPhotoRepository->find($doctorCode);

        try {
            $savedPhoto = $this->doctorPhotoRepository->save($doctorCode, $newPath, $uploadedBy);
        } catch (Throwable $exception) {
            $this->doctorPhotoStorageService->delete($newPath);

            throw $exception;
        }

        if ($oldPhoto && $oldPhoto->image_path !== $newPath) {
            $this->doctorPhotoStorageService->delete($oldPhoto->image_path);
        }

        return $savedPhoto;
    }

    public function delete(string $doctorCode): void
    {
        $photo = $this->doctorPhotoRepository->find(trim($doctorCode));

        if (! $photo) {
            return;
        }

        $path = $photo->image_path;
        $this->doctorPhotoRepository->delete($photo);
        $this->doctorPhotoStorageService->delete($path);
    }

    private function initials(string $name): string
    {
        $name = preg_replace('/\bdr\.?\b/iu', '', $name) ?? $name;
        preg_match_all('/[\p{L}]+/u', $name, $matches);
        $words = array_slice($matches[0] ?? [], 0, 2);

        if ($words === []) {
            return 'DR';
        }

        return mb_strtoupper(implode('', array_map(
            fn (string $word): string => mb_substr($word, 0, 1),
            $words
        )));
    }
}
