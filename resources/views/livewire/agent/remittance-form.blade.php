<div>
    <div class="bg-green-700 text-white rounded-lg shadow p-6 mb-6">
        <div class="text-sm text-green-100">Cash You Currently Owe MarketHub NG</div>
        <div class="text-3xl font-bold mt-1">{{ naira($totalOwed) }}</div>
        <p class="text-xs text-green-100 mt-2">Please remit this cash at the office. An admin will confirm receipt and clear it from your ledger.</p>
    </div>

    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-4 py-3 border-b font-semibold text-gray-800">Outstanding</div>
        <div class="divide-y">
            @forelse ($outstanding as $recon)
                <div class="px-4 py-3 flex items-center justify-between text-sm">
                    <div>
                        <span class="font-medium">#{{ $recon->vendorOrder->order->order_number }}</span>
                        <span class="text-gray-400 ml-2">{{ $recon->vendorOrder->vendor->business_name }}</span>
                    </div>
                    <span class="font-semibold">{{ naira($recon->amount_collected) }}</span>
                </div>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500">You have no outstanding cash to remit.</div>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b font-semibold text-gray-800">Recently Remitted</div>
        <div class="divide-y">
            @forelse ($remitted as $recon)
                <div class="px-4 py-3 flex items-center justify-between text-sm">
                    <div>
                        <span class="font-medium">#{{ $recon->vendorOrder->order->order_number }}</span>
                        <span class="text-gray-400 ml-2">{{ $recon->remitted_at?->format('M j, Y') }}</span>
                    </div>
                    <span class="font-semibold text-green-700">{{ naira($recon->remitted_amount ?? 0) }}</span>
                </div>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500">No remittance history yet.</div>
            @endforelse
        </div>
    </div>
</div>
