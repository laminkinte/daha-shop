<div>
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-400">Cash Expected</div>
            <div class="text-xl font-bold text-gray-900">{{ naira($summary['expected']) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-400">Cash Collected</div>
            <div class="text-xl font-bold text-gray-900">{{ naira($summary['collected']) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-400">Cash Remitted</div>
            <div class="text-xl font-bold text-green-700">{{ naira((int) $summary['remitted']) }}</div>
        </div>
    </div>

    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white border text-gray-600' }}">All</button>
        @foreach ($statuses as $status)
            <button wire:click="$set('filter', '{{ $status->value }}')" class="text-xs px-3 py-1.5 rounded-full capitalize {{ $filter === $status->value ? 'bg-green-700 text-white' : 'bg-white border text-gray-600' }}">{{ $status->value }}</button>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Order</th>
                    <th class="px-4 py-3 text-left">Agent</th>
                    <th class="px-4 py-3 text-left">Vendor</th>
                    <th class="px-4 py-3 text-left">Expected</th>
                    <th class="px-4 py-3 text-left">Collected</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($reconciliations as $recon)
                    <tr>
                        <td class="px-4 py-3 font-medium">#{{ $recon->vendorOrder->order->order_number }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $recon->deliveryAgent->user->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $recon->vendorOrder->vendor->business_name }}</td>
                        <td class="px-4 py-3">{{ naira($recon->amount_expected) }}</td>
                        <td class="px-4 py-3">{{ naira($recon->amount_collected) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full capitalize {{ $recon->status->value === 'remitted' ? 'bg-green-100 text-green-700' : ($recon->status->value === 'short' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ $recon->status->value }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($recon->status->value !== 'remitted')
                                <div class="flex items-center gap-2 justify-end">
                                    <input type="text" wire:model="remitAmount.{{ $recon->id }}" placeholder="₦ amount" class="w-24 text-xs rounded-md border-gray-300">
                                    <button wire:click="remit({{ $recon->id }})" class="text-xs bg-green-700 text-white px-3 py-1.5 rounded-md">Mark Remitted</button>
                                </div>
                            @else
                                <span class="text-xs text-gray-400">{{ $recon->remitted_at?->format('M j') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No reconciliations in this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reconciliations->links() }}</div>
</div>
