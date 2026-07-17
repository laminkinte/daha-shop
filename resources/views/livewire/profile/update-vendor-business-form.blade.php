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
    <header class="flex items-start justify-between flex-wrap gap-2">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">
                {{ __('Business Details') }}
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                {{ __('Your storefront details and where payouts are sent.') }}
            </p>
        </div>

        @php $vendor = auth()->user()->vendor; @endphp
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $vendor->status->value === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($vendor->status->value === 'suspended' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
            {{ $vendor->status->value }}
        </span>
    </header>

    @if ($vendor->needsIdDocumentRetake() || $vendor->needsSelfieRetake())
        <div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            An admin has asked you to retake a KYC photo.
            <a href="{{ route('vendor.identity') }}" wire:navigate class="font-semibold underline">Resubmit it here</a>.
        </div>
    @endif

    <form wire:submit="updateBusinessInfo" class="mt-6 space-y-6">
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
</section>
