<?php

namespace App\Services\LandingPage;

use App\Models\DoctorPhoto;
use App\Repositories\epasien\settings\DoctorDirectoryRepository;
use App\Repositories\epasien\settings\DoctorPhotoRepository;

class LandingDoctorService
{
    public function __construct(
        private readonly DoctorDirectoryRepository $doctorDirectoryRepository,
        private readonly DoctorPhotoRepository $doctorPhotoRepository
    ) {}

    /**
     * @return array<int, array<string, string>>
     */
    public function featured(int $limit = 12): array
    {
        $photos = $this->doctorPhotoRepository->featured($limit);

        if ($photos->isEmpty()) {
            return [];
        }

        $doctors = $this->doctorDirectoryRepository
            ->activeByCodes($photos->pluck('doctor_code')->all())
            ->keyBy(fn (object $doctor): string => trim((string) ($doctor->kd_dokter ?? '')));

        return $photos
            ->map(function (DoctorPhoto $photo) use ($doctors): ?array {
                $doctor = $doctors->get($photo->doctor_code);

                if (! $doctor) {
                    return null;
                }

                return [
                    'doctor_code' => trim((string) $doctor->kd_dokter),
                    'doctor_name' => trim((string) $doctor->nm_dokter),
                    'photo_url' => $photo->image_url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
