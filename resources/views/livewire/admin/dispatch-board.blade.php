<div>
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Dispatch</h2>
        <p class="text-sm text-gray-500 mt-1">Orders packed and ready to assign to a delivery agent.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Order</th>
                    <th class="px-4 py-3 text-left">Vendor</th>
                    <th class="px-4 py-3 text-left">Amount</th>
                    <th class="px-4 py-3 text-left">Assign Agent</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($packedOrders as $vendorOrder)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium">#{{ $vendorOrder->order->order_number }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $vendorOrder->vendor->business_name }}</td>
                        <td class="px-4 py-3">{{ naira($vendorOrder->codTotal()) }}</td>
                        <td class="px-4 py-3">
                            <select wire:model="selectedAgent.{{ $vendorOrder->id }}" class="text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                                <option value="">Select agent</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}">{{ $agent->user->name }} ({{ $agent->lga?->name }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="assign({{ $vendorOrder->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Assign</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No orders are ready for pickup right now.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
