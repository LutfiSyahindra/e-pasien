<?php

namespace App\Services\epasien\menu;

use App\Models\Promotion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PromotionService
{
    public function __construct(
        private readonly PromotionNotificationService $notificationService,
    ) {}

    public function create(array $data, User $creator): Promotion
    {
        $imagePath = $this->storeImage($data['image']);

        try {
            $promotion = DB::transaction(function () use ($data, $creator, $imagePath): Promotion {
                $schedule = $this->schedule($data);

                return Promotion::query()->create([
                    'creator_id' => $creator->getKey(),
                    'title' => trim($data['title']),
                    'caption' => trim($data['caption']),
                    'image_path' => $imagePath,
                    'duration_value' => $data['duration_value'],
                    'duration_unit' => $data['duration_unit'],
                    'starts_at' => $schedule['starts_at'],
                    'ends_at' => $schedule['ends_at'],
                    'status' => $data['status'],
                    'published_at' => $data['status'] === Promotion::STATUS_PUBLISHED ? now() : null,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($imagePath);
            throw $exception;
        }

        $this->notificationService->dispatchIfDue($promotion);

        return $promotion;
    }

    public function update(Promotion $promotion, array $data): Promotion
    {
        $oldImagePath = $promotion->image_path;
        $newImagePath = isset($data['image']) && $data['image'] instanceof UploadedFile
            ? $this->storeImage($data['image'])
            : null;

        try {
            DB::transaction(function () use ($promotion, $data, $newImagePath): void {
                $schedule = $this->schedule($data);
                $wasPublished = $promotion->status === Promotion::STATUS_PUBLISHED;

                $promotion->fill([
                    'title' => trim($data['title']),
                    'caption' => trim($data['caption']),
                    'duration_value' => $data['duration_value'],
                    'duration_unit' => $data['duration_unit'],
                    'starts_at' => $schedule['starts_at'],
                    'ends_at' => $schedule['ends_at'],
                    'status' => $data['status'],
                    'published_at' => ! $wasPublished && $data['status'] === Promotion::STATUS_PUBLISHED
                        ? now()
                        : $promotion->published_at,
                ]);

                if ($newImagePath) {
                    $promotion->image_path = $newImagePath;
                }

                $promotion->save();
            });
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            throw $exception;
        }

        if ($newImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        $this->notificationService->dispatchIfDue($promotion->refresh());

        return $promotion;
    }

    public function archive(Promotion $promotion): Promotion
    {
        $promotion->update(['status' => Promotion::STATUS_ARCHIVED]);

        return $promotion;
    }

    public function restore(Promotion $promotion): Promotion
    {
        $promotion->update(['status' => Promotion::STATUS_DRAFT]);

        return $promotion;
    }

    public function delete(Promotion $promotion): void
    {
        $imagePath = $promotion->image_path;
        $promotion->delete();
        Storage::disk('public')->delete($imagePath);
    }

    private function storeImage(UploadedFile $image): string
    {
        return $image->store('promotions/'.now()->format('Y/m'), 'public');
    }

    private function schedule(array $data): array
    {
        $startsAt = CarbonImmutable::parse($data['starts_at']);
        $value = (int) $data['duration_value'];

        $endsAt = match ($data['duration_unit']) {
            'hour' => $startsAt->addHours($value),
            'day' => $startsAt->addDays($value),
            'month' => $startsAt->addMonthsNoOverflow($value),
            'year' => $startsAt->addYearsNoOverflow($value),
        };

        return ['starts_at' => $startsAt, 'ends_at' => $endsAt];
    }
}
