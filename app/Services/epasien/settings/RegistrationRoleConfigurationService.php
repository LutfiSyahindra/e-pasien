<?php

namespace App\Services\epasien\settings;

use App\Models\RegistrationRoleConfiguration;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RegistrationRoleConfigurationService
{
    public function roles(): Collection
    {
        $configuredRoleIds = RegistrationRoleConfiguration::query()
            ->pluck('role_id');

        return Role::query()
            ->where('guard_name', 'web')
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->each(function (Role $role) use ($configuredRoleIds): void {
                $role->setAttribute('registration_enabled', $configuredRoleIds->contains($role->id));
            });
    }

    public function isConfigured(User $user): bool
    {
        if (! $user->exists && ! $user->relationLoaded('roles')) {
            return false;
        }

        $roleIds = $user->relationLoaded('roles')
            ? $user->roles->pluck('id')
            : $user->roles()->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return false;
        }

        return RegistrationRoleConfiguration::query()
            ->whereIn('role_id', $roleIds)
            ->exists();
    }

    public function sync(array $roleIds, User $configuredBy): void
    {
        $roleIds = collect($roleIds)
            ->map(fn (mixed $roleId): int => (int) $roleId)
            ->filter()
            ->unique()
            ->values();

        DB::connection(config('database.default'))->transaction(function () use ($roleIds, $configuredBy): void {
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
        });
    }
}
