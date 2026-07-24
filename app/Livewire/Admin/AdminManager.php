<?php

namespace App\Livewire\Admin;

use App\Enums\AdminPermission;
use App\Enums\UserRole;
use App\Mail\AdminAccessChangedMail;
use App\Mail\AdminAccountCreatedMail;
use App\Models\AdminActionLog;
use App\Models\User;
use App\Notifications\InAppAlert;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class AdminManager extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public string $name = '';

    public string $email = '';

    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public ?int $editingUserId = null;

    public bool $editingIsSuperAdmin = false;

    /** @var array<int, string> */
    public array $editingPermissions = [];

    public function create(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'selectedPermissions' => 'array',
            'selectedPermissions.*' => 'in:'.implode(',', array_column(AdminPermission::cases(), 'value')),
        ]);

        $password = Str::password(16);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            // Admins sign in with email + password, not phone/PIN, but the
            // `phone` column is NOT NULL/unique - give them a synthetic
            // placeholder, mirroring how phone/PIN accounts get a synthetic
            // placeholder email (see register.blade.php / hasRealEmail()).
            'phone' => 'admin-'.Str::random(12),
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
            'is_super_admin' => false,
            'admin_permissions' => $this->selectedPermissions,
            'email_verified_at' => now(),
        ]);

        $this->logAction('created', $user, ['permissions' => $this->selectedPermissions]);

        Mail::to($user->email)->queue(new AdminAccountCreatedMail($user, $password));

        $this->reset(['showForm', 'name', 'email', 'selectedPermissions']);
    }

    public function edit(int $userId): void
    {
        // Nobody edits their own admin standing here - avoids accidental
        // self-demotion/self-escalation. Any OTHER super-admin can be edited
        // just like a scoped admin.
        abort_if($userId === auth()->id(), 403);

        $target = User::where('role', UserRole::Admin)->findOrFail($userId);

        $this->editingUserId = $target->id;
        $this->editingIsSuperAdmin = $target->is_super_admin;
        $this->editingPermissions = $target->admin_permissions ?? [];
    }

    public function updateAdmin(): void
    {
        abort_if($this->editingUserId === auth()->id(), 403);

        $target = User::where('role', UserRole::Admin)->findOrFail($this->editingUserId);

        $this->validate([
            'editingPermissions' => 'array',
            'editingPermissions.*' => 'in:'.implode(',', array_column(AdminPermission::cases(), 'value')),
        ]);

        $before = [
            'is_super_admin' => $target->is_super_admin,
            'admin_permissions' => $target->admin_permissions,
        ];

        $target->update([
            'is_super_admin' => $this->editingIsSuperAdmin,
            // A super-admin bypasses permission checks entirely, so there's
            // nothing meaningful to store for them - only scoped admins keep
            // an explicit permission list.
            'admin_permissions' => $this->editingIsSuperAdmin ? null : $this->editingPermissions,
        ]);

        $after = [
            'is_super_admin' => $target->is_super_admin,
            'admin_permissions' => $target->admin_permissions,
        ];

        $action = match (true) {
            ! $before['is_super_admin'] && $after['is_super_admin'] => 'promoted',
            $before['is_super_admin'] && ! $after['is_super_admin'] => 'demoted',
            default => 'permissions_updated',
        };

        $log = $this->logAction($action, $target, ['before' => $before, 'after' => $after]);
        $this->notifyTarget($target, $log->summary());

        $this->reset(['editingUserId', 'editingIsSuperAdmin', 'editingPermissions']);
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingUserId', 'editingIsSuperAdmin', 'editingPermissions']);
    }

    public function revoke(int $userId): void
    {
        abort_if($userId === auth()->id(), 403);

        $target = User::where('role', UserRole::Admin)->findOrFail($userId);

        $before = [
            'is_super_admin' => $target->is_super_admin,
            'admin_permissions' => $target->admin_permissions,
        ];

        $log = $this->logAction('revoked', $target, ['before' => $before, 'after' => ['role' => 'customer']]);
        $this->notifyTarget($target, $log->summary());

        $target->update([
            'role' => UserRole::Customer,
            'is_super_admin' => false,
            'admin_permissions' => null,
        ]);
    }

    private function logAction(string $action, User $target, array $changes): AdminActionLog
    {
        $actor = auth()->user();

        return AdminActionLog::create([
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'target_id' => $target->id,
            'target_name' => $target->name,
            'target_email' => $target->email,
            'action' => $action,
            'changes' => $changes,
        ]);
    }

    private function notifyTarget(User $target, string $summary): void
    {
        $target->notify(new InAppAlert(
            title: 'Your admin access changed',
            message: $summary,
            url: route('profile'),
        ));

        Mail::to($target->email)->queue(new AdminAccessChangedMail($target, $summary));
    }

    public function render()
    {
        $admins = User::where('role', UserRole::Admin)->orderBy('name')->get();
        $auditLog = AdminActionLog::with('actor', 'target')->latest()->paginate(15);

        return view('livewire.admin.admin-manager', [
            'admins' => $admins,
            'permissions' => AdminPermission::cases(),
            'auditLog' => $auditLog,
        ]);
    }
}
