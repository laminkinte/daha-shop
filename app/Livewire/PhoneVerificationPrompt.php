<?php

namespace App\Livewire;

use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PhoneVerificationPrompt extends Component
{
    public string $code = '';

    public ?string $message = null;

    public bool $resent = false;

    public bool $dismissed = false;

    public function resend(OtpService $otpService): void
    {
        $otpService->generate(Auth::user()->phone, 'phone_verification');
        $this->resent = true;
        $this->message = null;
    }

    public function verify(OtpService $otpService)
    {
        $ok = $otpService->verify(Auth::user()->phone, 'phone_verification', $this->code);

        if (! $ok) {
            $this->message = 'That code is invalid or has expired. Please try again or request a new one.';

            return;
        }

        Auth::user()->update(['phone_verified_at' => now()]);
    }

    public function dismiss(): void
    {
        $this->dismissed = true;
    }

    public function render()
    {
        $shouldShow = Auth::check() && ! Auth::user()->isPhoneVerified() && ! $this->dismissed;

        return view('livewire.phone-verification-prompt', ['shouldShow' => $shouldShow]);
    }
}
