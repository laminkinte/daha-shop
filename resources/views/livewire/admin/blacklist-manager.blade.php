<div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex items-end gap-3 flex-wrap">
        <div>
            <label class="text-sm font-medium text-gray-700">Phone Number</label>
            <input type="text" wire:model="phone" placeholder="+234..." class="mt-1 rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
            @error('phone') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror
        </div>
        <div class="flex-1">
            <label class="text-sm font-medium text-gray-700">Reason</label>
            <input type="text" wire:model="reason" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
        </div>
        <button wire:click="add" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            Blacklist Number
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Phone</th>
                    <th class="px-4 py-3 text-left">Reason</th>
                    <th class="px-4 py-3 text-left">Blocked At</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($entries as $entry)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ $entry->phone }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $entry->reason ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $entry->blocked_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="remove({{ $entry->id }})" wire:confirm="Remove from blacklist?" class="text-xs text-green-700 hover:underline">Unblock</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No blacklisted numbers.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $entries->links() }}</div>
</div>
