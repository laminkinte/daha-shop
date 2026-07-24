<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminManager;
use App\Mail\AdminAccessChangedMail;
use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_scoped_admin_writes_an_audit_log_entry(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(AdminManager::class)
            ->set('name', 'New Admin')
            ->set('email', 'new-admin@example.com')
            ->set('selectedPermissions', ['vendors'])
            ->call('create');

        $target = User::where('email', 'new-admin@example.com')->firstOrFail();

        $log = AdminActionLog::firstOrFail();

        $this->assertSame('created', $log->action);
        $this->assertSame($superAdmin->id, $log->actor_id);
        $this->assertSame($target->id, $log->target_id);
        $this->assertSame(['vendors'], $log->changes['permissions']);
    }

    public function test_updating_permissions_writes_a_log_entry_and_notifies_the_target(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(AdminManager::class)
            ->call('edit', $scopedAdmin->id)
            ->set('editingPermissions', ['vendors', 'products'])
            ->call('updateAdmin');

        $log = AdminActionLog::firstOrFail();

        $this->assertSame('permissions_updated', $log->action);
        $this->assertSame(['vendors'], $log->changes['before']['admin_permissions']);
        $this->assertSame(['vendors', 'products'], $log->changes['after']['admin_permissions']);

        $this->assertSame(1, $scopedAdmin->fresh()->unreadNotifications()->count());

        Mail::assertQueued(AdminAccessChangedMail::class, fn ($mail) => $mail->hasTo($scopedAdmin->email));
    }

    public function test_promoting_a_scoped_admin_writes_a_promoted_log_entry(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(AdminManager::class)
            ->call('edit', $scopedAdmin->id)
            ->set('editingIsSuperAdmin', true)
            ->call('updateAdmin');

        $this->assertSame('promoted', AdminActionLog::firstOrFail()->action);
    }

    public function test_demoting_a_super_admin_writes_a_demoted_log_entry(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $otherSuperAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(AdminManager::class)
            ->call('edit', $otherSuperAdmin->id)
            ->set('editingIsSuperAdmin', false)
            ->set('editingPermissions', ['blacklist'])
            ->call('updateAdmin');

        $this->assertSame('demoted', AdminActionLog::firstOrFail()->action);
    }

    public function test_revoking_an_admin_writes_a_revoked_log_entry_and_notifies_them(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(AdminManager::class)
            ->call('revoke', $scopedAdmin->id);

        $log = AdminActionLog::firstOrFail();

        $this->assertSame('revoked', $log->action);
        $this->assertSame(1, $scopedAdmin->fresh()->unreadNotifications()->count());

        Mail::assertQueued(AdminAccessChangedMail::class, fn ($mail) => $mail->hasTo($scopedAdmin->email));
    }

    public function test_admin_management_page_shows_recent_activity(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(AdminManager::class)
            ->call('revoke', $scopedAdmin->id)
            ->assertSee('Admin access revoked')
            ->assertSee($superAdmin->name)
            ->assertSee($scopedAdmin->name);
    }
}
