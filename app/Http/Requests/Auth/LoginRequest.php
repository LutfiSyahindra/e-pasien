<?php

namespace App\Http\Requests\Auth;

use App\Services\epasien\settings\auth\PatientUserSyncService;
use Closure;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'captcha_answer' => [
                'required',
                'string',
                'size:5',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $expectedAnswer = $this->session()->get('login_captcha_answer');

                    if ($expectedAnswer === null || Str::upper(trim((string) $value)) !== $expectedAnswer) {
                        $fail('Captcha tidak sesuai. Silakan coba lagi.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom validation messages for login.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'captcha_answer.required' => 'Captcha wajib diisi.',
            'captcha_answer.string' => 'Captcha harus berupa huruf dan angka.',
            'captcha_answer.size' => 'Captcha harus 5 karakter.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) $this->input('login'));

        if (! $this->attemptLogin($login) && ! $this->attemptPatientProvisioning($login)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        if (! $this->user()?->status) {
            Auth::guard('web')->logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Akun Anda sedang nonaktif.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function attemptLogin(string $login): bool
    {
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return Auth::attempt([
            $field => $login,
            'password' => $this->input('password'),
        ], $this->boolean('remember'));
    }

    private function attemptPatientProvisioning(string $login): bool
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $user = app(PatientUserSyncService::class)->provisionFromPatientCredentials(
                $login,
                (string) $this->input('password')
            );
        } catch (Throwable $exception) {
            Log::warning('Gagal melakukan auto-provision user pasien saat login.', [
                'username' => $login,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        return $user !== null && $this->attemptLogin($login);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
