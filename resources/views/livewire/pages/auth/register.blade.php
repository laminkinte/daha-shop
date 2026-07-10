<?php

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    public string $accountType = 'customer';

    public string $business_name = '';
    public string $business_address = '';
    public string $business_phone = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
            $validated = $this->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
        'phone' => ['required', 'string', 'max:20', 'unique:'.User::class],
        'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],

        'business_name' => $this->accountType === 'seller'
            ? ['required', 'string', 'max:255']
            : ['nullable'],

        'business_phone' => $this->accountType === 'seller'
            ? ['required', 'string', 'max:20']
            : ['nullable'],

        'business_address' => $this->accountType === 'seller'
            ? ['required', 'string']
            : ['nullable'],
    ]);

        $validated['password'] = Hash::make($validated['password']);

        if ($this->accountType === 'customer') {

            $validated['role'] = UserRole::Customer;

            $user = User::create($validated);

        } else {

            $validated['role'] = UserRole::Vendor;

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'role' => UserRole::Vendor,
            ]);

            Vendor::create([
                'user_id' => $user->id,
                'business_name' => $validated['business_name'],
                'slug' => Str::slug($validated['business_name']) . '-' . time(),
                'business_phone' => $validated['business_phone'],
                'business_address' => $validated['business_address'],
                'status' => VendorStatus::Pending,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">

        <!-- Register Type -->
        <div class="mb-6">
            <x-input-label :value="__('Register As')" />

            <div class="mt-2 grid grid-cols-2 gap-2 rounded-lg bg-gray-100 p-1">

                <button
                    type="button"
                    wire:click="$set('accountType', 'customer')"
                    class="rounded-md px-4 py-2 text-sm font-medium transition {{ $accountType === 'customer' ? 'bg-green-600 text-white shadow' : 'bg-transparent text-gray-700 hover:bg-gray-200' }}">
                    Customer
                </button>

                <button
                    type="button"
                    wire:click="$set('accountType', 'seller')"
                    class="rounded-md px-4 py-2 text-sm font-medium transition {{ $accountType === 'seller' ? 'bg-green-600 text-white shadow' : 'bg-transparent text-gray-700 hover:bg-gray-200' }}">
                    Seller
                </button>

            </div>
        </div>

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Phone -->
        <div class="mt-4">
            <x-input-label for="phone" :value="__('Phone Number')" />
            <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" required />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        @if ($accountType === 'seller')

            <div class="mt-4">
                <x-input-label for="business_name" :value="__('Business Name')" />
                <x-text-input wire:model="business_name" id="business_name" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="business_phone" :value="__('Business Phone')" />
                <x-text-input wire:model="business_phone" id="business_phone" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('business_phone')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="business_address" :value="__('Business Address')" />
                <textarea
                    wire:model="business_address"
                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm"
                    rows="3"></textarea>
                <x-input-error :messages="$errors->get('business_address')" class="mt-2" />
            </div>

        @endif

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" required />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">

            <x-primary-button>
                Register
            </x-primary-button>

            <p class="mt-4 text-sm text-gray-600">
                Already have an account?
                <a
                    href="{{ route('login') }}"
                    wire:navigate
                    class="font-semibold text-green-700 hover:text-green-800 underline">
                    Sign in
                </a>
            </p>

        </div>

    </form>
</div>