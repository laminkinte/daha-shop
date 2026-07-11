<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string')]
    public string $login = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * A 4-digit PIN has only 10,000 possible values - far weaker than a real
     * password - so PIN-based accounts get a much tighter lockout: fewer
     * attempts, and a long cooldown instead of the standard one-minute decay.
     */
    private const PASSWORD_MAX_ATTEMPTS = 5;

    private const PASSWORD_DECAY_SECONDS = 60;

    private const PIN_MAX_ATTEMPTS = 3;

    private const PIN_DECAY_SECONDS = 900;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $usesPin = $this->targetUsesPin();

        $this->ensureIsNotRateLimited($usesPin);

        $field = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::attempt([$field => $this->login, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey(), $usesPin ? self::PIN_DECAY_SECONDS : self::PASSWORD_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'form.login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(bool $usesPin): void
    {
        $maxAttempts = $usesPin ? self::PIN_MAX_ATTEMPTS : self::PASSWORD_MAX_ATTEMPTS;

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Whether the account this login attempt targets (if it exists) uses a
     * PIN, so the stricter lockout policy applies. Looked up by email or
     * phone without revealing to the caller whether the account exists.
     */
    private function targetUsesPin(): bool
    {
        $field = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        return User::where($field, $this->login)->value('uses_pin') ?? false;
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->login).'|'.request()->ip());
    }
}
