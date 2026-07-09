<div>
    <div class="flex justify-end mb-4">
        <button wire:click="$set('showForm', true)" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-md">
            + Onboard Agent
        </button>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
                <h2 class="font-semibold text-lg mb-4">Onboard Delivery Agent</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Name</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-md border-gray-300">
                        @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Email</label>
                            <input type="email" wire:model="email" class="mt-1 w-full rounded-md border-gray-300">
                            @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" wire:model="phone" class="mt-1 w-full rounded-md border-gray-300">
                            @error('phone') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Temporary Password</label>
                        <input type="text" wire:model="password" class="mt-1 w-full rounded-md border-gray-300">
                        @error('password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">State</label>
                            <select wire:model.live="stateId" class="mt-1 w-full rounded-md border-gray-300">
                                <option value="">Select</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                            @error('stateId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">LGA / Zone</label>
                            <select wire:model="lgaId" class="mt-1 w-full rounded-md border-gray-300">
                                <option value="">Select</option>
                                @foreach ($this->lgas as $lga)
                                    <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                @endforeach
                            </select>
                            @error('lgaId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Vehicle Type</label>
                        <select wire:model="vehicleType" class="mt-1 w-full rounded-md border-gray-300">
                            <option value="motorcycle">Motorcycle</option>
                            <option value="bicycle">Bicycle</option>
                            <option value="car">Car</option>
                            <option value="van">Van</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="$set('showForm', false)" class="text-sm text-gray-600 px-4 py-2">Cancel</button>
                    <button wire:click="create" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-md">Create</button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Zone</th>
                    <th class="px-4 py-3 text-left">Vehicle</th>
                    <th class="px-4 py-3 text-left">Availability</th>
                    <th class="px-4 py-3 text-left">Delivered</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($agents as $agent)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $agent->user->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $agent->lga?->name }}, {{ $agent->state?->name }}</td>
                        <td class="px-4 py-3 text-gray-500 capitalize">{{ $agent->vehicle_type }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full capitalize {{ $agent->availability->value === 'available' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $agent->availability->value }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $agent->delivered_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No delivery agents yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
