<?php

namespace Modules\User\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mrj\Foundation\Support\Email;
use Mrj\Foundation\Support\PhoneNumber;

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
     * Normalise the login identifier before anything reads it (validation, the
     * email/phone branch in the controller, Auth::attempt). Must run before the
     * FILTER_VALIDATE_EMAIL check — an email with a stray invisible character
     * fails that check and would be mistaken for a phone number.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('login')) {
            $this->merge(['login' => Email::normalize($this->input('login'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Determine whether the login identifier looks like an email.
     */
    public function isEmail(): bool
    {
        return filter_var($this->input('login'), FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! $this->attemptLogin()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Email logs in against users.email. A phone number is resolved through the
     * project's user model (User::scopeWherePhone()) and the password verified.
     */
    private function attemptLogin(): bool
    {
        $login = (string) $this->input('login');
        $remember = $this->boolean('remember');

        if ($this->isEmail()) {
            return Auth::attempt(['email' => $login, 'password' => $this->input('password')], $remember);
        }

        $user = User::query()
            ->wherePhone(PhoneNumber::toE164($login))
            ->first();

        if ($user === null || ! Hash::check((string) $this->input('password'), $user->password)) {
            return false;
        }

        Auth::login($user, $remember);

        return true;
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
