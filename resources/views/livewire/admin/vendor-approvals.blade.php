<div>
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <button wire:click="$set('filter', 'all')" class="text-xs px-3 py-1.5 rounded-full transition-colors {{ $filter === 'all' ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">All</button>
        @foreach ($statuses as $status)
            <button wire:click="$set('filter', '{{ $status->value }}')" class="text-xs px-3 py-1.5 rounded-full capitalize transition-colors {{ $filter === $status->value ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">{{ $status->value }}</button>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse ($vendors as $vendor)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <div class="font-semibold text-gray-800">{{ $vendor->business_name }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $vendor->user->name }} &middot; {{ $vendor->business_phone }}</div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize {{ $vendor->status->value === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($vendor->status->value === 'suspended' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
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
                                    <img src="{{ route('admin.vendors.document', [$vendor, 'id']) }}" class="rounded-lg border border-gray-200 aspect-video object-cover w-full hover:opacity-80 transition-opacity">
                                </a>
                            @else
                                <div class="rounded-lg border border-dashed border-gray-200 aspect-video flex items-center justify-center text-xs text-gray-400">Not provided</div>
                            @endif

                            @if ($vendor->id_document_rejection_reason)
                                <div class="mt-1.5 text-xs text-red-600 bg-red-50 rounded-lg px-2.5 py-1.5">
                                    Retake requested: {{ $vendor->id_document_rejection_reason }}
                                </div>
                            @elseif ($vendor->id_document_path)
                                <div class="mt-1.5 flex items-start gap-1.5">
                                    <input type="text" wire:model="rejectionReason.{{ $vendor->id }}.id" placeholder="Reason for retake" class="flex-1 text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                                    <button wire:click="requestRetake({{ $vendor->id }}, 'id')" class="text-xs bg-amber-600 hover:bg-amber-700 text-white px-2.5 py-1.5 rounded-lg whitespace-nowrap transition-colors">Request Retake</button>
                                </div>
                                @error('rejectionReason.'.$vendor->id.'.id') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 mb-1">Live Selfie</div>
                            @if ($vendor->selfie_path)
                                <a href="{{ route('admin.vendors.document', [$vendor, 'selfie']) }}" target="_blank">
                                    <img src="{{ route('admin.vendors.document', [$vendor, 'selfie']) }}" class="rounded-lg border border-gray-200 aspect-video object-cover w-full hover:opacity-80 transition-opacity">
                                </a>
                            @else
                                <div class="rounded-lg border border-dashed border-gray-200 aspect-video flex items-center justify-center text-xs text-gray-400">Not provided</div>
                            @endif

                            @if ($vendor->selfie_rejection_reason)
                                <div class="mt-1.5 text-xs text-red-600 bg-red-50 rounded-lg px-2.5 py-1.5">
                                    Retake requested: {{ $vendor->selfie_rejection_reason }}
                                </div>
                            @elseif ($vendor->selfie_path)
                                <div class="mt-1.5 flex items-start gap-1.5">
                                    <input type="text" wire:model="rejectionReason.{{ $vendor->id }}.selfie" placeholder="Reason for retake" class="flex-1 text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                                    <button wire:click="requestRetake({{ $vendor->id }}, 'selfie')" class="text-xs bg-amber-600 hover:bg-amber-700 text-white px-2.5 py-1.5 rounded-lg whitespace-nowrap transition-colors">Request Retake</button>
                                </div>
                                @error('rejectionReason.'.$vendor->id.'.selfie') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                            @endif
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Compare the ID photo and selfie above &mdash; confirm it's the same person before approving. If a photo is unclear or doesn't match, request a retake instead of suspending the account.</p>
                @endif

                <div class="mt-3 flex justify-end gap-2">
                    @if ($vendor->status->value !== 'approved')
                        <button wire:click="approve({{ $vendor->id }})" class="text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-1.5 rounded-lg transition-colors">Approve</button>
                    @endif
                    @if ($vendor->status->value !== 'suspended')
                        <button wire:click="suspend({{ $vendor->id }})" wire:confirm="Suspend this vendor?" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition-colors">Suspend</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center text-gray-500">No vendors in this filter.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $vendors->links() }}</div>
</div>
