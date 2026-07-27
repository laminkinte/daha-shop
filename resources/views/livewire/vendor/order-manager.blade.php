<div wire:poll.5s="refreshPendingPayments">
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">All</button>
        @foreach ($statuses as $status)
            <button wire:click="$set('filter', '{{ $status->value }}')" class="text-xs px-3 py-1.5 rounded-full capitalize transition-colors {{ $filter === $status->value ? 'bg-green-700 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                {{ str_replace('_',' ',$status->value) }}
            </button>
        @endforeach
    </div>

    @if ($error)
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">{{ $error }}</div>
    @endif

    @php
        $statusStyles = [
            'pending' => 'bg-amber-50 text-amber-700',
            'accepted' => 'bg-blue-50 text-blue-700',
            'rejected' => 'bg-red-50 text-red-700',
            'packed' => 'bg-indigo-50 text-indigo-700',
            'assigned_to_agent' => 'bg-indigo-50 text-indigo-700',
            'out_for_delivery' => 'bg-sky-50 text-sky-700',
            'ready_for_pickup' => 'bg-indigo-50 text-indigo-700',
            'picked_up' => 'bg-emerald-50 text-emerald-700',
            'delivered' => 'bg-emerald-50 text-emerald-700',
            'failed' => 'bg-red-50 text-red-700',
            'cancelled' => 'bg-gray-100 text-gray-600',
        ];
    @endphp

    <div class="space-y-4">
        @forelse ($vendorOrders as $vendorOrder)
            @php
                $isSelfDelivering = $vendorOrder->status->value === 'out_for_delivery' && ! $vendorOrder->delivery_agent_id;
                $showPaymentCard = $isSelfDelivering || $vendorOrder->status->value === 'ready_for_pickup';
                $method = $paymentMethod[$vendorOrder->id] ?? 'qr';
            @endphp
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <span class="font-semibold">#{{ $vendorOrder->order->order_number }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $vendorOrder->created_at->diffForHumans() }}</span>
                        @if ($vendorOrder->isPickup())
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 ml-2">Pickup</span>
                        @endif
                        @if ($isSelfDelivering)
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 ml-2">Self-delivering</span>
                        @endif
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
                    <div class="flex items-center gap-2">
                        @if ($vendorOrder->status->value === 'pending')
                            <button wire:click="accept({{ $vendorOrder->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Accept</button>
                            <button wire:click="reject({{ $vendorOrder->id }})" wire:confirm="Reject this order?" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition-colors">Reject</button>
                        @elseif ($vendorOrder->status->value === 'accepted')
                            <button wire:click="pack({{ $vendorOrder->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Mark Packed &amp; Ready</button>
                        @elseif ($vendorOrder->status->value === 'packed' && $vendorOrder->isPickup())
                            <button wire:click="markReadyForPickup({{ $vendorOrder->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Mark Ready for Pickup</button>
                        @elseif ($vendorOrder->status->value === 'packed' && ! $vendorOrder->isPickup())
                            <button wire:click="deliverMyself({{ $vendorOrder->id }})" wire:confirm="Deliver this order yourself instead of waiting for an agent?" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Deliver It Myself</button>
                            <span class="text-xs text-gray-400">or wait to be assigned an agent</span>
                        @endif
                    </div>
                </div>

                @if ($showPaymentCard)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Collect Digital Payment</h4>
                        @if ($vendorOrder->deliveryPayment)
                            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 mb-3">
                                <p class="text-sm font-semibold text-amber-900">{{ ucfirst($vendorOrder->deliveryPayment->status->value) }} payment</p>
                                <p class="text-xs text-amber-800">Ask the customer to complete payment. This updates automatically.</p>
                                @if ($vendorOrder->deliveryPayment->cashier_url)
                                    <a href="{{ $vendorOrder->deliveryPayment->cashier_url }}" target="_blank" class="inline-block mt-2 text-xs font-semibold text-green-700 underline">Open payment page</a>
                                    <img src="https://quickchart.io/qr?size=200&text={{ urlencode($vendorOrder->deliveryPayment->cashier_url) }}" alt="Payment QR code" class="mt-3 w-40 h-40 border p-2 bg-white">
                                @endif
                            </div>
                        @endif
                        <div class="space-y-3">
                            <select wire:model="paymentMethod.{{ $vendorOrder->id }}" class="w-full text-sm rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                                <option value="qr">Scan to Pay (QR code)</option>
                                <option value="manual">Manual payment request</option>
                            </select>
                            @if ($method === 'manual')
                                <div>
                                    <input type="text" wire:model="customerPhone.{{ $vendorOrder->id }}" placeholder="Customer phone e.g. 08012345678" class="w-full text-sm rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                                    @error("customerPhone.{$vendorOrder->id}") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                            @endif
                            <button wire:click="startDigitalPayment({{ $vendorOrder->id }})" wire:loading.attr="disabled" class="w-full text-sm bg-green-700 hover:bg-green-800 text-white font-semibold py-2 rounded-lg transition-colors disabled:opacity-60">
                                {{ $vendorOrder->deliveryPayment?->status?->value === 'failed' ? 'Retry Payment' : 'Start Payment' }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center text-gray-500">No orders in this filter.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $vendorOrders->links() }}</div>
</div>
