<?php

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ImageClarityChecker;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.guest')] class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    public string $accountType = 'customer';

    public string $business_name = '';
    public string $business_address = '';
    public string $business_phone = '';

    public string $idDocumentType = 'national_id';
    public $idDocument;
    public $selfie;
    public ?string $idDocumentClarityWarning = null;
    public ?string $selfieClarityWarning = null;

    public function mount(): void
    {
        if (request()->query('as') === 'seller') {
            $this->accountType = 'seller';
        }
    }

    public function updatedIdDocument(ImageClarityChecker $checker): void
    {
        $this->idDocumentClarityWarning = ($this->idDocument && ! $checker->isClear($this->idDocument->getRealPath()))
            ? "This photo looks blurry or unclear. Please retake it in good lighting."
            : null;
    }

    public function updatedSelfie(ImageClarityChecker $checker): void
    {
        $this->selfieClarityWarning = ($this->selfie && ! $checker->isClear($this->selfie->getRealPath()))
            ? "This photo looks blurry or unclear. Please retake it in good lighting."
            : null;
    }

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

        'idDocumentType' => $this->accountType === 'seller'
            ? ['required', 'in:national_id,passport']
            : ['nullable'],

        'idDocument' => $this->accountType === 'seller'
            ? ['required', 'image', 'max:5120']
            : ['nullable'],

        'selfie' => $this->accountType === 'seller'
            ? ['required', 'image', 'max:5120']
            : ['nullable'],
    ]);

        if ($this->accountType === 'seller' && ($this->idDocumentClarityWarning || $this->selfieClarityWarning)) {
            $this->addError('idDocument', 'Please retake any blurry photos before submitting.');

            return;
        }

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
                'id_document_type' => $this->idDocumentType,
                'id_document_path' => $this->idDocument->store('vendor-kyc', 'local'),
                'selfie_path' => $this->selfie->store('vendor-kyc', 'local'),
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Create your account</h1>
        <p class="text-sm text-gray-500 mt-1">Join Daha Shop as a customer, or start selling.</p>
    </div>

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

            <div class="mt-6 border-t border-gray-200 pt-4">
                <h2 class="text-sm font-semibold text-gray-800">Identity Verification</h2>
                <p class="text-xs text-gray-500 mt-1">
                    Required before your seller account can be approved. An admin reviews these before you can list products.
                </p>
            </div>

            <div class="mt-4">
                <x-input-label :value="__('ID Document Type')" />
                <select wire:model="idDocumentType" class="mt-1 w-full rounded-md border-gray-300 focus:border-green-500 focus:ring-green-500">
                    <option value="national_id">National ID Card</option>
                    <option value="passport">International Passport</option>
                </select>
                <x-input-error :messages="$errors->get('idDocumentType')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label :value="__('ID Document Photo')" />

                <div x-data="{ mode: 'upload' }" class="mt-1">
                    <div class="flex gap-2 mb-2 text-xs">
                        <button type="button" @click="mode = 'upload'" :class="mode === 'upload' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-3 py-1.5 rounded-md font-medium">
                            Upload Photo
                        </button>
                        <button type="button" @click="mode = 'camera'" :class="mode === 'camera' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-3 py-1.5 rounded-md font-medium">
                            Use Camera
                        </button>
                    </div>

                    <div x-show="mode === 'upload'">
                        <input type="file" wire:model="idDocument" accept="image/*" class="block w-full text-sm">
                        <div wire:loading wire:target="idDocument" class="text-xs text-gray-500 mt-1">Checking image clarity&hellip;</div>
                    </div>

                    <div x-show="mode === 'camera'" x-cloak>
                        <x-camera-capture wireModel="idDocument" label="Capture ID Document" />
                    </div>
                </div>

                <x-input-error :messages="$errors->get('idDocument')" class="mt-2" />
                @if ($idDocumentClarityWarning)
                    <p class="text-xs text-red-600 mt-1">{{ $idDocumentClarityWarning }}</p>
                @endif
            </div>

            <div class="mt-4">
                <x-input-label :value="__('Live Selfie')" />
                <p class="text-xs text-gray-500 mb-2">
                    We ask for a live camera photo, not an upload, so an admin can confirm it matches your ID document.
                </p>

                <x-camera-capture wireModel="selfie" label="Capture Selfie" />

                <x-input-error :messages="$errors->get('selfie')" class="mt-2" />
                @if ($selfieClarityWarning)
                    <p class="text-xs text-red-600 mt-1">{{ $selfieClarityWarning }}</p>
                @endif
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
