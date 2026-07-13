<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center">
        @if (! $order->deliveryFeePaid())
            <div class="mx-auto h-14 w-14 rounded-full bg-indigo-50 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 4.5h16.5a1.5 1.5 0 011.5 1.5v12a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V6a1.5 1.5 0 011.5-1.5z" /></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Pay your delivery fee</h1>
            <p class="text-sm text-gray-500 mt-2">
                Order #{{ $order->order_number }}'s delivery fee of <span class="font-semibold text-gray-900">{{ naira($order->delivery_fee_total) }}</span>
                is paid online via OPay before your order can be confirmed. Your items themselves stay cash on delivery, as usual.
            </p>

            @if (session('delivery_fee_status') === 'failed')
                <div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">
                    That payment could not be confirmed. If you were charged, contact support &mdash; otherwise, please try again.
                </div>
            @endif

            <button wire:click="payDeliveryFee" wire:loading.attr="disabled" class="mt-6 w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="payDeliveryFee">Pay {{ naira($order->delivery_fee_total) }} with OPay &rarr;</span>
                <span wire:loading wire:target="payDeliveryFee">Redirecting to OPay…</span>
            </button>
        @else
            <div class="mx-auto h-14 w-14 rounded-full bg-emerald-50 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Confirm your order</h1>
            <p class="text-sm text-gray-500 mt-2">
                We sent a 6-digit code by SMS to <span class="font-medium">{{ $order->address->phone }}</span> to confirm order #{{ $order->order_number }}.
            </p>

            @if ($order->delivery_fee_total > 0)
                <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2 text-sm">
                    Delivery fee of {{ naira($order->delivery_fee_total) }} paid via OPay.
                </div>
            @endif

            @if ($message)
                <div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">{{ $message }}</div>
            @endif

            @if ($resent)
                <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2 text-sm">A new code has been sent.</div>
            @endif

            <input type="text" wire:model="code" maxlength="6" placeholder="••••••"
                class="mt-6 w-full text-center text-2xl tracking-[0.5em] rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 py-3">

            <button wire:click="verify" wire:loading.attr="disabled" class="mt-6 w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-60">
                Confirm Order
            </button>

            <button wire:click="resend" class="mt-3 text-sm text-green-700 hover:underline">
                Didn't get a code? Resend
            </button>

            @if (app()->environment('local'))
                <p class="mt-4 text-xs text-gray-400">Dev tip: check <code>storage/logs/laravel.log</code> for the SMS code (no real gateway configured).</p>
            @endif
        @endif
    </div>
</div>
