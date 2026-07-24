<div class="max-w-2xl">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Business Settings</h2>
        <p class="text-sm text-gray-500 mt-1">Tune platform rules without a code deploy.</p>
    </div>

    @if (session('settings-saved'))
        <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-700 text-sm px-4 py-3">
            Settings saved.
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-800">Order Rules</h3>

            <div>
                <label class="text-sm font-medium text-gray-700">COD auto-confirm threshold (NGN)</label>
                <input type="text" wire:model="maxCodAutoConfirmAmount" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                <p class="text-xs text-gray-400 mt-1">Orders above this amount require manual admin approval instead of auto-confirming after OTP.</p>
                @error('maxCodAutoConfirmAmount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Max delivery attempts</label>
                <input type="text" wire:model="maxDeliveryAttempts" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                <p class="text-xs text-gray-400 mt-1">Failed delivery attempts allowed before an order is auto-cancelled and stock restored.</p>
                @error('maxDeliveryAttempts') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-800">Vendor Subscription Pricing</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Monthly plan (NGN)</label>
                    <input type="text" wire:model="subscriptionMonthlyAmount" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    @error('subscriptionMonthlyAmount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Annual plan (NGN)</label>
                    <input type="text" wire:model="subscriptionAnnualAmount" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    @error('subscriptionAnnualAmount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-800">Platform Commission</h3>
            <div>
                <label class="text-sm font-medium text-gray-700">Commission rate (%)</label>
                <input type="text" wire:model="platformCommissionRate" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                <p class="text-xs text-amber-600 mt-1">Not yet applied to payouts &mdash; vendors currently still receive 100% of collected cash. This field is stored for future use only.</p>
                @error('platformCommissionRate') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-semibold text-gray-800">Branding</h3>

            @if ($currentLogoUrl)
                <img src="{{ $currentLogoUrl }}" alt="Current logo" class="h-12">
            @endif

            <div>
                <label class="text-sm font-medium text-gray-700">App logo</label>
                <input type="file" wire:model="logo" class="mt-1 w-full text-sm">
                <p class="text-xs text-gray-400 mt-1">Shown across the dashboard, storefront, and login pages. Falls back to the "Daha Shop" text if none is uploaded.</p>
                @error('logo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
                Save Settings
            </button>
        </div>
    </form>
</div>
