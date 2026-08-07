<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Epasien\PushSubscriptionController;
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

    public function test_logout_detaches_only_the_current_browser_push_subscription(): void
    {
        $user = User::factory()->create();
        $chromeEndpoint = 'https://push.example.test/subscriptions/chrome';
        $samsungEndpoint = 'https://push.example.test/subscriptions/samsung';

        $user->updatePushSubscription($chromeEndpoint, 'chrome-key', 'chrome-token', 'aes128gcm');
        $user->updatePushSubscription($samsungEndpoint, 'samsung-key', 'samsung-token', 'aes128gcm');

        $response = $this->actingAs($user)
            ->withSession([PushSubscriptionController::SESSION_ENDPOINT_KEY => $chromeEndpoint])
            ->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $chromeEndpoint,
            'subscribable_id' => $user->id,
        ]);
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => $samsungEndpoint,
            'subscribable_id' => $user->id,
        ]);
    }

    public function test_logout_can_detach_the_subscription_reported_by_the_browser(): void
    {
        $user = User::factory()->create();
        $endpoint = 'https://push.example.test/subscriptions/reported-browser';
        $user->updatePushSubscription($endpoint, 'browser-key', 'browser-token', 'aes128gcm');

        $this->actingAs($user)
            ->post('/logout', ['push_endpoint' => $endpoint])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $endpoint,
            'subscribable_id' => $user->id,
        ]);
    }
}
