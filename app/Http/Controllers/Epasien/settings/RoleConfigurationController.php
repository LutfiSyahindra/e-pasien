<?php

namespace App\Http\Controllers\Epasien\settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\epasien\menu\PromotionNotificationRecipientService;
use App\Services\epasien\settings\RoleConfigurationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleConfigurationController extends Controller
{
    public function __construct(
        private readonly RoleConfigurationService $configurationService,
        private readonly PromotionNotificationRecipientService $recipientService,
    ) {}

    public function index(Request $request): View
    {
        $roles = $this->configurationService->roles();
        $registrationRoles = $roles->where('registration_enabled', true);
        $emailOnboardingRoles = $roles->where('email_onboarding_enabled', true);
        $promotionNotificationRoles = $roles->where('promotion_notifications_enabled', true);
        $configuredPromotionUsers = $this->configurationService->promotionNotificationUsers();
        $hasOldPromotionUsers = $request->session()->hasOldInput('promotion_notification_user_ids');
        $selectedPromotionUserIds = $hasOldPromotionUsers
            ? (array) $request->session()->getOldInput('promotion_notification_user_ids', [])
            : $configuredPromotionUsers->pluck('id')->all();
        $selectedPromotionUsers = $hasOldPromotionUsers
            ? $this->configurationService->promotionNotificationUsers(
                collect($selectedPromotionUserIds)
                    ->merge($configuredPromotionUsers->pluck('id'))
                    ->unique()
                    ->values()
                    ->all()
            )
            : $configuredPromotionUsers;

        return view('e-pasien.settings.roleConfigurations.index', [
            'roles' => $roles,
            'registrationRoleCount' => $registrationRoles->count(),
            'registrationUserCount' => $registrationRoles->sum('users_count'),
            'emailOnboardingRoleCount' => $emailOnboardingRoles->count(),
            'emailOnboardingUserCount' => $emailOnboardingRoles->sum('users_count'),
            'promotionNotificationRoleCount' => $promotionNotificationRoles->count(),
            'promotionNotificationUserCount' => $configuredPromotionUsers->count(),
            'promotionNotificationRecipientCount' => $this->recipientService->count(),
            'configuredPromotionUserIds' => $configuredPromotionUsers->pluck('id'),
            'selectedPromotionUserIds' => collect($selectedPromotionUserIds)->map(fn ($id) => (int) $id),
            'selectedPromotionUsers' => $selectedPromotionUsers,
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $term = trim($validated['q'] ?? '');

        $users = User::query()
            ->select(['id', 'name', 'username', 'email'])
            ->with('roles:id,name')
            ->where(function (Builder $query): void {
                $query->where('status', true)->orWhereNull('status');
            })
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $search) use ($term): void {
                    $search
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('username', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->limit(21)
            ->get();
        $hasMore = $users->count() > 20;

        return response()->json([
            'results' => $users->take(20)->map(function (User $user): array {
                $identity = $user->username ?: $user->email;
                $roles = $user->roles->pluck('name')->implode(', ');

                return [
                    'id' => $user->getKey(),
                    'text' => $user->name.($identity ? " ({$identity})" : ''),
                    'roles' => $roles ?: 'Tanpa role',
                ];
            })->values(),
            'pagination' => ['more' => $hasMore],
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
            'promotion_notification_role_ids' => ['nullable', 'array'],
            'promotion_notification_role_ids.*' => $roleRule,
            'promotion_notification_user_ids' => ['nullable', 'array'],
            'promotion_notification_user_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id'),
            ],
        ]);

        $this->configurationService->sync(
            $validated['registration_role_ids'] ?? [],
            $validated['email_onboarding_role_ids'] ?? [],
            $validated['promotion_notification_role_ids'] ?? [],
            $validated['promotion_notification_user_ids'] ?? [],
            $request->user(),
        );

        return redirect()
            ->route('roleConfiguration.index')
            ->with('status', 'Konfigurasi role dan penerima notifikasi berhasil disimpan.');
    }
}
