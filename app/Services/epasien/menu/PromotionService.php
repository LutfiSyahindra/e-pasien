<?php

namespace App\Services\epasien\menu;

use App\Models\Promotion;
use App\Models\PromotionConfiguration;
use App\Models\User;
use App\Notifications\PromotionPublishedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
                    'category' => $data['category'],
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
                    'category' => $data['category'],
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
        $promotionId = $promotion->getKey();
        $imagePath = $promotion->image_path;
        $promotion->delete();
        DatabaseNotification::query()
            ->where('type', PromotionPublishedNotification::class)
            ->where('data->promotion_id', $promotionId)
            ->delete();
        Storage::disk('public')->delete($imagePath);
    }

    public function deleteExpired(?PromotionConfiguration $configuration = null): int
    {
        $configuration ??= PromotionConfiguration::current();

        if (! $configuration->auto_delete_enabled) {
            return 0;
        }

        $deleted = 0;
        $cutoff = $configuration->deletionCutoff();

        Promotion::query()
            ->whereIn('status', [Promotion::STATUS_PUBLISHED, Promotion::STATUS_ARCHIVED])
            ->where('ends_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($promotions) use (&$deleted): void {
                $promotionIds = $promotions->modelKeys();
                $imagePaths = $promotions->pluck('image_path')->filter()->all();

                DB::transaction(function () use ($promotionIds): void {
                    Promotion::query()->whereKey($promotionIds)->delete();
                    DatabaseNotification::query()
                        ->where('type', PromotionPublishedNotification::class)
                        ->whereIn('data->promotion_id', $promotionIds)
                        ->delete();
                });

                Storage::disk('public')->delete($imagePaths);
                $deleted += count($promotionIds);
            });

        return $deleted;
    }

    private function storeImage(UploadedFile $image): string
    {
        $fallback = fn (): string => $image->store('promotions/'.now()->format('Y/m'), 'public');

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return $fallback();
        }

        $contents = @file_get_contents($image->getRealPath());
        $source = is_string($contents) ? @imagecreatefromstring($contents) : false;

        if (! $source) {
            return $fallback();
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, 1600 / $sourceWidth, 1200 / $sourceHeight);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        $encoded = @imagewebp($target, null, 82);
        $optimizedContents = ob_get_clean();
        imagedestroy($target);
        imagedestroy($source);

        if (! $encoded || ! is_string($optimizedContents) || $optimizedContents === '') {
            return $fallback();
        }

        $path = 'promotions/'.now()->format('Y/m').'/'.Str::uuid().'.webp';

        return Storage::disk('public')->put($path, $optimizedContents)
            ? $path
            : $fallback();
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
