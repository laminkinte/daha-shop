<div>
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">All</button>
        @foreach ($statuses as $status)
            <button wire:click="$set('filter', '{{ $status->value }}')" class="text-xs px-3 py-1.5 rounded-full capitalize transition-colors {{ $filter === $status->value ? 'bg-green-700 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                {{ str_replace('_',' ',$status->value) }}
            </button>
        @endforeach
    </div>

    @php
        $statusStyles = [
            'pending' => 'bg-amber-50 text-amber-700',
            'accepted' => 'bg-blue-50 text-blue-700',
            'rejected' => 'bg-red-50 text-red-700',
            'packed' => 'bg-indigo-50 text-indigo-700',
            'assigned_to_agent' => 'bg-indigo-50 text-indigo-700',
            'out_for_delivery' => 'bg-sky-50 text-sky-700',
            'delivered' => 'bg-emerald-50 text-emerald-700',
            'failed' => 'bg-red-50 text-red-700',
            'cancelled' => 'bg-gray-100 text-gray-600',
        ];
    @endphp

    <div class="space-y-4">
        @forelse ($vendorOrders as $vendorOrder)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <span class="font-semibold">#{{ $vendorOrder->order->order_number }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $vendorOrder->created_at->diffForHumans() }}</span>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $statusStyles[$vendorOrder->status->value] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_',' ',$vendorOrder->status->value) }}</span>
                </div>

                <div class="text-sm text-gray-600 space-y-1 mb-3">
                    @foreach ($vendorOrder->items as $item)
                        <div class="flex justify-between">
                            <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                            <span>{{ naira($item->subtotal) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-between items-center border-t border-gray-100 pt-3">
                    <span class="font-semibold">{{ naira($vendorOrder->items_subtotal) }}</span>
                    <div class="space-x-2">
                        @if ($vendorOrder->status->value === 'pending')
                            <button wire:click="accept({{ $vendorOrder->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Accept</button>
                            <button wire:click="reject({{ $vendorOrder->id }})" wire:confirm="Reject this order?" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition-colors">Reject</button>
                        @elseif ($vendorOrder->status->value === 'accepted')
                            <button wire:click="pack({{ $vendorOrder->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Mark Packed &amp; Ready</button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center text-gray-500">No orders in this filter.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $vendorOrders->links() }}</div>
</div>
