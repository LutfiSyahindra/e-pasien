<?php

namespace App\Services\epasien\menu;

use App\Models\PromotionNotificationUserConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PromotionNotificationRecipientService
{
    public function query(): Builder
    {
        return User::query()
            ->where(function (Builder $query): void {
                $query->where('status', true)->orWhereNull('status');
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('roles', function (Builder $roles): void {
                        $roles
                            ->where('roles.guard_name', 'web')
                            ->where('roles.promotion_notifications_enabled', true);
                    })
                    ->orWhereIn(
                        $query->getModel()->getQualifiedKeyName(),
                        PromotionNotificationUserConfiguration::query()->select('user_id'),
                    );
            });
    }

    public function count(): int
    {
        return $this->query()->count();
    }
}
