<div class="space-y-4">
    @php
        $statusStyles = [
            'assigned_to_agent' => 'bg-indigo-50 text-indigo-700',
            'out_for_delivery' => 'bg-sky-50 text-sky-700',
        ];
    @endphp
    @forelse ($deliveries as $vendorOrder)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <span class="font-semibold">#{{ $vendorOrder->order->order_number }}</span>
                    <span class="text-xs font-semibold ml-2 px-2.5 py-1 rounded-full capitalize {{ $statusStyles[$vendorOrder->status->value] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_',' ',$vendorOrder->status->value) }}</span>
                </div>
                <span class="font-bold text-green-700">{{ naira($vendorOrder->codTotal()) }}</span>
            </div>

            <div class="text-sm text-gray-600 mb-3">
                <div><span class="text-gray-400">Pickup:</span> {{ $vendorOrder->vendor->business_name }} &mdash; {{ $vendorOrder->vendor->business_address }}</div>
                <div><span class="text-gray-400">Deliver to:</span> {{ $vendorOrder->order->address->street_address }}, {{ $vendorOrder->order->address->area }}</div>
                <div><span class="text-gray-400">Customer phone:</span> {{ $vendorOrder->order->address->phone }}</div>
            </div>

            <div class="text-sm text-gray-600 space-y-1 mb-3 border-t border-gray-100 pt-2">
                @foreach ($vendorOrder->items as $item)
                    <div class="flex justify-between">
                        <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-2">
                @if ($vendorOrder->status->value === 'assigned_to_agent')
                    <button wire:click="markOutForDelivery({{ $vendorOrder->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Start Delivery</button>
                @else
                    <a href="{{ route('agent.deliveries.show', $vendorOrder->id) }}" wire:navigate class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Complete Delivery</a>
                @endif
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center text-gray-500">No deliveries assigned to you right now.</div>
    @endforelse
</div>
