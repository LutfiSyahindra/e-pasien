<?php

namespace App\Jobs;

use App\Models\Promotion;
use App\Notifications\PromotionPublishedNotification;
use App\Services\epasien\menu\PromotionNotificationRecipientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class DispatchPromotionNotifications implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public readonly int $promotionId) {}

    public function handle(PromotionNotificationRecipientService $recipients): void
    {
        $promotion = Promotion::query()->find($this->promotionId);

        if (! $promotion) {
            return;
        }

        $notification = PromotionPublishedNotification::fromPromotion($promotion);

        $recipients->query()
            ->chunkById(250, function ($patients) use ($notification): void {
                Notification::send($patients, $notification);
            });
    }
}
