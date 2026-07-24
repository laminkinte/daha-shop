<div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Cash Expected</span>
                <div class="h-9 w-9 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 mt-3">{{ naira($summary['expected']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Cash Collected</span>
                <div class="h-9 w-9 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 4.5h16.5a1.5 1.5 0 011.5 1.5v12a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V6a1.5 1.5 0 011.5-1.5z" /></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 mt-3">{{ naira($summary['collected']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Cash Remitted</span>
                <div class="h-9 w-9 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-green-700 mt-3">{{ naira((int) $summary['remitted']) }}</div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div class="flex items-center gap-2 flex-wrap">
            <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">All</button>
            @foreach ($statuses as $status)
                <button wire:click="$set('filter', '{{ $status->value }}')" class="text-xs px-3 py-1.5 rounded-full capitalize transition-colors {{ $filter === $status->value ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">{{ $status->value }}</button>
            @endforeach
        </div>
        <a href="{{ route('admin.reconciliation.export', ['filter' => $filter]) }}" class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg transition-colors">
            Export CSV
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
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
            <tbody class="divide-y divide-gray-100">
                @forelse ($reconciliations as $recon)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium">#{{ $recon->vendorOrder->order->order_number }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $recon->deliveryAgent->user->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $recon->vendorOrder->vendor->business_name }}</td>
                        <td class="px-4 py-3">{{ naira($recon->amount_expected) }}</td>
                        <td class="px-4 py-3">{{ naira($recon->amount_collected) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $recon->status->value === 'remitted' ? 'bg-emerald-50 text-emerald-700' : ($recon->status->value === 'short' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                {{ $recon->status->value }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($recon->status->value !== 'remitted')
                                <div class="flex items-center gap-2 justify-end">
                                    <input type="text" wire:model="remitAmount.{{ $recon->id }}" placeholder="₦ amount" class="w-24 text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                                    <button wire:click="remit({{ $recon->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Mark Remitted</button>
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
