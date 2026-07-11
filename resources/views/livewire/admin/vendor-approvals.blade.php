<div>
    <div class="flex items-center gap-2 mb-4">
        <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white border text-gray-600' }}">All</button>
        @foreach ($statuses as $status)
            <button wire:click="$set('filter', '{{ $status->value }}')" class="text-xs px-3 py-1.5 rounded-full capitalize {{ $filter === $status->value ? 'bg-green-700 text-white' : 'bg-white border text-gray-600' }}">{{ $status->value }}</button>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse ($vendors as $vendor)
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <div class="font-semibold text-gray-800">{{ $vendor->business_name }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $vendor->user->name }} &middot; {{ $vendor->business_phone }}</div>
                    </div>
                    <span class="text-xs font-semibold px-2 py-1 rounded-full capitalize {{ $vendor->status->value === 'approved' ? 'bg-green-100 text-green-700' : ($vendor->status->value === 'suspended' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ $vendor->status->value }}
                    </span>
                </div>

                @if ($vendor->id_document_path || $vendor->selfie_path)
                    <div class="mt-3 grid grid-cols-2 gap-3 max-w-md">
                        <div>
                            <div class="text-xs text-gray-400 mb-1">
                                ID Document @if($vendor->id_document_type) ({{ str_replace('_', ' ', ucfirst($vendor->id_document_type)) }}) @endif
                            </div>
                            @if ($vendor->id_document_path)
                                <a href="{{ route('admin.vendors.document', [$vendor, 'id']) }}" target="_blank">
                                    <img src="{{ route('admin.vendors.document', [$vendor, 'id']) }}" class="rounded-md border border-gray-200 aspect-video object-cover w-full hover:opacity-80 transition">
                                </a>
                            @else
                                <div class="rounded-md border border-dashed border-gray-200 aspect-video flex items-center justify-center text-xs text-gray-400">Not provided</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 mb-1">Live Selfie</div>
                            @if ($vendor->selfie_path)
                                <a href="{{ route('admin.vendors.document', [$vendor, 'selfie']) }}" target="_blank">
                                    <img src="{{ route('admin.vendors.document', [$vendor, 'selfie']) }}" class="rounded-md border border-gray-200 aspect-video object-cover w-full hover:opacity-80 transition">
                                </a>
                            @else
                                <div class="rounded-md border border-dashed border-gray-200 aspect-video flex items-center justify-center text-xs text-gray-400">Not provided</div>
                            @endif
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Compare the ID photo and selfie above &mdash; confirm it's the same person before approving.</p>
                @endif

                <div class="mt-3 flex justify-end gap-2">
                    @if ($vendor->status->value !== 'approved')
                        <button wire:click="approve({{ $vendor->id }})" class="text-xs bg-green-700 text-white px-3 py-1.5 rounded-md">Approve</button>
                    @endif
                    @if ($vendor->status->value !== 'suspended')
                        <button wire:click="suspend({{ $vendor->id }})" wire:confirm="Suspend this vendor?" class="text-xs bg-red-600 text-white px-3 py-1.5 rounded-md">Suspend</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-12 text-center text-gray-500">No vendors in this filter.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $vendors->links() }}</div>
</div>
