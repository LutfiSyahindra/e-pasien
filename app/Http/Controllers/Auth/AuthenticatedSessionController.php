<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        $this->setLoginCaptcha($request);

        return view('auth.login', [
            'captchaQuestion' => $request->session()->get('login_captcha_question'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->forget(['login_captcha_question', 'login_captcha_answer']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Generate a simple session-backed captcha for the login form.
     */
    private function setLoginCaptcha(Request $request): void
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $captcha = '';

        for ($index = 0; $index < 5; $index++) {
            $captcha .= $characters[random_int(0, strlen($characters) - 1)];
        }

        $request->session()->put([
            'login_captcha_question' => $captcha,
            'login_captcha_answer' => $captcha,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
