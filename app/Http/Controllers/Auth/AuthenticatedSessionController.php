<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Epasien\PushSubscriptionController;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\epasien\Profile\PatientEmailOnboardingService;
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
    public function store(
        LoginRequest $request,
        PatientEmailOnboardingService $patientEmailOnboardingService
    ): RedirectResponse {
        $request->authenticate();

        $request->session()->forget(['login_captcha_question', 'login_captcha_answer']);
        $request->session()->regenerate();

        $user = $request->user();

        if ($user && $patientEmailOnboardingService->shouldPrompt($user)) {
            return redirect()
                ->route('profile.edit')
                ->with('patient_email_onboarding', true);
        }

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
        $sessionEndpoint = $request->session()->get(PushSubscriptionController::SESSION_ENDPOINT_KEY);
        $reportedEndpoint = $request->input('push_endpoint');

        $endpoints = array_filter(
            [$sessionEndpoint, $reportedEndpoint],
            static fn (mixed $endpoint): bool => is_string($endpoint)
                && $endpoint !== ''
                && strlen($endpoint) <= 500,
        );

        foreach (array_unique($endpoints) as $endpoint) {
            $request->user()?->deletePushSubscription($endpoint);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
