<div class="bg-white rounded-lg shadow overflow-x-auto">
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
        <tbody class="divide-y">
            @forelse ($payouts as $payout)
                <tr>
                    <td class="px-4 py-3">{{ $payout->period_start->format('M j') }} &ndash; {{ $payout->period_end->format('M j, Y') }}</td>
                    <td class="px-4 py-3 font-semibold">{{ naira($payout->total_amount) }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $payout->status->value === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
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
