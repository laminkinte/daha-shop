<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Welcome back, {{ Str::of(auth()->user()->name)->before(' ') }}</h2>
        <p class="text-sm text-gray-500 mt-1">Here's how your shop is performing.</p>
    </div>

    @if (!$vendor->isApproved())
        <div class="mb-6 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <span>Your vendor account is <strong class="capitalize">{{ $vendor->status->value }}</strong>. You'll be able to sell once an admin approves your account.</span>
        </div>
    @endif

    @if (!$hasActiveSubscription)
        <div class="mb-6 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm flex items-center justify-between gap-3">
            <span class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                <span>You don't have an active subscription. You'll need one before posting or listing new products.</span>
            </span>
            <a href="{{ route('vendor.subscription') }}" wire:navigate class="shrink-0 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold px-3 py-1.5 rounded-md">Subscribe now</a>
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Products</span>
                <div class="h-9 w-9 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mt-3">{{ $stats['products'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Pending Orders</span>
                <div class="h-9 w-9 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mt-3">{{ $stats['pending_orders'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Delivered This Month</span>
                <div class="h-9 w-9 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mt-3">{{ $stats['delivered_this_month'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Pending Payout</span>
                <div class="h-9 w-9 rounded-lg bg-green-50 flex items-center justify-center text-green-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.725-.659 1.622-.659 2.003-.659.545 0 1.09.181 1.505.545" /></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-green-700 mt-3">{{ naira($stats['pending_payout']) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Recent Orders</h3>
            <a href="{{ route('vendor.orders') }}" wire:navigate class="text-sm text-green-700 font-medium hover:underline">View all &rarr;</a>
        </div>
        <div class="divide-y divide-gray-100">
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
            @forelse ($recentOrders as $vendorOrder)
                <div class="px-5 py-3.5 flex items-center justify-between text-sm">
                    <div>
                        <span class="font-medium text-gray-900">#{{ $vendorOrder->order->order_number }}</span>
                        <span class="text-gray-400 ml-2">{{ $vendorOrder->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-700">{{ naira($vendorOrder->items_subtotal) }}</span>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $statusStyles[$vendorOrder->status->value] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_',' ',$vendorOrder->status->value) }}</span>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-sm text-gray-500 text-center">No orders yet.</div>
            @endforelse
        </div>
    </div>
</div>
