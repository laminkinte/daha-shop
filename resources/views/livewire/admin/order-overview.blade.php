<div>
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div class="flex items-center gap-2 flex-wrap">
            <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">All</button>
            <button wire:click="$set('filter', 'pending_admin_review')" class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $filter === 'pending_admin_review' ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">Awaiting Admin Review</button>
            <button wire:click="$set('filter', 'confirmed')" class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $filter === 'confirmed' ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">Confirmed</button>
            <button wire:click="$set('filter', 'rejected')" class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $filter === 'rejected' ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">Rejected</button>
        </div>
        <a href="{{ route('admin.orders.export', ['filter' => $filter]) }}" class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg transition-colors">
            Export CSV
        </a>
    </div>

    @php
        $statusStyles = [
            'pending_confirmation' => 'bg-amber-50 text-amber-700',
            'pending_admin_review' => 'bg-amber-50 text-amber-700',
            'confirmed' => 'bg-emerald-50 text-emerald-700',
            'rejected' => 'bg-red-50 text-red-700',
        ];
    @endphp

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Order</th>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">COD Total</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium">#{{ $order->order_number }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $order->user->name }}</td>
                        <td class="px-4 py-3">{{ naira($order->cod_amount_expected) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $statusStyles[$order->confirmation_status->value] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_',' ',$order->confirmation_status->value) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if ($order->confirmation_status->value === 'pending_admin_review')
                                <button wire:click="approve({{ $order->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Approve</button>
                                <button wire:click="reject({{ $order->id }})" wire:confirm="Reject this order?" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition-colors">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No orders in this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</div>
