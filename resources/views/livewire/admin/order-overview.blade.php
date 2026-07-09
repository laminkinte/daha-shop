<div>
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white border text-gray-600' }}">All</button>
        <button wire:click="$set('filter', 'pending_admin_review')" class="text-xs px-3 py-1.5 rounded-full {{ $filter === 'pending_admin_review' ? 'bg-green-700 text-white' : 'bg-white border text-gray-600' }}">Awaiting Admin Review</button>
        <button wire:click="$set('filter', 'confirmed')" class="text-xs px-3 py-1.5 rounded-full {{ $filter === 'confirmed' ? 'bg-green-700 text-white' : 'bg-white border text-gray-600' }}">Confirmed</button>
        <button wire:click="$set('filter', 'rejected')" class="text-xs px-3 py-1.5 rounded-full {{ $filter === 'rejected' ? 'bg-green-700 text-white' : 'bg-white border text-gray-600' }}">Rejected</button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
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
            <tbody class="divide-y">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3 font-medium">#{{ $order->order_number }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $order->user->name }}</td>
                        <td class="px-4 py-3">{{ naira($order->cod_amount_expected) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full bg-gray-100 text-gray-700 capitalize">{{ str_replace('_',' ',$order->confirmation_status->value) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if ($order->confirmation_status->value === 'pending_admin_review')
                                <button wire:click="approve({{ $order->id }})" class="text-xs bg-green-700 text-white px-3 py-1.5 rounded-md">Approve</button>
                                <button wire:click="reject({{ $order->id }})" wire:confirm="Reject this order?" class="text-xs bg-red-600 text-white px-3 py-1.5 rounded-md">Reject</button>
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
