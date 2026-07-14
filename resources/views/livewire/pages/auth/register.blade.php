<?php

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ImageClarityChecker;
use App\Services\OtpService;
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

    public int $step = 1;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    public string $registrationMethod = 'email';
    public string $pin = '';
    public string $pin_confirmation = '';

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

    public function getTotalStepsProperty(): int
    {
        return $this->accountType === 'seller' ? 4 : 2;
    }

    public function nextStep(): void
    {
        if ($this->step === 2) {
            $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => $this->registrationMethod === 'email'
                    ? ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class]
                    : ['nullable'],
                'phone' => ['required', 'string', 'max:20', 'unique:'.User::class],
                'password' => $this->registrationMethod === 'email'
                    ? ['required', 'string', 'confirmed', Rules\Password::defaults()]
                    : ['nullable'],
                'pin' => $this->registrationMethod === 'phone'
                    ? ['required', 'digits:4', 'confirmed']
                    : ['nullable'],
            ]);
        } elseif ($this->step === 3) {
            $this->validate([
                'business_name' => ['required', 'string', 'max:255'],
                'business_phone' => ['required', 'string', 'max:20'],
                'business_address' => ['required', 'string'],
            ]);
        }

        $this->step++;
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
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
    public function register(OtpService $otpService): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => $this->registrationMethod === 'email'
                ? ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class]
                : ['nullable'],

            'phone' => ['required', 'string', 'max:20', 'unique:'.User::class],

            'password' => $this->registrationMethod === 'email'
                ? ['required', 'string', 'confirmed', Rules\Password::defaults()]
                : ['nullable'],

            'pin' => $this->registrationMethod === 'phone'
                ? ['required', 'digits:4', 'confirmed']
                : ['nullable'],

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

        $isPinAccount = $this->registrationMethod === 'phone';

        $baseAttributes = [
            'name' => $validated['name'],
            'email' => $isPinAccount
                ? Str::slug($validated['phone']).'@phone.dahashop.internal'
                : $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($isPinAccount ? $validated['pin'] : $validated['password']),
            'uses_pin' => $isPinAccount,
        ];

        if ($this->accountType === 'customer') {

            $user = User::create([...$baseAttributes, 'role' => UserRole::Customer]);

        } else {

            $user = User::create([...$baseAttributes, 'role' => UserRole::Vendor]);

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

        // Every new account is "pending" until the phone number is confirmed -
        // the account works immediately, but a verification prompt follows
        // the user around the app until this OTP is entered.
        $otpService->generate($user->phone, 'phone_verification');

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    {{-- Loaded unconditionally (not from inside the step-4-only camera/selfie
         components) so the browser executes these as part of the normal page
         load, before any Livewire step transition morphs those components in. --}}
    <x-camera-capture-scripts />
    @vite('resources/js/selfie-capture.js')

    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Create your account</h1>
        <p class="text-sm text-gray-500 mt-1">Join Daha Shop as a customer, or start selling.</p>
    </div>

    <!-- Step progress -->
    @php
        $stepLabels = $accountType === 'seller'
            ? [1 => 'Account', 2 => 'Details', 3 => 'Business', 4 => 'Verify']
            : [1 => 'Account', 2 => 'Details'];
    @endphp
    <div class="flex items-center mb-8">
        @foreach ($stepLabels as $num => $label)
            <div class="flex-1 flex flex-col items-center relative">
                <div class="h-7 w-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 {{ $step >= $num ? 'bg-green-700 text-white' : 'bg-gray-100 text-gray-400' }}">
                    @if ($step > $num)
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span class="text-[10px] mt-1 text-center {{ $step >= $num ? 'text-green-700 font-medium' : 'text-gray-400' }}">{{ $label }}</span>
                @if (!$loop->last)
                    <div class="absolute top-3.5 left-1/2 w-full h-0.5 -z-10 {{ $step > $num ? 'bg-green-700' : 'bg-gray-200' }}"></div>
                @endif
            </div>
        @endforeach
    </div>

    <div>
        @if ($step === 1)
            <!-- Step 1: Account type -->
            <div>
                <x-input-label :value="__('How do you want to use Daha Shop?')" class="mb-3" />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button type="button" wire:click="$set('accountType', 'customer')"
                        class="text-left rounded-xl border-2 p-4 transition-colors {{ $accountType === 'customer' ? 'border-green-600 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center text-green-700 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" /></svg>
                        </div>
                        <p class="font-semibold text-gray-900">Customer</p>
                        <p class="text-xs text-gray-500 mt-1">Shop products and pay cash on delivery.</p>
                    </button>

                    <button type="button" wire:click="$set('accountType', 'seller')"
                        class="text-left rounded-xl border-2 p-4 transition-colors {{ $accountType === 'seller' ? 'border-green-600 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center text-green-700 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72" /></svg>
                        </div>
                        <p class="font-semibold text-gray-900">Seller</p>
                        <p class="text-xs text-gray-500 mt-1">List products and start selling online.</p>
                    </button>
                </div>

                <x-primary-button type="button" wire:click="nextStep" class="w-full mt-6">
                    Continue
                </x-primary-button>
            </div>
        @endif

        @if ($step === 2)
            <!-- Step 2: Your details -->
            <div>
                <div class="mb-6">
                    <x-input-label :value="__('Sign Up With')" />

                    <div class="mt-2 grid grid-cols-2 gap-2 rounded-lg bg-gray-100 p-1">
                        <button type="button" wire:click="$set('registrationMethod', 'email')"
                            class="rounded-md px-4 py-2 text-sm font-medium transition {{ $registrationMethod === 'email' ? 'bg-green-600 text-white shadow' : 'bg-transparent text-gray-700 hover:bg-gray-200' }}">
                            Email &amp; Password
                        </button>

                        <button type="button" wire:click="$set('registrationMethod', 'phone')"
                            class="rounded-md px-4 py-2 text-sm font-medium transition {{ $registrationMethod === 'phone' ? 'bg-green-600 text-white shadow' : 'bg-transparent text-gray-700 hover:bg-gray-200' }}">
                            Phone Number &amp; PIN
                        </button>
                    </div>
                </div>

                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                @if ($registrationMethod === 'email')
                    <div class="mt-4">
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                @endif

                <div class="mt-4">
                    <x-input-label for="phone" :value="__('Phone Number')" />
                    <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    @if ($registrationMethod === 'phone')
                        <p class="text-xs text-gray-500 mt-1">We'll text a code here to confirm it's really you.</p>
                    @endif
                </div>

                @if ($registrationMethod === 'email')
                    <div class="mt-4">
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" required />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                @else
                    <div class="mt-4">
                        <x-input-label for="pin" :value="__('4-Digit PIN')" />
                        <x-text-input wire:model="pin" id="pin" class="block mt-1 w-full tracking-[0.5em] text-center" type="password" inputmode="numeric" maxlength="4" pattern="[0-9]*" required />
                        <x-input-error :messages="$errors->get('pin')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="pin_confirmation" :value="__('Confirm PIN')" />
                        <x-text-input wire:model="pin_confirmation" id="pin_confirmation" class="block mt-1 w-full tracking-[0.5em] text-center" type="password" inputmode="numeric" maxlength="4" pattern="[0-9]*" required />
                        <x-input-error :messages="$errors->get('pin_confirmation')" class="mt-2" />
                    </div>
                @endif

                <div class="mt-6 flex gap-3">
                    <x-secondary-button type="button" wire:click="previousStep">Back</x-secondary-button>

                    @if ($accountType === 'seller')
                        <x-primary-button type="button" wire:click="nextStep" class="flex-1">
                            Next: Business Details
                        </x-primary-button>
                    @else
                        <x-primary-button type="button" wire:click="register" class="flex-1" wire:loading.attr="disabled" wire:target="register">
                            <span wire:loading.remove wire:target="register">Create Account</span>
                            <span wire:loading wire:target="register">Creating your account&hellip;</span>
                        </x-primary-button>
                    @endif
                </div>
            </div>
        @endif

        @if ($step === 3 && $accountType === 'seller')
            <!-- Step 3: Business details -->
            <div>
                <div>
                    <x-input-label for="business_name" :value="__('Business Name')" />
                    <x-text-input wire:model="business_name" id="business_name" class="block mt-1 w-full" type="text" autofocus />
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
                        id="business_address"
                        class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                        rows="3"></textarea>
                    <x-input-error :messages="$errors->get('business_address')" class="mt-2" />
                </div>

                <div class="mt-6 flex gap-3">
                    <x-secondary-button type="button" wire:click="previousStep">Back</x-secondary-button>
                    <x-primary-button type="button" wire:click="nextStep" class="flex-1">
                        Next: Identity Verification
                    </x-primary-button>
                </div>
            </div>
        @endif

        @if ($step === 4 && $accountType === 'seller')
            <!-- Step 4: Identity verification -->
            <div>
                <div class="mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Identity Verification</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Required before your seller account can be approved. An admin reviews these before you can list products.
                    </p>
                </div>

                <div>
                    <x-input-label :value="__('ID Document Type')" />
                    <select wire:model="idDocumentType" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
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

                        {{--
                            x-if (not x-show) on purpose: this only mounts (and
                            requests camera access) once the user actually
                            chooses "Use Camera", instead of opening a hidden
                            camera stream on every page load regardless of
                            which mode is selected - which also meant it was
                            competing for the camera device with the selfie
                            capture below at the same time.
                        --}}
                        <template x-if="mode === 'camera'">
                            <div>
                                <x-camera-capture wireModel="idDocument" label="Capture ID Document" />
                            </div>
                        </template>
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
                        Center your face in the frame &mdash; it captures automatically once you're positioned and holding still.
                    </p>

                    <x-selfie-capture wireModel="selfie" label="Capture Selfie" />

                    <x-input-error :messages="$errors->get('selfie')" class="mt-2" />
                    @if ($selfieClarityWarning)
                        <p class="text-xs text-red-600 mt-1">{{ $selfieClarityWarning }}</p>
                    @endif
                </div>

                <div class="mt-6 flex gap-3">
                    <x-secondary-button type="button" wire:click="previousStep">Back</x-secondary-button>
                    <x-primary-button type="button" wire:click="register" class="flex-1" wire:loading.attr="disabled" wire:target="register">
                        <span wire:loading.remove wire:target="register">Create Account</span>
                        <span wire:loading wire:target="register">Creating your account&hellip;</span>
                    </x-primary-button>
                </div>
            </div>
        @endif
    </div>

    <p class="mt-6 text-sm text-gray-600 text-center">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-green-700 hover:text-green-800 underline">
            Sign in
        </a>
    </p>
</div>
