<?php

namespace App\Http\Controllers\Epasien\settings;

use App\Http\Controllers\Controller;
use App\Services\epasien\settings\RegistrationRoleConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationRoleConfigurationController extends Controller
{
    public function __construct(
        private readonly RegistrationRoleConfigurationService $configurationService
    ) {}

    public function index(): View
    {
        $roles = $this->configurationService->roles();

        return view('e-pasien.settings.registrationRoles.index', [
            'roles' => $roles,
            'configuredCount' => $roles->where('registration_enabled', true)->count(),
            'configuredUsersCount' => $roles
                ->where('registration_enabled', true)
                ->sum('users_count'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('roles', 'id')->where('guard_name', 'web'),
            ],
        ]);

        $this->configurationService->sync(
            $validated['role_ids'] ?? [],
            $request->user(),
        );

        return redirect()
            ->route('registrationRoleConfiguration.index')
            ->with('status', 'Konfigurasi role pendaftaran berhasil disimpan.');
    }
}
