<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->uses_pin ? '' : $user->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     * Phone/PIN accounts have no real email (see User::hasRealEmail()), so
     * only their name is editable here.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $rules = ['name' => ['required', 'string', 'max:255']];

        if (! $user->uses_pin) {
            $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)];
        }

        $validated = $this->validate($rules);

        $user->name = $validated['name'];

        if (! $user->uses_pin) {
            if ($validated['email'] !== $user->email) {
                $user->email_verified_at = null;
            }

            $user->email = $validated['email'];
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <div class="h-9 w-9 rounded-lg bg-green-50 flex items-center justify-center text-green-700 shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.964 0a9 9 0 10-11.964 0m11.964 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
        </div>
        <div>
            <h2 class="font-semibold text-gray-800">
                {{ __('Profile Information') }}
            </h2>

            <p class="text-xs text-gray-500">
                @if (auth()->user()->uses_pin)
                    {{ __('Update your name.') }}
                @else
                    {{ __("Update your account's profile information and email address.") }}
                @endif
            </p>
        </div>
    </div>

    <form wire:submit="updateProfileInformation" class="p-6 space-y-6">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        @unless (auth()->user()->uses_pin)
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if (! auth()->user()->hasVerifiedEmail())
                    <div>
                        <p class="text-sm mt-2 text-gray-600">
                            {{ __('Your email address is unverified.') }}

                            <button wire:click.prevent="sendVerification" class="underline text-sm text-green-700 hover:text-green-800">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-medium text-sm text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        @endunless

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
