<div>
    <div class="flex items-center justify-between mb-4">
        <div></div>
        <button wire:click="create" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-md">
            + Add Product
        </button>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" wire:key="modal">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="font-semibold text-lg mb-4">{{ $editingId ? 'Edit Product' : 'New Product' }}</h2>

                @if ($editingStatus?->value === 'rejected' && $editingRejectionReason)
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                        <strong>This product was rejected:</strong> {{ $editingRejectionReason }}
                        <br>Update it and submit for review again.
                    </div>
                @elseif ($editingStatus?->value === 'pending_review')
                    <div class="mb-4 rounded-md bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 text-sm">
                        This product is awaiting admin review. You can still edit it while you wait.
                    </div>
                @elseif ($editingStatus?->value === 'published')
                    <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
                        This product is live. Changes save immediately without needing re-approval.
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Name</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-md border-gray-300">
                        @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Category</label>
                        <select wire:model="categoryId" class="mt-1 w-full rounded-md border-gray-300">
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('categoryId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Description</label>
                        <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-md border-gray-300"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Price (₦)</label>
                            <input type="text" wire:model="price" class="mt-1 w-full rounded-md border-gray-300">
                            @error('price') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Stock</label>
                            <input type="number" wire:model="stock" class="mt-1 w-full rounded-md border-gray-300">
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
                    <button wire:click="$set('showForm', false)" class="text-sm text-gray-600 px-4 py-2">Cancel</button>

                    @if ($this->isAwaitingOrLive)
                        <button wire:click="saveChanges" wire:loading.attr="disabled" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-md">
                            Save Changes
                        </button>
                    @else
                        <button wire:click="saveAsDraft" wire:loading.attr="disabled" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2 rounded-md">
                            Save as Draft
                        </button>
                        <button wire:click="submitForReview" wire:loading.attr="disabled" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-md">
                            Submit for Review
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-x-auto">
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
            <tbody class="divide-y">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $product->category->name }}</td>
                        <td class="px-4 py-3">{{ naira($product->base_price) }}</td>
                        <td class="px-4 py-3">{{ $product->stock }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = [
                                    'published' => 'bg-green-100 text-green-700',
                                    'pending_review' => 'bg-yellow-100 text-yellow-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    'draft' => 'bg-gray-100 text-gray-600',
                                ];
                            @endphp
                            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $statusColors[$product->status->value] }}">
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
