<?php

namespace App\Services\epasien\settings;

use App\Models\PromotionNotificationUserConfiguration;
use App\Models\RegistrationRoleConfiguration;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleConfigurationService
{
    public function roles(): Collection
    {
        $registrationRoleIds = RegistrationRoleConfiguration::query()
            ->pluck('role_id');

        return Role::query()
            ->where('guard_name', 'web')
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->each(function (Role $role) use ($registrationRoleIds): void {
                $role->setAttribute(
                    'registration_enabled',
                    $registrationRoleIds->contains($role->id)
                );
                $role->setAttribute(
                    'email_onboarding_enabled',
                    (bool) $role->email_onboarding_enabled
                );
                $role->setAttribute(
                    'promotion_notifications_enabled',
                    (bool) $role->promotion_notifications_enabled
                );
            });
    }

    public function promotionNotificationUsers(?array $userIds = null): Collection
    {
        return User::query()
            ->with('roles:id,name')
            ->when(
                $userIds === null,
                fn ($query) => $query->whereIn(
                    $query->getModel()->getQualifiedKeyName(),
                    PromotionNotificationUserConfiguration::query()->select('user_id'),
                ),
                fn ($query) => $query->whereKey($this->normalizeIds($userIds ?? [])),
            )
            ->orderBy('name')
            ->get();
    }

    public function isRegistrationEnabled(User $user): bool
    {
        $roleIds = $this->roleIdsFor($user);

        return $roleIds->isNotEmpty()
            && RegistrationRoleConfiguration::query()
                ->whereIn('role_id', $roleIds)
                ->exists();
    }

    public function syncRegistration(array $roleIds, User $configuredBy): void
    {
        $roleIds = $this->normalizeIds($roleIds);

        DB::connection(config('database.default'))->transaction(
            fn () => $this->replaceRegistrationRoles($roleIds, $configuredBy)
        );
    }

    public function sync(
        array $registrationRoleIds,
        array $emailOnboardingRoleIds,
        array $promotionNotificationRoleIds,
        array $promotionNotificationUserIds,
        User $configuredBy
    ): void {
        $registrationRoleIds = $this->normalizeIds($registrationRoleIds);
        $emailOnboardingRoleIds = $this->normalizeIds($emailOnboardingRoleIds);
        $promotionNotificationRoleIds = $this->normalizeIds($promotionNotificationRoleIds);
        $promotionNotificationUserIds = $this->normalizeIds($promotionNotificationUserIds);

        DB::connection(config('database.default'))->transaction(function () use (
            $registrationRoleIds,
            $emailOnboardingRoleIds,
            $promotionNotificationRoleIds,
            $promotionNotificationUserIds,
            $configuredBy
        ): void {
            $this->replaceRegistrationRoles($registrationRoleIds, $configuredBy);

            Role::query()
                ->where('guard_name', 'web')
                ->update(['email_onboarding_enabled' => false]);

            if ($emailOnboardingRoleIds->isNotEmpty()) {
                Role::query()
                    ->where('guard_name', 'web')
                    ->whereIn('id', $emailOnboardingRoleIds)
                    ->update(['email_onboarding_enabled' => true]);
            }

            Role::query()
                ->where('guard_name', 'web')
                ->update(['promotion_notifications_enabled' => false]);

            if ($promotionNotificationRoleIds->isNotEmpty()) {
                Role::query()
                    ->where('guard_name', 'web')
                    ->whereIn('id', $promotionNotificationRoleIds)
                    ->update(['promotion_notifications_enabled' => true]);
            }

            $this->replacePromotionNotificationUsers(
                $promotionNotificationUserIds,
                $configuredBy
            );
        });
    }

    private function roleIdsFor(User $user): Collection
    {
        if (! $user->exists && ! $user->relationLoaded('roles')) {
            return collect();
        }

        return $user->relationLoaded('roles')
            ? $user->roles->pluck('id')
            : $user->roles()->pluck('roles.id');
    }

    private function normalizeIds(array $ids): Collection
    {
        return collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    private function replacePromotionNotificationUsers(Collection $userIds, User $configuredBy): void
    {
        PromotionNotificationUserConfiguration::query()
            ->when(
                $userIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('user_id', $userIds),
            )
            ->delete();

        if ($userIds->isNotEmpty()) {
            $now = now();
            PromotionNotificationUserConfiguration::query()->upsert(
                $userIds->map(fn (int $userId): array => [
                    'user_id' => $userId,
                    'configured_by' => $configuredBy->getKey(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
                ['user_id'],
                ['configured_by', 'updated_at'],
            );
        }
    }

    private function replaceRegistrationRoles(Collection $roleIds, User $configuredBy): void
    {
        RegistrationRoleConfiguration::query()
            ->when(
                $roleIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('role_id', $roleIds),
            )
            ->delete();

        foreach ($roleIds as $roleId) {
            RegistrationRoleConfiguration::query()->updateOrCreate(
                ['role_id' => $roleId],
                ['configured_by' => $configuredBy->getKey()],
            );
        }
    }
}
