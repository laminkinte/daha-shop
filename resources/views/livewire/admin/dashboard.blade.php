<div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-400">GMV (Collected)</div>
        <div class="text-2xl font-bold text-green-700">{{ naira($stats['gmv']) }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-400">Pending Vendor Approvals</div>
        <div class="text-2xl font-bold text-gray-900">{{ $stats['pending_vendor_approvals'] }}</div>
        <a href="{{ route('admin.vendors') }}" wire:navigate class="text-xs text-green-700 hover:underline">Review &rarr;</a>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-400">High-Value Orders Awaiting Review</div>
        <div class="text-2xl font-bold text-gray-900">{{ $stats['pending_admin_review'] }}</div>
        <a href="{{ route('admin.orders') }}" wire:navigate class="text-xs text-green-700 hover:underline">Review &rarr;</a>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-400">Order Rejection Rate</div>
        <div class="text-2xl font-bold text-gray-900">{{ $stats['order_rejection_rate'] }}%</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-400">Delivery Success Rate</div>
        <div class="text-2xl font-bold text-gray-900">{{ $stats['delivery_success_rate'] }}%</div>
    </div>
</div>
