<div>
    @if ($subscriptionRequired)
        <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm flex items-center justify-between gap-3">
            <span>You need an active subscription before you can post or list new products.</span>
            <a href="{{ route('vendor.subscription') }}" wire:navigate class="shrink-0 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">Subscribe now</a>
        </div>
    @endif

    <div class="flex items-center justify-between mb-4">
        <div></div>
        <button wire:click="create" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            + Add Product
        </button>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" wire:key="modal">
            <div class="bg-white rounded-xl border border-gray-100 shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="font-semibold text-lg mb-4">{{ $editingId ? 'Edit Product' : 'New Product' }}</h2>

                @if ($editingStatus?->value === 'rejected' && $editingRejectionReason)
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                        <strong>This product was rejected:</strong> {{ $editingRejectionReason }}
                        <br>Update it and submit for review again.
                    </div>
                @elseif ($editingStatus?->value === 'pending_review')
                    <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
                        This product is awaiting admin review. You can still edit it while you wait.
                    </div>
                @elseif ($editingStatus?->value === 'published')
                    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                        This product is live. Changes save immediately without needing re-approval.
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Name</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                        @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Category</label>
                        <select wire:model="categoryId" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('categoryId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Description</label>
                        <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Price (₦)</label>
                            <input type="text" wire:model="price" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            @error('price') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Stock</label>
                            <input type="number" wire:model="stock" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            @error('stock') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Image (optional)</label>
                        <input type="file" wire:model="image" class="mt-1 w-full text-sm">
                        @error('image') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="$set('showForm', false)" class="text-sm text-gray-600 px-4 py-2 hover:text-gray-800">Cancel</button>

                    @if ($this->isAwaitingOrLive)
                        <button wire:click="saveChanges" wire:loading.attr="disabled" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                            Save Changes
                        </button>
                    @else
                        <button wire:click="saveAsDraft" wire:loading.attr="disabled" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                            Save as Draft
                        </button>
                        <button wire:click="submitForReview" wire:loading.attr="disabled" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                            Submit for Review
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Product</th>
                    <th class="px-4 py-3 text-left">Category</th>
                    <th class="px-4 py-3 text-left">Price</th>
                    <th class="px-4 py-3 text-left">Stock</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $product)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $product->category->name }}</td>
                        <td class="px-4 py-3">{{ naira($product->base_price) }}</td>
                        <td class="px-4 py-3">{{ $product->stock }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = [
                                    'published' => 'bg-emerald-50 text-emerald-700',
                                    'pending_review' => 'bg-amber-50 text-amber-700',
                                    'rejected' => 'bg-red-50 text-red-700',
                                    'draft' => 'bg-gray-100 text-gray-600',
                                ];
                            @endphp
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusColors[$product->status->value] }}">
                                {{ str_replace('_', ' ', ucfirst($product->status->value)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button wire:click="edit({{ $product->id }})" class="text-green-700 hover:underline text-xs">Edit</button>
                            <button wire:click="delete({{ $product->id }})" wire:confirm="Delete this product?" class="text-red-600 hover:underline text-xs">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</div>
