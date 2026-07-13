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
                <div class="text-gray-400">COD Total</div>
                <div class="font-semibold">{{ naira($order->cod_amount_expected) }}</div>
            </div>
            <div>
                <div class="text-gray-400">Deliver To</div>
                <div class="font-semibold">{{ $order->address->area }}, {{ $order->address->lga->name }}</div>
            </div>
        </div>
    </div>

    @php
        $steps = ['pending' => 'Placed', 'accepted' => 'Accepted', 'packed' => 'Packed', 'assigned_to_agent' => 'Assigned', 'out_for_delivery' => 'Out for Delivery', 'delivered' => 'Delivered'];
        $stepKeys = array_keys($steps);
        $statusStyles = [
            'pending' => 'bg-amber-50 text-amber-700',
            'accepted' => 'bg-blue-50 text-blue-700',
            'packed' => 'bg-indigo-50 text-indigo-700',
            'assigned_to_agent' => 'bg-indigo-50 text-indigo-700',
            'out_for_delivery' => 'bg-sky-50 text-sky-700',
            'delivered' => 'bg-emerald-50 text-emerald-700',
            'rejected' => 'bg-red-50 text-red-700',
            'failed' => 'bg-red-50 text-red-700',
            'cancelled' => 'bg-gray-100 text-gray-600',
        ];
    @endphp

    @foreach ($order->vendorOrders as $vendorOrder)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">{{ $vendorOrder->vendor->business_name }}</h2>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $statusStyles[$vendorOrder->status->value] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_',' ',$vendorOrder->status->value) }}</span>
            </div>

            @unless (in_array($vendorOrder->status->value, ['rejected', 'failed', 'cancelled']))
                @php $currentIndex = array_search($vendorOrder->status->value, $stepKeys); @endphp
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

            <div class="space-y-2">
                @foreach ($vendorOrder->items as $item)
                    <div class="flex items-center justify-between text-sm border-t border-gray-100 pt-2">
                        <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                        <span class="font-medium">{{ naira($item->subtotal) }}</span>
                    </div>

                    @if ($vendorOrder->status->value === 'delivered')
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
                    <span>Delivery Fee</span><span>{{ naira($vendorOrder->delivery_fee) }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
