<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
