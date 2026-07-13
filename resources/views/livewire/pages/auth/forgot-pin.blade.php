<?php

use App\Models\User;
use App\Services\OtpService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;

new #[Layout('layouts.guest')] class extends Component
{
    public string $phone = '';
    public string $code = '';
    public string $pin = '';
    public string $pin_confirmation = '';

public bool $otpSent = false;

    public function sendOtp(OtpService $otpService): void
    {
        $this->validate([
            'phone' => ['required', 'string'],
        ]);

        $user = User::where('phone', $this->phone)
            ->where('uses_pin', true)
            ->first();

        if (! $user) {
            $this->addError('phone', 'No PIN account was found with this phone number.');

            return;
        }

        $otpService->generate($this->phone, 'pin_reset');

       session()->flash('status', 'A verification code has been sent to your phone.');
            $this->otpSent = true;

    }

    public function resetPin(OtpService $otpService): void
    {
        $this->validate([
            'code' => ['required', 'digits:6'],
            'pin' => ['required', 'digits:4', 'confirmed'],
        ]);

        if (! $otpService->verify($this->phone, 'pin_reset', $this->code)) {
            $this->addError('code', 'The verification code is invalid or has expired.');

            return;
        }

        User::where('phone', $this->phone)
            ->where('uses_pin', true)
            ->update([
                'password' => Hash::make($this->pin),
            ]);

        session()->flash('status', 'Your PIN has been reset successfully.');

        $this->redirect(route('login'), navigate: true);
    }
};

?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        Enter your phone number and we'll send you a verification code to reset your PIN.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="{{ $otpSent ? 'resetPin' : 'sendOtp' }}">

        <div>
            <x-input-label for="phone" :value="__('Phone Number')" />

            <x-text-input
                wire:model="phone"
                id="phone"
                class="block mt-1 w-full"
                type="text"
                required
                autofocus
            />

            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

            @if (! $otpSent)

                <div class="mt-6">
                    <x-primary-button>
                        Send Verification Code
                    </x-primary-button>
                </div>

            @else

                <div class="mt-6">
                    <x-input-label for="code" :value="__('Verification Code')" />

                    <x-text-input
                        id="code"
                        wire:model="code"
                        class="block mt-1 w-full"
                        type="text"
                        maxlength="6"
                    />

                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="pin" :value="__('New PIN')" />

                    <x-text-input
                        id="pin"
                        wire:model="pin"
                        class="block mt-1 w-full"
                        type="password"
                        maxlength="4"
                    />

                    <x-input-error :messages="$errors->get('pin')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="pin_confirmation" :value="__('Confirm PIN')" />

                    <x-text-input
                        id="pin_confirmation"
                        wire:model="pin_confirmation"
                        class="block mt-1 w-full"
                        type="password"
                        maxlength="4"
                    />
                </div>

                <div class="mt-6">
                    <x-primary-button>
                        Reset PIN
                    </x-primary-button>
                </div>

            @endif

    </form>
</div>