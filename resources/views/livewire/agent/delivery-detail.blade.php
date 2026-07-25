<div class="max-w-xl">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
        <h2 class="font-semibold text-lg mb-2">#{{ $vendorOrder->order->order_number }}</h2>
        <div class="text-sm text-gray-600 space-y-1">
            <div><span class="text-gray-400">Deliver to:</span> {{ $vendorOrder->order->address->street_address }}, {{ $vendorOrder->order->address->area }}</div>
            <div><span class="text-gray-400">Customer phone:</span> {{ $vendorOrder->order->address->phone }}</div>
            <div><span class="text-gray-400">Payment due:</span> <span class="font-bold text-green-700">{{ naira($vendorOrder->cashDueAtDelivery()) }}</span></div>
            @if ($vendorOrder->delivery_fee > 0)
                <div class="text-xs text-gray-400">Delivery fee of {{ naira($vendorOrder->delivery_fee) }} was already paid online via OPay &mdash; don't collect it again.</div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6" wire:poll.5s="refreshPaymentStatus">
        <h3 class="font-semibold text-gray-800 mb-4">Collect Digital Payment</h3>
        @if ($error)
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm mb-4">{{ $error }}</div>
        @endif
        @if ($vendorOrder->deliveryPayment)
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 mb-4">
                <p class="font-semibold text-amber-900">{{ ucfirst($vendorOrder->deliveryPayment->status->value) }} payment</p>
                <p class="text-sm text-amber-800">Ask the customer to complete payment. This screen updates automatically.</p>
                @if ($vendorOrder->deliveryPayment->cashier_url)
                    <a href="{{ $vendorOrder->deliveryPayment->cashier_url }}" target="_blank" class="inline-block mt-3 text-sm font-semibold text-green-700 underline">Open payment page</a>
                    <img src="https://quickchart.io/qr?size=240&text={{ urlencode($vendorOrder->deliveryPayment->cashier_url) }}" alt="Payment QR code" class="mt-4 w-48 h-48 border p-2 bg-white">
                @endif
            </div>
        @endif
        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium text-gray-700">Payment option</label>
                <select wire:model="paymentMethod" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    <option value="qr">Scan to Pay (QR code)</option>
                    <option value="manual">Manual payment request</option>
                </select>
            </div>
            @if ($paymentMethod === 'manual')
                <div>
                    <label class="text-sm font-medium text-gray-700">Customer phone number</label>
                    <input type="text" wire:model="customerPhone" placeholder="e.g. 08012345678" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                    @error('customerPhone') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            @endif
            <button wire:click="startPayment" wire:loading.attr="disabled" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-60">
                {{ $vendorOrder->deliveryPayment?->status?->value === 'failed' ? 'Retry Payment' : 'Start Payment' }}
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Delivery Failed?</h3>
        <div class="space-y-4">
            <select wire:model="failureReason" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                @foreach ($failureReasons as $reason)
                    <option value="{{ $reason->value }}">{{ str_replace('_', ' ', ucfirst($reason->value)) }}</option>
                @endforeach
            </select>
            <button wire:click="markFailed" wire:confirm="Mark this delivery as failed?" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-lg transition-colors">
                Report Failed Delivery
            </button>
        </div>
    </div>
</div>
