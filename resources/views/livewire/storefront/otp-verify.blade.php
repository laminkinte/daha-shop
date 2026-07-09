<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <div class="mx-auto h-14 w-14 rounded-full bg-green-100 flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900">Confirm your order</h1>
        <p class="text-sm text-gray-500 mt-2">
            We sent a 6-digit code by SMS to <span class="font-medium">{{ $order->address->phone }}</span> to confirm order #{{ $order->order_number }}.
        </p>

        @if ($message)
            <div class="mt-4 rounded-md bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">{{ $message }}</div>
        @endif

        @if ($resent)
            <div class="mt-4 rounded-md bg-green-50 border border-green-200 text-green-700 px-4 py-2 text-sm">A new code has been sent.</div>
        @endif

        <input type="text" wire:model="code" maxlength="6" placeholder="••••••"
            class="mt-6 w-full text-center text-2xl tracking-[0.5em] rounded-md border-gray-300 py-3">

        <button wire:click="verify" wire:loading.attr="disabled" class="mt-6 w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-md disabled:opacity-60">
            Confirm Order
        </button>

        <button wire:click="resend" class="mt-3 text-sm text-green-700 hover:underline">
            Didn't get a code? Resend
        </button>

        @if (app()->environment('local'))
            <p class="mt-4 text-xs text-gray-400">Dev tip: check <code>storage/logs/laravel.log</code> for the SMS code (no real gateway configured).</p>
        @endif
    </div>
</div>
