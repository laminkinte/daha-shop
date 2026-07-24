<div>
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div class="flex items-center gap-2 flex-wrap">
            <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">All</button>
            @foreach ($statuses as $status)
                <button wire:click="$set('filter', '{{ $status->value }}')" class="text-xs px-3 py-1.5 rounded-full capitalize transition-colors {{ $filter === $status->value ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">{{ $status->value }}</button>
            @endforeach
        </div>
        <a href="{{ route('admin.payouts.export', ['filter' => $filter]) }}" class="text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-3 py-1.5 rounded-lg transition-colors">
            Export CSV
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Vendor</th>
                    <th class="px-4 py-3 text-left">Period</th>
                    <th class="px-4 py-3 text-left">Amount</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Reference</th>
                    <th class="px-4 py-3 text-left">Paid At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($payouts as $payout)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ $payout->vendor->business_name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $payout->period_start->format('M j') }} - {{ $payout->period_end->format('M j, Y') }}</td>
                        <td class="px-4 py-3">{{ naira($payout->total_amount) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $payout->status->value === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $payout->status->value }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $payout->reference ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $payout->paid_at?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No payouts in this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payouts->links() }}</div>
</div>
