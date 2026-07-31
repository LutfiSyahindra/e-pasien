<?php

namespace App\Services\epasien\Profile;

use App\Models\User;
use Illuminate\Support\Str;

class PatientEmailOnboardingService
{
    public function shouldPrompt(User $user): bool
    {
        $email = Str::lower(trim((string) $user->email));

        if ($email === '' || ! Str::endsWith($email, '@e-pasien.local')) {
            return false;
        }

        if ($user->relationLoaded('roles')) {
            return $user->roles->contains(
                fn ($role): bool => (bool) $role->email_onboarding_enabled
            );
        }

        return $user->roles()
            ->where('email_onboarding_enabled', true)
            ->exists();
    }
}
