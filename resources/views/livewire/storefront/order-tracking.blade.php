<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Order #{{ $order->order_number }}</h1>
        <span class="text-sm text-gray-500">Placed {{ $order->created_at->format('M j, Y') }}</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-gray-400">Order Status</div>
                <div class="font-semibold capitalize">{{ str_replace('_', ' ', $order->status->value) }}</div>
            </div>
            <div>
                <div class="text-gray-400">Confirmation</div>
                <div class="font-semibold capitalize">{{ str_replace('_', ' ', $order->confirmation_status->value) }}</div>
            </div>
            <div>
                <div class="text-gray-400">Cash on Delivery</div>
                <div class="font-semibold">{{ naira($order->cod_amount_expected) }}</div>
            </div>
            <div>
                <div class="text-gray-400">Deliver To</div>
                <div class="font-semibold">{{ $order->address->area }}, {{ $order->address->lga->name }}</div>
            </div>
        </div>

        @if ($order->delivery_fee_total > 0)
            <div class="mt-4 pt-4 border-t border-gray-100 text-sm flex items-center gap-2">
                <span class="text-gray-400">Delivery Fee ({{ naira($order->delivery_fee_total) }}):</span>
                @if ($order->deliveryFeePaid())
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Paid via OPay</span>
                @else
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Payment pending</span>
                @endif
            </div>
        @endif
    </div>

    @php
        $deliverySteps = ['pending' => 'Placed', 'accepted' => 'Accepted', 'packed' => 'Packed', 'assigned_to_agent' => 'Assigned', 'out_for_delivery' => 'Out for Delivery', 'delivered' => 'Delivered'];
        $pickupSteps = ['pending' => 'Placed', 'accepted' => 'Accepted', 'packed' => 'Packed', 'ready_for_pickup' => 'Ready for Pickup', 'picked_up' => 'Picked Up'];
        $statusStyles = [
            'pending' => 'bg-amber-50 text-amber-700',
            'accepted' => 'bg-blue-50 text-blue-700',
            'packed' => 'bg-indigo-50 text-indigo-700',
            'assigned_to_agent' => 'bg-indigo-50 text-indigo-700',
            'out_for_delivery' => 'bg-sky-50 text-sky-700',
            'delivered' => 'bg-emerald-50 text-emerald-700',
            'ready_for_pickup' => 'bg-indigo-50 text-indigo-700',
            'picked_up' => 'bg-emerald-50 text-emerald-700',
            'rejected' => 'bg-red-50 text-red-700',
            'failed' => 'bg-red-50 text-red-700',
            'cancelled' => 'bg-gray-100 text-gray-600',
        ];
    @endphp

    @foreach ($order->vendorOrders as $vendorOrder)
        @php
            $steps = $vendorOrder->isPickup() ? $pickupSteps : $deliverySteps;
            $stepKeys = array_keys($steps);
            $isFulfilled = in_array($vendorOrder->status->value, ['delivered', 'picked_up'], true);
            // Whoever is actually out delivering this order: the assigned
            // agent, or the vendor themselves when self-delivering (no
            // agent assigned) - see OrderTracking::refreshAgentLocation().
            $tracked = $vendorOrder->deliveryAgent ?? $vendorOrder->vendor;
            $trackedLat = $vendorOrder->status === \App\Enums\VendorOrderStatus::OutForDelivery ? $tracked?->current_lat : null;
            $trackedLng = $vendorOrder->status === \App\Enums\VendorOrderStatus::OutForDelivery ? $tracked?->current_lng : null;
            $isLiveTracked = $trackedLat !== null && $trackedLng !== null;
            $currentIndex = array_search($vendorOrder->status->value, $stepKeys);
            $firstItem = $vendorOrder->items->first();
            $thumbnail = $firstItem?->product?->images->first();
        @endphp
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-6 overflow-hidden">

            @if ($isLiveTracked)
                {{-- Amazon-style hero: the map is the dominant element, with a
                     floating card carrying the status summary on top of it. --}}
                <div class="relative" style="isolation: isolate;" wire:poll.10s="refreshAgentLocation({{ $vendorOrder->id }})">
                    <x-delivery-map
                        :lat="(float) $trackedLat"
                        :lng="(float) $trackedLng"
                        :vendor-order-id="$vendorOrder->id"
                        :dest-lat="$order->address->lat !== null ? (float) $order->address->lat : null"
                        :dest-lng="$order->address->lng !== null ? (float) $order->address->lng : null"
                        class="h-80 sm:h-96 w-full"
                    />

                    {{-- Fixed on top of the map for as long as this card is
                         relevant - it has no dismiss control and nothing on
                         this page closes it; it only stops rendering once
                         the vendor order leaves this tracked state.

                         Leaflet's own internal panes/controls use z-index
                         values up to 1000 (see leaflet.css) - z-index:2000
                         !important guarantees this wins regardless, rather
                         than relying on Tailwind's z-10 competing with
                         whatever stacking context Leaflet ends up creating.
                         The transform forces its own GPU compositing layer,
                         fixing a separate issue where this card wouldn't
                         paint at all until something forced a repaint. --}}
                    <div class="absolute top-4 left-4 right-4 sm:right-auto sm:w-80 bg-white rounded-xl shadow-lg p-4" style="z-index: 2000 !important; transform: translateZ(0); will-change: transform;">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-bold text-gray-900">Arriving today</h2>
                            <a href="{{ route('storefront.orders') }}" wire:navigate class="text-xs font-semibold text-green-700 hover:underline">See all orders</a>
                        </div>

                        <div class="flex items-center gap-3 mb-4">
                            @if ($thumbnail)
                                <img src="{{ $thumbnail->url() }}" alt="" class="h-12 w-12 rounded-lg object-cover border border-gray-100">
                            @else
                                <div class="h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                                </div>
                            @endif
                            <div class="text-sm min-w-0">
                                <div class="font-medium text-gray-800 truncate">{{ $vendorOrder->vendor->business_name }}</div>
                                <div class="text-gray-400">{{ $vendorOrder->items->count() }} item{{ $vendorOrder->items->count() === 1 ? '' : 's' }}</div>
                            </div>
                        </div>

                        <div class="flex items-center">
                            @foreach ($steps as $key => $label)
                                <div class="flex-1 flex flex-col items-center relative">
                                    <div class="h-2.5 w-2.5 rounded-full {{ $loop->index <= $currentIndex ? 'bg-green-600' : 'bg-gray-200' }}"></div>
                                    <span class="text-[9px] mt-1 text-gray-500 text-center leading-tight">{{ $label }}</span>
                                    @if (!$loop->last)
                                        <div class="absolute top-1 left-1/2 w-full h-0.5 {{ $loop->index < $currentIndex ? 'bg-green-600' : 'bg-gray-200' }}"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="p-6">
                @unless ($isLiveTracked)
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-gray-800">
                            {{ $vendorOrder->vendor->business_name }}
                            @if ($vendorOrder->isPickup())
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 ml-1">Pickup</span>
                            @endif
                        </h2>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $statusStyles[$vendorOrder->status->value] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_',' ',$vendorOrder->status->value) }}</span>
                    </div>
                @endunless

                @if ($vendorOrder->isPickup())
                    <div class="mb-4 rounded-lg bg-purple-50 border border-purple-100 px-3 py-2 text-xs text-purple-800">
                        Pick up from: <strong>{{ $vendorOrder->vendor->business_name }}</strong> &mdash; {{ $vendorOrder->vendor->business_address }}
                    </div>
                @endif

                @unless ($isLiveTracked)
                    @unless (in_array($vendorOrder->status->value, ['rejected', 'failed', 'cancelled']))
                        <div class="flex items-center mb-6">
                            @foreach ($steps as $key => $label)
                                <div class="flex-1 flex flex-col items-center relative">
                                    <div class="h-3 w-3 rounded-full {{ $loop->index <= $currentIndex ? 'bg-green-600' : 'bg-gray-200' }}"></div>
                                    <span class="text-[10px] mt-1 text-gray-500 text-center">{{ $label }}</span>
                                    @if (!$loop->last)
                                        <div class="absolute top-1.5 left-1/2 w-full h-0.5 {{ $loop->index < $currentIndex ? 'bg-green-600' : 'bg-gray-200' }}"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endunless
                @endunless

                <div class="space-y-2">
                    @foreach ($vendorOrder->items as $item)
                        <div class="flex items-center justify-between text-sm border-t border-gray-100 pt-2">
                            <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                            <span class="font-medium">{{ naira($item->subtotal) }}</span>
                        </div>

                        @if ($isFulfilled)
                            <div class="bg-gray-50 rounded-lg p-3 mt-1">
                                @if ($item->review)
                                    <div class="text-xs text-gray-500">Your review: <span class="text-amber-500">{{ str_repeat('★', $item->review->rating) }}</span> {{ $item->review->comment }}</div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <select wire:model="ratings.{{ $item->id }}" class="text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                                            @for ($i = 5; $i >= 1; $i--)
                                                <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                                            @endfor
                                        </select>
                                        <input type="text" wire:model="comments.{{ $item->id }}" placeholder="Leave a review (optional)" class="flex-1 text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                                        <button wire:click="submitReview({{ $item->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Submit</button>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach

                    <div class="flex justify-between text-sm pt-2 border-t border-gray-100 font-semibold">
                        <span>{{ $vendorOrder->isPickup() ? 'Delivery Fee (pickup - none)' : 'Delivery Fee (paid via OPay)' }}</span><span>{{ naira($vendorOrder->delivery_fee) }}</span>
                    </div>
                </div>

                @if (in_array($vendorOrder->status->value, ['out_for_delivery', 'ready_for_pickup'], true))
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        @if ($vendorOrder->deliveryPayment?->status?->value === 'paid')
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">Payment received</span>
                        @else
                            <a href="{{ route('storefront.scan-to-pay') }}" wire:navigate
                                class="block w-full text-center bg-green-700 hover:bg-green-800 text-white font-semibold py-2.5 rounded-lg transition-colors">
                                Scan to Pay
                            </a>
                        @endif
                    </div>
                @endif

                @if ($vendorOrder->status === \App\Enums\VendorOrderStatus::OutForDelivery && ! $isLiveTracked)
                    <div class="mt-4 pt-4 border-t border-gray-100" wire:poll.10s="refreshAgentLocation({{ $vendorOrder->id }})">
                        <p class="text-sm text-gray-500">Waiting for your delivery agent's location&hellip;</p>
                    </div>
                @endif

                <div class="mt-4 pt-4 border-t border-gray-100 flex flex-col gap-2">
                    <button wire:click="buyNow({{ $vendorOrder->id }})" wire:loading.attr="disabled" wire:target="buyNow({{ $vendorOrder->id }})"
                        class="w-full text-sm font-semibold bg-green-700 hover:bg-green-800 text-white rounded-full py-2.5 transition-colors disabled:opacity-50">
                        Buy Now
                    </button>
                    <button wire:click="addToCart({{ $vendorOrder->id }})" wire:loading.attr="disabled" wire:target="addToCart({{ $vendorOrder->id }})"
                        class="w-full text-sm font-semibold border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-full py-2.5 transition-colors disabled:opacity-50">
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>
    @endforeach
</div>
