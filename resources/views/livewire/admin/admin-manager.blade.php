<div>
    <div class="flex justify-end mb-4">
        <button wire:click="$set('showForm', true)" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            + Create Admin
        </button>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl border border-gray-100 shadow-xl w-full max-w-lg p-6">
                <h2 class="font-semibold text-lg mb-4">{{ $createMode === 'existing' ? 'Grant Admin Access' : 'Create Admin' }}</h2>

                <div class="flex gap-2 mb-4">
                    <button type="button" wire:click="$set('createMode', 'new')" class="text-xs font-semibold px-3 py-1.5 rounded-full transition-colors {{ $createMode === 'new' ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                        New Person
                    </button>
                    <button type="button" wire:click="$set('createMode', 'existing')" class="text-xs font-semibold px-3 py-1.5 rounded-full transition-colors {{ $createMode === 'existing' ? 'bg-green-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                        Existing User
                    </button>
                </div>

                <div class="space-y-4">
                    @if ($createMode === 'existing')
                        <div>
                            <label class="text-sm font-medium text-gray-700">Email of existing user</label>
                            <input type="email" wire:model="existingEmail" placeholder="someone@example.com" class="mt-1 w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500">
                            @error('existingEmail') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-400 mt-1">Grants admin access to an existing customer, vendor, or agent account &mdash; including someone whose admin access was previously revoked. They keep their current password.</p>
                        </div>
                    @else
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
                    @endif
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
                    <button wire:click="cancelCreate" class="text-sm text-gray-600 px-4 py-2 hover:text-gray-800">Cancel</button>
                    @if ($createMode === 'existing')
                        <button wire:click="promoteExisting" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Grant Access</button>
                    @else
                        <button wire:click="create" class="bg-green-700 hover:bg-green-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Create</button>
                    @endif
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

    <div class="flex justify-end mb-3">
        <x-admin.export-button :href="route('admin.admins.export')" />
    </div>

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
                            <div class="flex flex-wrap gap-1">
                                @if ($admin->isSuperAdmin())
                                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-purple-50 text-purple-700">Super Admin</span>
                                @else
                                    @forelse ($admin->admin_permissions ?? [] as $value)
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                            {{ \App\Enums\AdminPermission::from($value)->label() }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">No access granted</span>
                                    @endforelse
                                @endif
                                @if ($admin->isBlocked())
                                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-50 text-red-700">Blocked</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($admin->id === auth()->id())
                                <span class="text-xs text-gray-400">This is you</span>
                            @else
                                <div class="flex items-center gap-3">
                                    <button wire:click="edit({{ $admin->id }})" class="text-xs font-semibold text-green-700 hover:text-green-800">Edit</button>
                                    @if ($admin->isBlocked())
                                        <button wire:click="unblock({{ $admin->id }})" class="text-xs font-semibold text-blue-700 hover:text-blue-800">Unblock</button>
                                    @else
                                        <button wire:click="block({{ $admin->id }})" wire:confirm="Block {{ $admin->name }}? They won't be able to log in at all until unblocked, but keep their current permissions." class="text-xs font-semibold text-amber-600 hover:text-amber-700">Block</button>
                                    @endif
                                    <button wire:click="revoke({{ $admin->id }})" wire:confirm="Remove this admin's access? They will become a regular customer account." class="text-xs font-semibold text-red-600 hover:text-red-700">Revoke</button>
                                    <button wire:click="deleteAdmin({{ $admin->id }})" wire:confirm="Delete {{ $admin->name }}'s admin account? This removes admin access AND blocks them from logging in at all. Their underlying account and order/vendor history, if any, are kept." class="text-xs font-semibold text-red-700 hover:text-red-800">Delete</button>
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
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-800">Recent Admin Activity</h2>
            <x-admin.export-button :href="route('admin.admins.audit-log.export')">Export Audit Log</x-admin.export-button>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">When</th>
                        <th class="px-4 py-3 text-left">Actor</th>
                        <th class="px-4 py-3 text-left">Target</th>
                        <th class="px-4 py-3 text-left">What changed</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($auditLog as $log)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3">{{ $log->actor_name }}</td>
                            <td class="px-4 py-3">{{ $log->target_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $log->summary() }}</td>
                            <td class="px-4 py-3">
                                @if ($log->action === 'revoked' && $log->target && $log->target->id !== auth()->id() && ! $log->target->isAdmin())
                                    <button wire:click="reinstate({{ $log->id }})" wire:confirm="Reinstate {{ $log->target_name }}'s previous admin access?" class="text-xs font-semibold text-green-700 hover:text-green-800">
                                        Reinstate
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No admin activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $auditLog->links() }}
        </div>
    </div>
</div>
