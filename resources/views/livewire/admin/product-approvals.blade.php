<div>
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">All</button>
        @foreach ($statuses as $status)
            <button wire:click="$set('filter', '{{ $status->value }}')" class="text-xs px-3 py-1.5 rounded-full capitalize transition-colors {{ $filter === $status->value ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ str_replace('_', ' ', $status->value) }}
            </button>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse ($products as $product)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex gap-4">
                <div class="h-20 w-20 shrink-0 bg-gray-100 rounded-lg flex items-center justify-center text-gray-300 overflow-hidden">
                    @if ($product->images->first())
                        <img src="{{ $product->images->first()->url() }}" class="object-cover w-full h-full">
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h16M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-800">{{ $product->name }}</div>
                            <div class="text-xs text-gray-400">{{ $product->vendor->business_name }} &middot; {{ $product->category->name }}</div>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize shrink-0
                            {{ match($product->status->value) {
                                'published' => 'bg-emerald-50 text-emerald-700',
                                'pending_review' => 'bg-amber-50 text-amber-700',
                                'rejected' => 'bg-red-50 text-red-700',
                                default => 'bg-gray-100 text-gray-600',
                            } }}">
                            {{ str_replace('_', ' ', $product->status->value) }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ $product->description }}</p>

                    <div class="flex items-center gap-4 mt-2 text-sm">
                        <span class="font-semibold text-green-700">{{ naira($product->base_price) }}</span>
                        <span class="text-gray-400">Stock: {{ $product->stock }}</span>
                    </div>

                    @if ($product->status->value === 'rejected' && $product->rejection_reason)
                        <div class="mt-2 text-xs text-red-600 bg-red-50 rounded-lg px-3 py-2">
                            Rejected: {{ $product->rejection_reason }}
                        </div>
                    @endif

                    @if ($product->status->value === 'pending_review')
                        <div class="mt-3 flex items-start gap-2">
                            <input type="text" wire:model="rejectionReason.{{ $product->id }}" placeholder="Reason if rejecting (required to reject)" class="flex-1 text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            <button wire:click="approve({{ $product->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-2 rounded-lg whitespace-nowrap transition-colors">Approve</button>
                            <button wire:click="reject({{ $product->id }})" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg whitespace-nowrap transition-colors">Reject</button>
                        </div>
                        @error('rejectionReason.'.$product->id) <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center text-gray-500">No products in this filter.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</div>
