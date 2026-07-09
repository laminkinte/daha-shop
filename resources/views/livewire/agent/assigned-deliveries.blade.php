<div class="space-y-4">
    @forelse ($deliveries as $vendorOrder)
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <span class="font-semibold">#{{ $vendorOrder->order->order_number }}</span>
                    <span class="text-xs font-semibold ml-2 px-2 py-1 rounded-full bg-gray-100 text-gray-700 capitalize">{{ str_replace('_',' ',$vendorOrder->status->value) }}</span>
                </div>
                <span class="font-bold text-green-700">{{ naira($vendorOrder->codTotal()) }}</span>
            </div>

            <div class="text-sm text-gray-600 mb-3">
                <div><span class="text-gray-400">Pickup:</span> {{ $vendorOrder->vendor->business_name }} &mdash; {{ $vendorOrder->vendor->business_address }}</div>
                <div><span class="text-gray-400">Deliver to:</span> {{ $vendorOrder->order->address->street_address }}, {{ $vendorOrder->order->address->area }}</div>
                <div><span class="text-gray-400">Customer phone:</span> {{ $vendorOrder->order->address->phone }}</div>
            </div>

            <div class="text-sm text-gray-600 space-y-1 mb-3 border-t pt-2">
                @foreach ($vendorOrder->items as $item)
                    <div class="flex justify-between">
                        <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-2">
                @if ($vendorOrder->status->value === 'assigned_to_agent')
                    <button wire:click="markOutForDelivery({{ $vendorOrder->id }})" class="text-xs bg-green-700 text-white px-3 py-1.5 rounded-md">Start Delivery</button>
                @else
                    <a href="{{ route('agent.deliveries.show', $vendorOrder->id) }}" wire:navigate class="text-xs bg-green-700 text-white px-3 py-1.5 rounded-md">Complete Delivery</a>
                @endif
            </div>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">No deliveries assigned to you right now.</div>
    @endforelse
</div>
