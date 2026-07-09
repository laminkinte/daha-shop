<div>
    <div class="flex items-center gap-2 mb-4">
        <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white border text-gray-600' }}">All</button>
        @foreach ($statuses as $status)
            <button wire:click="$set('filter', '{{ $status->value }}')" class="text-xs px-3 py-1.5 rounded-full capitalize {{ $filter === $status->value ? 'bg-green-700 text-white' : 'bg-white border text-gray-600' }}">{{ $status->value }}</button>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Business</th>
                    <th class="px-4 py-3 text-left">Owner</th>
                    <th class="px-4 py-3 text-left">Phone</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($vendors as $vendor)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $vendor->business_name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $vendor->user->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $vendor->business_phone }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full capitalize {{ $vendor->status->value === 'approved' ? 'bg-green-100 text-green-700' : ($vendor->status->value === 'suspended' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ $vendor->status->value }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if ($vendor->status->value !== 'approved')
                                <button wire:click="approve({{ $vendor->id }})" class="text-xs bg-green-700 text-white px-3 py-1.5 rounded-md">Approve</button>
                            @endif
                            @if ($vendor->status->value !== 'suspended')
                                <button wire:click="suspend({{ $vendor->id }})" wire:confirm="Suspend this vendor?" class="text-xs bg-red-600 text-white px-3 py-1.5 rounded-md">Suspend</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No vendors in this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $vendors->links() }}</div>
</div>
