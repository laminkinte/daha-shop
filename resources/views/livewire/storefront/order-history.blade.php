<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">My Orders</h1>

    @if ($orders->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <p class="text-gray-500 mt-3">You haven't placed any orders yet.</p>
            <div class="mt-4">
                <a href="{{ route('storefront.home') }}" wire:navigate class="text-green-700 font-medium hover:underline">Start shopping &rarr;</a>
            </div>
        </div>
    @else
        @php
            $statusStyles = [
                'pending_confirmation' => 'bg-amber-50 text-amber-700',
                'confirmed' => 'bg-blue-50 text-blue-700',
                'rejected' => 'bg-red-50 text-red-700',
                'processing' => 'bg-indigo-50 text-indigo-700',
                'completed' => 'bg-emerald-50 text-emerald-700',
                'cancelled' => 'bg-gray-100 text-gray-600',
            ];
        @endphp
        <div class="space-y-4">
            @foreach ($orders as $order)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center justify-between flex-wrap gap-3 hover:shadow-md transition-shadow">
                    <div>
                        <a href="{{ route('storefront.orders.show', $order->order_number) }}" wire:navigate class="font-semibold text-green-700 hover:underline">
                            #{{ $order->order_number }}
                        </a>
                        <div class="text-xs text-gray-500 mt-1.5 flex items-center gap-2">
                            <span>{{ $order->created_at->format('M j, Y') }}</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full capitalize {{ $statusStyles[$order->status->value] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_', ' ', $order->status->value) }}</span>
                            <span>{{ naira($order->cod_amount_expected) }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="reorder({{ $order->id }})" class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition-colors">
                            Reorder
                        </button>
                        <a href="{{ route('storefront.orders.show', $order->order_number) }}" wire:navigate class="text-xs bg-green-700 text-white rounded-lg px-3 py-1.5 hover:bg-green-800 transition-colors">
                            Track
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</div>
