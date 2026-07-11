<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Platform Overview</h2>
        <p class="text-sm text-gray-500 mt-1">A snapshot of marketplace health across vendors, orders, and delivery.</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">GMV (Collected)</span>
                <div class="h-9 w-9 rounded-lg bg-green-50 flex items-center justify-center text-green-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.725-.659 1.622-.659 2.003-.659.545 0 1.09.181 1.505.545" /></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-green-700 mt-3">{{ naira($stats['gmv']) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Pending Vendor Approvals</span>
                <div class="h-9 w-9 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75" /></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mt-3">{{ $stats['pending_vendor_approvals'] }}</div>
            <a href="{{ route('admin.vendors') }}" wire:navigate class="text-xs text-green-700 font-medium hover:underline mt-1 inline-block">Review &rarr;</a>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">High-Value Orders Awaiting Review</span>
                <div class="h-9 w-9 rounded-lg bg-red-50 flex items-center justify-center text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mt-3">{{ $stats['pending_admin_review'] }}</div>
            <a href="{{ route('admin.orders') }}" wire:navigate class="text-xs text-green-700 font-medium hover:underline mt-1 inline-block">Review &rarr;</a>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Order Rejection Rate</span>
                <div class="h-9 w-9 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mt-3">{{ $stats['order_rejection_rate'] }}%</div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Delivery Success Rate</span>
                <div class="h-9 w-9 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mt-3">{{ $stats['delivery_success_rate'] }}%</div>
        </div>
    </div>
</div>
