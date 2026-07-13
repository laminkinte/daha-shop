<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Welcome back</h1>
        <p class="text-sm text-gray-500 mt-1">Log in to your Daha Shop account.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <!-- Email or Phone -->
        <div>
            <x-input-label for="login" :value="__('Email or Phone Number')" />
            <x-text-input wire:model="form.login" id="login" class="block mt-1 w-full" type="text" name="login" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password / PIN')" />

            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

       <div class="mt-4 flex items-center justify-between">
            <div class="flex flex-col gap-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">
                    Need help signing in?
                </span>

                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        wire:navigate
                        class="text-sm font-medium text-gray-600 hover:text-green-700 hover:underline transition duration-200"
                    >
                        Forgot your password?
                    </a>
                @endif

                @if (Route::has('pin.request'))
                    <a
                        href="{{ route('pin.request') }}"
                        wire:navigate
                        class="text-sm font-medium text-gray-600 hover:text-green-700 hover:underline transition duration-200"
                    >
                        Forgot PIN?
                    </a>
                @endif
            </div>

            <x-primary-button class="ms-4">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <p class="mt-6 text-sm text-gray-600 text-center">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-green-700 hover:text-green-800 underline">
            Register
        </a>
    </p>
</div>
