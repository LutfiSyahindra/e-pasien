<?php

namespace App\Jobs;

use App\Models\Promotion;
use App\Models\User;
use App\Notifications\PromotionPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class DispatchPromotionNotifications implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public readonly int $promotionId) {}

    public function handle(): void
    {
        $promotion = Promotion::query()->find($this->promotionId);

        if (! $promotion) {
            return;
        }

        $roleNames = array_unique([
            config('access-control.patient_role', 'Patient'),
            ...config('access-control.patient_role_aliases', ['Pasien']),
        ]);
        $notification = PromotionPublishedNotification::fromPromotion($promotion);

        User::query()
            ->where(function ($query): void {
                $query->where('status', true)->orWhereNull('status');
            })
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $roleNames))
            ->chunkById(250, function ($patients) use ($notification): void {
                Notification::send($patients, $notification);
            });
    }
}
