<?php

namespace Tests\Unit\Services\Epasien\Profile;

use App\Models\User;
use App\Services\epasien\Profile\PatientEmailOnboardingService;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientEmailOnboardingServiceTest extends TestCase
{
    public function test_patient_with_placeholder_email_should_be_prompted(): void
    {
        $user = $this->userWithRoles(
            'pasien-001-abc123@e-pasien.local',
            ['Pasien' => true]
        );

        $this->assertTrue(app(PatientEmailOnboardingService::class)->shouldPrompt($user));
    }

    public function test_role_with_disabled_onboarding_should_not_be_prompted(): void
    {
        $user = $this->userWithRoles(
            'pasien-002-abc123@e-pasien.local',
            ['Pasien' => false]
        );

        $this->assertFalse(app(PatientEmailOnboardingService::class)->shouldPrompt($user));
    }

    public function test_patient_with_personal_email_should_not_be_prompted(): void
    {
        $user = $this->userWithRoles(
            'pasien@example.com',
            ['Pasien' => true]
        );

        $this->assertFalse(app(PatientEmailOnboardingService::class)->shouldPrompt($user));
    }

    public function test_non_patient_with_placeholder_email_should_not_be_prompted(): void
    {
        $user = $this->userWithRoles('admin@e-pasien.local', ['Administrator' => false]);

        $this->assertFalse(app(PatientEmailOnboardingService::class)->shouldPrompt($user));
    }

    /**
     * @param  array<string, bool>  $roles
     */
    private function userWithRoles(string $email, array $roles): User
    {
        $user = new User(['email' => $email]);
        $user->setRelation('roles', Collection::make($roles)->map(
            fn (bool $enabled, string $role): Role => new Role([
                'name' => $role,
                'guard_name' => 'web',
                'email_onboarding_enabled' => $enabled,
            ])
        ));

        return $user;
    }
}
