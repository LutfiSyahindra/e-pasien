<?php

namespace App\Repositories\epasien\settings;

use App\Models\DoctorPhoto;
use Illuminate\Support\Collection;

class DoctorPhotoRepository
{
    /**
     * @param  array<int, string>  $doctorCodes
     * @return array<string, string>
     */
    public function urlsForCodes(array $doctorCodes): array
    {
        $doctorCodes = collect($doctorCodes)
            ->map(fn (mixed $code): string => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        if ($doctorCodes->isEmpty()) {
            return [];
        }

        return DoctorPhoto::query()
            ->whereIn('doctor_code', $doctorCodes)
            ->get()
            ->mapWithKeys(fn (DoctorPhoto $photo): array => [
                $photo->doctor_code => $photo->image_url,
            ])
            ->all();
    }

    public function find(string $doctorCode): ?DoctorPhoto
    {
        return DoctorPhoto::query()->where('doctor_code', $doctorCode)->first();
    }

    public function featured(int $limit = 12): Collection
    {
        return DoctorPhoto::query()
            ->latest('updated_at')
            ->limit(max(1, min($limit, 24)))
            ->get();
    }

    public function save(string $doctorCode, string $imagePath, ?int $uploadedBy): DoctorPhoto
    {
        return DoctorPhoto::query()->updateOrCreate(
            ['doctor_code' => $doctorCode],
            [
                'image_path' => $imagePath,
                'uploaded_by' => $uploadedBy,
            ]
        );
    }

    public function delete(DoctorPhoto $photo): void
    {
        $photo->delete();
    }
}
