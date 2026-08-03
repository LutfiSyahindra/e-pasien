<?php

namespace App\Services\epasien\menu;

use App\Jobs\DispatchPromotionNotifications;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

class PromotionNotificationService
{
    public function dispatchIfDue(Promotion $promotion): bool
    {
        $claimed = DB::transaction(function () use ($promotion): ?Promotion {
            $candidate = Promotion::query()->lockForUpdate()->find($promotion->getKey());

            if (! $candidate
                || $candidate->notified_at
                || $candidate->status !== Promotion::STATUS_PUBLISHED
                || $candidate->starts_at->isFuture()
                || $candidate->ends_at->lte(now())) {
                return null;
            }

            $candidate->forceFill(['notified_at' => now()])->save();

            return $candidate;
        });

        if (! $claimed) {
            return false;
        }

        DispatchPromotionNotifications::dispatch($claimed->getKey())->afterCommit();

        return true;
    }

    public function dispatchDue(): int
    {
        $count = 0;

        Promotion::query()
            ->where('status', Promotion::STATUS_PUBLISHED)
            ->whereNull('notified_at')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->orderBy('id')
            ->each(function (Promotion $promotion) use (&$count): void {
                if ($this->dispatchIfDue($promotion)) {
                    $count++;
                }
            });

        return $count;
    }
}
