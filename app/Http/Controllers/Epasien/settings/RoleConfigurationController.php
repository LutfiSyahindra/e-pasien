<?php

namespace App\Http\Controllers\Epasien\settings;

use App\Http\Controllers\Controller;
use App\Services\epasien\settings\RoleConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleConfigurationController extends Controller
{
    public function __construct(
        private readonly RoleConfigurationService $configurationService
    ) {}

    public function index(): View
    {
        $roles = $this->configurationService->roles();
        $registrationRoles = $roles->where('registration_enabled', true);
        $emailOnboardingRoles = $roles->where('email_onboarding_enabled', true);

        return view('e-pasien.settings.roleConfigurations.index', [
            'roles' => $roles,
            'registrationRoleCount' => $registrationRoles->count(),
            'registrationUserCount' => $registrationRoles->sum('users_count'),
            'emailOnboardingRoleCount' => $emailOnboardingRoles->count(),
            'emailOnboardingUserCount' => $emailOnboardingRoles->sum('users_count'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $roleRule = [
            'integer',
            'distinct',
            Rule::exists('roles', 'id')->where('guard_name', 'web'),
        ];
        $validated = $request->validate([
            'registration_role_ids' => ['nullable', 'array'],
            'registration_role_ids.*' => $roleRule,
            'email_onboarding_role_ids' => ['nullable', 'array'],
            'email_onboarding_role_ids.*' => $roleRule,
        ]);

        $this->configurationService->sync(
            $validated['registration_role_ids'] ?? [],
            $validated['email_onboarding_role_ids'] ?? [],
            $request->user(),
        );

        return redirect()
            ->route('roleConfiguration.index')
            ->with('status', 'Konfigurasi roles berhasil disimpan.');
    }
}
