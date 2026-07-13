<div class="max-w-xl">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
        <h2 class="font-semibold text-lg mb-2">#{{ $vendorOrder->order->order_number }}</h2>
        <div class="text-sm text-gray-600 space-y-1">
            <div><span class="text-gray-400">Deliver to:</span> {{ $vendorOrder->order->address->street_address }}, {{ $vendorOrder->order->address->area }}</div>
            <div><span class="text-gray-400">Customer phone:</span> {{ $vendorOrder->order->address->phone }}</div>
            <div><span class="text-gray-400">Expected COD amount:</span> <span class="font-bold text-green-700">{{ naira($vendorOrder->codTotal()) }}</span></div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
        <h3 class="font-semibold text-gray-800 mb-4">Mark as Delivered</h3>
        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium text-gray-700">Cash Collected (₦)</label>
                <input type="text" wire:model="cashCollected" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                @error('cashCollected') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-400 mt-1">If the customer paid less than expected (e.g. no change available), enter the actual amount collected here and note it below.</p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Denomination / Change Notes (optional)</label>
                <input type="text" wire:model="denominationNotes" placeholder="e.g. Paid with ₦2000 notes, no change for ₦500" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Proof of Delivery Photo (optional)</label>
                <input type="file" wire:model="proofPhoto" class="mt-1 w-full text-sm">
                @error('proofPhoto') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <button wire:click="markDelivered" wire:loading.attr="disabled" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-60">
                Confirm Delivery &amp; Cash Collected
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
