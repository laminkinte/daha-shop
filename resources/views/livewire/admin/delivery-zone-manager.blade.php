<div>
    <div class="flex justify-end mb-4">
        <button wire:click="$set('showForm', true)" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            + Add Delivery Zone
        </button>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl border border-gray-100 shadow-xl w-full max-w-md p-6">
                <h2 class="font-semibold text-lg mb-4">Add Delivery Zone</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">State</label>
                        <select wire:model.live="stateId" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            <option value="">Select</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                            @endforeach
                        </select>
                        @error('stateId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">LGA</label>
                        <select wire:model="lgaId" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            <option value="">Select</option>
                            @foreach ($this->lgas as $lga)
                                <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                            @endforeach
                        </select>
                        @error('lgaId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Base Delivery Fee (₦)</label>
                        <input type="text" wire:model="fee" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                        @error('fee') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="$set('showForm', false)" class="text-sm text-gray-600 px-4 py-2 hover:text-gray-800">Cancel</button>
                    <button wire:click="create" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Save</button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Zone</th>
                    <th class="px-4 py-3 text-left">State</th>
                    <th class="px-4 py-3 text-left">Base Fee</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($zones as $zone)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ $zone->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $zone->state->name }}</td>
                        <td class="px-4 py-3">{{ naira($zone->fees->firstWhere('vendor_id', null)?->fee ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">No delivery zones configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
