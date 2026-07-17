<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_pin = '';
    public string $pin = '';
    public string $pin_confirmation = '';

    /**
     * Update the PIN for the currently authenticated phone/PIN account.
     * Mirrors update-password-form.blade.php, but for the 4-digit PIN that
     * phone-based accounts sign in with instead of a password.
     */
    public function updatePin(): void
    {
        try {
            $validated = $this->validate([
                'current_pin' => ['required', 'string', 'current_password'],
                'pin' => ['required', 'digits:4', 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_pin', 'pin', 'pin_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['pin']),
        ]);

        $this->reset('current_pin', 'pin', 'pin_confirmation');

        $this->dispatch('pin-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Update PIN') }}
        </h2>

        <p class="mt-1 text-sm text-gray-500">
            {{ __('Change the 4-digit PIN you use to sign in.') }}
        </p>
    </header>

    <form wire:submit="updatePin" class="mt-6 space-y-6">
        <div>
            <x-input-label for="update_pin_current_pin" :value="__('Current PIN')" />
            <x-text-input wire:model="current_pin" id="update_pin_current_pin" name="current_pin" type="password" inputmode="numeric" maxlength="4" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_pin')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_pin_pin" :value="__('New PIN')" />
            <x-text-input wire:model="pin" id="update_pin_pin" name="pin" type="password" inputmode="numeric" maxlength="4" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('pin')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_pin_pin_confirmation" :value="__('Confirm New PIN')" />
            <x-text-input wire:model="pin_confirmation" id="update_pin_pin_confirmation" name="pin_confirmation" type="password" inputmode="numeric" maxlength="4" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('pin_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="pin-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
