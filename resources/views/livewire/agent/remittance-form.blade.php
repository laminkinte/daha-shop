<div>
    <div class="bg-gradient-to-br from-green-700 to-green-800 text-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.725-.659 1.622-.659 2.003-.659.545 0 1.09.181 1.505.545" /></svg>
            </div>
            <div>
                <div class="text-sm text-green-100">Cash You Currently Owe Daha Shop</div>
                <div class="text-3xl font-bold mt-0.5">{{ naira($totalOwed) }}</div>
            </div>
        </div>
        <p class="text-xs text-green-100 mt-3">Please remit this cash at the office. An admin will confirm receipt and clear it from your ledger.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-6">
        <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800">Outstanding</div>
        <div class="divide-y divide-gray-100">
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

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-800">Recently Remitted</div>
        <div class="divide-y divide-gray-100">
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
