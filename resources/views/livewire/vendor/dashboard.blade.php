<div>
    @if (!$vendor->isApproved())
        <div class="mb-6 rounded-md bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 text-sm">
            Your vendor account is <strong>{{ $vendor->status->value }}</strong>. You'll be able to sell once an admin approves your account.
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-400">Products</div>
            <div class="text-2xl font-bold text-gray-900">{{ $stats['products'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-400">Pending Orders</div>
            <div class="text-2xl font-bold text-gray-900">{{ $stats['pending_orders'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-400">Delivered This Month</div>
            <div class="text-2xl font-bold text-gray-900">{{ $stats['delivered_this_month'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-400">Pending Payout</div>
            <div class="text-2xl font-bold text-green-700">{{ naira($stats['pending_payout']) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b font-semibold text-gray-800">Recent Orders</div>
        <div class="divide-y">
            @forelse ($recentOrders as $vendorOrder)
                <div class="px-4 py-3 flex items-center justify-between text-sm">
                    <div>
                        <span class="font-medium">#{{ $vendorOrder->order->order_number }}</span>
                        <span class="text-gray-400 ml-2">{{ $vendorOrder->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span>{{ naira($vendorOrder->items_subtotal) }}</span>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full bg-gray-100 text-gray-700 capitalize">{{ str_replace('_',' ',$vendorOrder->status->value) }}</span>
                    </div>
                </div>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500">No orders yet.</div>
            @endforelse
        </div>
    </div>
</div>
