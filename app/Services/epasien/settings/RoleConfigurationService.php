<?php

namespace App\Services\epasien\settings;

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
            });
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
        $roleIds = $this->normalizeRoleIds($roleIds);

        DB::connection(config('database.default'))->transaction(
            fn () => $this->replaceRegistrationRoles($roleIds, $configuredBy)
        );
    }

    public function sync(
        array $registrationRoleIds,
        array $emailOnboardingRoleIds,
        User $configuredBy
    ): void {
        $registrationRoleIds = $this->normalizeRoleIds($registrationRoleIds);
        $emailOnboardingRoleIds = $this->normalizeRoleIds($emailOnboardingRoleIds);

        DB::connection(config('database.default'))->transaction(function () use (
            $registrationRoleIds,
            $emailOnboardingRoleIds,
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

    private function normalizeRoleIds(array $roleIds): Collection
    {
        return collect($roleIds)
            ->map(fn (mixed $roleId): int => (int) $roleId)
            ->filter()
            ->unique()
            ->values();
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
