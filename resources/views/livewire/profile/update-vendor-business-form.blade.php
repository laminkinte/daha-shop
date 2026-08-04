<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $business_name = '';
    public string $business_phone = '';
    public string $business_address = '';
    public string $bank_name = '';
    public string $bank_account_number = '';
    public string $bank_account_name = '';

    public function mount(): void
    {
        $vendor = Auth::user()->vendor;

        $this->business_name = $vendor->business_name;
        $this->business_phone = $vendor->business_phone;
        $this->business_address = $vendor->business_address;
        $this->bank_name = (string) $vendor->bank_name;
        $this->bank_account_number = (string) $vendor->bank_account_number;
        $this->bank_account_name = (string) $vendor->bank_account_name;
    }

    /**
     * Bank details are how payouts actually reach a vendor - there was no
     * other page in the app where a vendor could set these before this form
     * existed.
     */
    public function updateBusinessInfo(): void
    {
        $validated = $this->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_phone' => ['required', 'string', 'max:20'],
            'business_address' => ['required', 'string'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:20'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
        ]);

        Auth::user()->vendor->update($validated);

        $this->dispatch('vendor-business-updated');
    }
}; ?>

<section>
    @php $vendor = auth()->user()->vendor; @endphp
    <div class="px-6 py-4 border-b border-gray-100 flex items-start justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-lg bg-purple-50 flex items-center justify-center text-purple-600 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72" /></svg>
            </div>
            <div>
                <h2 class="font-semibold text-gray-800">
                    {{ __('Business Details') }}
                </h2>

                <p class="text-xs text-gray-500">
                    {{ __('Your storefront details and where payouts are sent.') }}
                </p>
            </div>
        </div>

        <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $vendor->status->value === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($vendor->status->value === 'suspended' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
            {{ $vendor->status->value }}
        </span>
    </div>

    <div class="p-6">
        @if ($vendor->needsIdDocumentRetake() || $vendor->needsSelfieRetake())
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                An admin has asked you to retake a KYC photo.
                <a href="{{ route('vendor.identity') }}" wire:navigate class="font-semibold underline">Resubmit it here</a>.
            </div>
        @endif

        <form wire:submit="updateBusinessInfo" class="space-y-6">
        <div>
            <x-input-label for="business_name" :value="__('Business Name')" />
            <x-text-input wire:model="business_name" id="business_name" type="text" class="mt-1 block w-full" required />
            <x-input-error class="mt-2" :messages="$errors->get('business_name')" />
        </div>

        <div>
            <x-input-label for="business_phone" :value="__('Business Phone')" />
            <x-text-input wire:model="business_phone" id="business_phone" type="text" class="mt-1 block w-full" required />
            <x-input-error class="mt-2" :messages="$errors->get('business_phone')" />
        </div>

        <div>
            <x-input-label for="business_address" :value="__('Business Address')" />
            <textarea wire:model="business_address" id="business_address" rows="2" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500" required></textarea>
            <x-input-error class="mt-2" :messages="$errors->get('business_address')" />
        </div>

        <div class="pt-4 border-t border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">{{ __('Payout Bank Account') }}</h3>
            <p class="text-xs text-gray-500 mt-0.5">{{ __("This is where your Daha Shop payouts are sent.") }}</p>

            <div class="mt-4 space-y-4">
                <div>
                    <x-input-label for="bank_name" :value="__('Bank Name')" />
                    <x-text-input wire:model="bank_name" id="bank_name" type="text" class="mt-1 block w-full" />
                    <x-input-error class="mt-2" :messages="$errors->get('bank_name')" />
                </div>

                <div>
                    <x-input-label for="bank_account_number" :value="__('Account Number')" />
                    <x-text-input wire:model="bank_account_number" id="bank_account_number" type="text" inputmode="numeric" class="mt-1 block w-full" />
                    <x-input-error class="mt-2" :messages="$errors->get('bank_account_number')" />
                </div>

                <div>
                    <x-input-label for="bank_account_name" :value="__('Account Name')" />
                    <x-text-input wire:model="bank_account_name" id="bank_account_name" type="text" class="mt-1 block w-full" />
                    <x-input-error class="mt-2" :messages="$errors->get('bank_account_name')" />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="vendor-business-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
        </form>
    </div>
</section>
