<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
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
                    <td class="px-4 py-3">{{ $payout->period_start->format('M j') }} &ndash; {{ $payout->period_end->format('M j, Y') }}</td>
                    <td class="px-4 py-3 font-semibold">{{ naira($payout->total_amount) }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $payout->status->value === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                            {{ ucfirst($payout->status->value) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $payout->reference ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $payout->paid_at?->format('M j, Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No payouts yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $payouts->links() }}</div>
</div>
