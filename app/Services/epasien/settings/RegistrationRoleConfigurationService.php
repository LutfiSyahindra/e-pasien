<?php

namespace App\Services\epasien\settings;

use App\Models\User;
use Illuminate\Support\Collection;

class RegistrationRoleConfigurationService
{
    public function __construct(
        private readonly RoleConfigurationService $roleConfigurationService
    ) {}

    public function roles(): Collection
    {
        return $this->roleConfigurationService->roles();
    }

    public function isConfigured(User $user): bool
    {
        return $this->roleConfigurationService->isRegistrationEnabled($user);
    }

    public function sync(array $roleIds, User $configuredBy): void
    {
        $this->roleConfigurationService->syncRegistration($roleIds, $configuredBy);
    }
}
