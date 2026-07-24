<div>
    <div class="flex justify-end mb-4">
        <button wire:click="$set('showForm', true)" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            + Create Admin
        </button>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl border border-gray-100 shadow-xl w-full max-w-lg p-6">
                <h2 class="font-semibold text-lg mb-4">Create Admin</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Name</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                        @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Email</label>
                        <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                        @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-400 mt-1">A temporary password will be generated and emailed to this address.</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Permissions</label>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->value }}" class="rounded border-gray-300 text-green-700 focus:ring-green-500">
                                    {{ $permission->label() }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="$set('showForm', false)" class="text-sm text-gray-600 px-4 py-2 hover:text-gray-800">Cancel</button>
                    <button wire:click="create" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Create</button>
                </div>
            </div>
        </div>
    @endif

    @if ($editingUserId)
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl border border-gray-100 shadow-xl w-full max-w-lg p-6">
                <h2 class="font-semibold text-lg mb-4">Edit Admin</h2>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-4">
                    <input type="checkbox" wire:model.live="editingIsSuperAdmin" class="rounded border-gray-300 text-purple-700 focus:ring-purple-500">
                    Super Admin (full access, can manage other admins)
                </label>
                <div @if ($editingIsSuperAdmin) class="opacity-40 pointer-events-none" @endif>
                    <label class="text-sm font-medium text-gray-700">Permissions</label>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach ($permissions as $permission)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="editingPermissions" value="{{ $permission->value }}" class="rounded border-gray-300 text-green-700 focus:ring-green-500">
                                {{ $permission->label() }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button wire:click="cancelEdit" class="text-sm text-gray-600 px-4 py-2 hover:text-gray-800">Cancel</button>
                    <button wire:click="updateAdmin" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Save</button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Access</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($admins as $admin)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium">{{ $admin->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $admin->email }}</td>
                        <td class="px-4 py-3">
                            @if ($admin->is_super_admin)
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-purple-50 text-purple-700">Super Admin</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($admin->admin_permissions ?? [] as $value)
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                            {{ \App\Enums\AdminPermission::from($value)->label() }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">No access granted</span>
                                    @endforelse
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($admin->id === auth()->id())
                                <span class="text-xs text-gray-400">This is you</span>
                            @else
                                <div class="flex items-center gap-3">
                                    <button wire:click="edit({{ $admin->id }})" class="text-xs font-semibold text-green-700 hover:text-green-800">Edit</button>
                                    <button wire:click="revoke({{ $admin->id }})" wire:confirm="Remove this admin's access? They will become a regular customer account." class="text-xs font-semibold text-red-600 hover:text-red-700">Revoke</button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No admins yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Recent Admin Activity</h2>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">When</th>
                        <th class="px-4 py-3 text-left">Actor</th>
                        <th class="px-4 py-3 text-left">Target</th>
                        <th class="px-4 py-3 text-left">What changed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($auditLog as $log)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3">{{ $log->actor_name }}</td>
                            <td class="px-4 py-3">{{ $log->target_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $log->summary() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No admin activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $auditLog->links() }}
        </div>
    </div>
</div>
