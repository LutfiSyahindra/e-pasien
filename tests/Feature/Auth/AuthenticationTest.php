<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->withSession(['login_captcha_answer' => 'A7B2C'])->post('/login', [
            'login' => $user->email,
            'password' => 'password',
            'captcha_answer' => 'a7b2c',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_patient_with_placeholder_email_is_directed_to_email_onboarding(): void
    {
        $user = User::factory()->create([
            'email' => 'pasien-001-abc123@e-pasien.local',
            'username' => '001',
        ]);
        $user->assignRole(Role::create([
            'name' => 'Pasien',
            'guard_name' => 'web',
            'email_onboarding_enabled' => true,
        ]));

        $response = $this->withSession(['login_captcha_answer' => 'A7B2C'])->post('/login', [
            'login' => $user->username,
            'password' => 'password',
            'captcha_answer' => 'A7B2C',
        ]);

        $this->assertAuthenticatedAs($user);
        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('patient_email_onboarding', true);
    }

    public function test_patient_with_personal_email_continues_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'pasien@example.com',
            'username' => '002',
        ]);
        $user->assignRole(Role::create([
            'name' => 'Pasien',
            'guard_name' => 'web',
            'email_onboarding_enabled' => true,
        ]));

        $response = $this->withSession(['login_captcha_answer' => 'A7B2C'])->post('/login', [
            'login' => $user->username,
            'password' => 'password',
            'captcha_answer' => 'A7B2C',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_patient_with_disabled_email_onboarding_continues_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'pasien-004-ghi789@e-pasien.local',
            'username' => '004',
        ]);
        $user->assignRole(Role::create([
            'name' => 'Pasien',
            'guard_name' => 'web',
            'email_onboarding_enabled' => false,
        ]));

        $response = $this->withSession(['login_captcha_answer' => 'A7B2C'])->post('/login', [
            'login' => $user->username,
            'password' => 'password',
            'captcha_answer' => 'A7B2C',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->withSession(['login_captcha_answer' => 'A7B2C'])->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
            'captcha_answer' => 'A7B2C',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_captcha(): void
    {
        $user = User::factory()->create();

        $this->withSession(['login_captcha_answer' => 'A7B2C'])->post('/login', [
            'login' => $user->email,
            'password' => 'password',
            'captcha_answer' => 'X9Y8Z',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
