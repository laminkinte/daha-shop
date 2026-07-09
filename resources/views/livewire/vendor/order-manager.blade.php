<div>
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white text-gray-600 border' }}">All</button>
        @foreach ($statuses as $status)
            <button wire:click="$set('filter', '{{ $status->value }}')" class="text-xs px-3 py-1.5 rounded-full capitalize {{ $filter === $status->value ? 'bg-green-700 text-white' : 'bg-white text-gray-600 border' }}">
                {{ str_replace('_',' ',$status->value) }}
            </button>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse ($vendorOrders as $vendorOrder)
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <span class="font-semibold">#{{ $vendorOrder->order->order_number }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $vendorOrder->created_at->diffForHumans() }}</span>
                    </div>
                    <span class="text-xs font-semibold px-2 py-1 rounded-full bg-gray-100 text-gray-700 capitalize">{{ str_replace('_',' ',$vendorOrder->status->value) }}</span>
                </div>

                <div class="text-sm text-gray-600 space-y-1 mb-3">
                    @foreach ($vendorOrder->items as $item)
                        <div class="flex justify-between">
                            <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                            <span>{{ naira($item->subtotal) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-between items-center border-t pt-3">
                    <span class="font-semibold">{{ naira($vendorOrder->items_subtotal) }}</span>
                    <div class="space-x-2">
                        @if ($vendorOrder->status->value === 'pending')
                            <button wire:click="accept({{ $vendorOrder->id }})" class="text-xs bg-green-700 text-white px-3 py-1.5 rounded-md">Accept</button>
                            <button wire:click="reject({{ $vendorOrder->id }})" wire:confirm="Reject this order?" class="text-xs bg-red-600 text-white px-3 py-1.5 rounded-md">Reject</button>
                        @elseif ($vendorOrder->status->value === 'accepted')
                            <button wire:click="pack({{ $vendorOrder->id }})" class="text-xs bg-green-700 text-white px-3 py-1.5 rounded-md">Mark Packed &amp; Ready</button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">No orders in this filter.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $vendorOrders->links() }}</div>
</div>
