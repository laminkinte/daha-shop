<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">My Orders</h1>

    @if ($orders->isEmpty())
        <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">
            You haven't placed any orders yet.
            <div class="mt-4">
                <a href="{{ route('storefront.home') }}" wire:navigate class="text-green-700 font-medium hover:underline">Start shopping &rarr;</a>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($orders as $order)
                <div class="bg-white rounded-lg shadow p-4 flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <a href="{{ route('storefront.orders.show', $order->order_number) }}" wire:navigate class="font-semibold text-green-700 hover:underline">
                            #{{ $order->order_number }}
                        </a>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $order->created_at->format('M j, Y') }} &middot;
                            <span class="capitalize">{{ str_replace('_', ' ', $order->status->value) }}</span> &middot;
                            {{ naira($order->cod_amount_expected) }}
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="reorder({{ $order->id }})" class="text-xs border border-gray-300 rounded-md px-3 py-1.5 hover:bg-gray-50">
                            Reorder
                        </button>
                        <a href="{{ route('storefront.orders.show', $order->order_number) }}" wire:navigate class="text-xs bg-green-700 text-white rounded-md px-3 py-1.5 hover:bg-green-800">
                            Track
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</div>
