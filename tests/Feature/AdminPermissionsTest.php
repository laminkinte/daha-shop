<?php

namespace Tests\Feature;

use App\Mail\AdminAccountCreatedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_admin_management_page(): void
    {
        $superAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin)->get(route('admin.admins'))->assertOk();
    }

    public function test_scoped_admin_cannot_access_admin_management_page(): void
    {
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($scopedAdmin)->get(route('admin.admins'))->assertForbidden();
    }

    public function test_customer_cannot_access_admin_management_page(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('admin.admins'))->assertForbidden();
    }

    public function test_super_admin_can_create_a_scoped_admin(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->set('name', 'New Admin')
            ->set('email', 'new-admin@example.com')
            ->set('selectedPermissions', ['vendors'])
            ->call('create')
            ->assertHasNoErrors();

        $created = User::where('email', 'new-admin@example.com')->firstOrFail();

        $this->assertTrue($created->isAdmin());
        $this->assertFalse($created->isSuperAdmin());
        $this->assertSame(['vendors'], $created->admin_permissions);

        Mail::assertQueued(AdminAccountCreatedMail::class, fn ($mail) => $mail->hasTo('new-admin@example.com'));
    }

    public function test_scoped_admin_can_only_reach_permitted_admin_routes(): void
    {
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($scopedAdmin)->get(route('admin.vendors'))->assertOk();
        $this->actingAs($scopedAdmin)->get(route('admin.products'))->assertForbidden();
        $this->actingAs($scopedAdmin)->get(route('admin.orders'))->assertForbidden();
        $this->actingAs($scopedAdmin)->get(route('admin.dispatch'))->assertForbidden();
        $this->actingAs($scopedAdmin)->get(route('admin.reconciliation'))->assertForbidden();
        $this->actingAs($scopedAdmin)->get(route('admin.agents'))->assertForbidden();
        $this->actingAs($scopedAdmin)->get(route('admin.delivery-zones'))->assertForbidden();
        $this->actingAs($scopedAdmin)->get(route('admin.blacklist'))->assertForbidden();
    }

    public function test_scoped_admin_can_always_reach_the_dashboard(): void
    {
        $scopedAdmin = User::factory()->scopedAdmin([])->create();

        $this->actingAs($scopedAdmin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_full_admin_reaches_every_admin_route_via_super_admin_bypass(): void
    {
        $superAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin)->get(route('admin.vendors'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.products'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.orders'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.dispatch'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.reconciliation'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.agents'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.delivery-zones'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.blacklist'))->assertOk();
    }

    public function test_super_admin_cannot_revoke_their_own_access(): void
    {
        $superAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('revoke', $superAdmin->id)
            ->assertStatus(403);

        $this->assertTrue($superAdmin->fresh()->isSuperAdmin());
    }

    public function test_super_admin_can_revoke_another_super_admin(): void
    {
        $superAdmin = User::factory()->admin()->create();
        $otherSuperAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('revoke', $otherSuperAdmin->id);

        $otherSuperAdmin->refresh();

        $this->assertTrue($otherSuperAdmin->isCustomer());
        $this->assertFalse($otherSuperAdmin->isSuperAdmin());
    }

    public function test_super_admin_can_revoke_a_scoped_admin(): void
    {
        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('revoke', $scopedAdmin->id);

        $scopedAdmin->refresh();

        $this->assertTrue($scopedAdmin->isCustomer());
        $this->assertFalse($scopedAdmin->isSuperAdmin());
        $this->assertNull($scopedAdmin->admin_permissions);

        $this->actingAs($scopedAdmin)->get(route('admin.vendors'))->assertForbidden();
    }

    public function test_super_admin_can_update_a_scoped_admins_permissions(): void
    {
        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('edit', $scopedAdmin->id)
            ->set('editingPermissions', ['vendors', 'products'])
            ->call('updateAdmin');

        $this->assertSame(['vendors', 'products'], $scopedAdmin->fresh()->admin_permissions);
    }

    public function test_super_admin_can_promote_a_scoped_admin_to_super_admin(): void
    {
        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('edit', $scopedAdmin->id)
            ->set('editingIsSuperAdmin', true)
            ->call('updateAdmin');

        $scopedAdmin->refresh();

        $this->assertTrue($scopedAdmin->isSuperAdmin());
        $this->assertNull($scopedAdmin->admin_permissions);
    }

    public function test_super_admin_can_demote_another_super_admin_to_scoped(): void
    {
        $superAdmin = User::factory()->admin()->create();
        $otherSuperAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('edit', $otherSuperAdmin->id)
            ->set('editingIsSuperAdmin', false)
            ->set('editingPermissions', ['blacklist'])
            ->call('updateAdmin');

        $otherSuperAdmin->refresh();

        $this->assertFalse($otherSuperAdmin->isSuperAdmin());
        $this->assertSame(['blacklist'], $otherSuperAdmin->admin_permissions);
    }

    public function test_super_admin_cannot_edit_their_own_account(): void
    {
        $superAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('edit', $superAdmin->id)
            ->assertStatus(403);
    }

    public function test_super_admin_can_grant_access_to_an_existing_customer(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->set('createMode', 'existing')
            ->set('existingEmail', $customer->email)
            ->set('selectedPermissions', ['vendors'])
            ->call('promoteExisting')
            ->assertHasNoErrors();

        $customer->refresh();

        $this->assertTrue($customer->isAdmin());
        $this->assertFalse($customer->isSuperAdmin());
        $this->assertSame(['vendors'], $customer->admin_permissions);

        Mail::assertQueued(\App\Mail\AdminAccessChangedMail::class, fn ($mail) => $mail->hasTo($customer->email));
    }

    public function test_cannot_grant_access_to_a_user_who_is_already_admin(): void
    {
        $superAdmin = User::factory()->admin()->create();
        $existingAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->set('createMode', 'existing')
            ->set('existingEmail', $existingAdmin->email)
            ->call('promoteExisting')
            ->assertHasErrors('existingEmail');
    }

    public function test_a_revoked_scoped_admin_can_be_reinstated_with_their_previous_permissions(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors', 'products'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('revoke', $scopedAdmin->id);

        $this->assertTrue($scopedAdmin->fresh()->isCustomer());

        $log = \App\Models\AdminActionLog::where('action', 'revoked')->where('target_id', $scopedAdmin->id)->firstOrFail();

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('reinstate', $log->id);

        $scopedAdmin->refresh();

        $this->assertTrue($scopedAdmin->isAdmin());
        $this->assertFalse($scopedAdmin->isSuperAdmin());
        $this->assertSame(['vendors', 'products'], $scopedAdmin->admin_permissions);
        $this->assertSame('reinstated', \App\Models\AdminActionLog::orderByDesc('id')->first()->action);
    }

    public function test_a_revoked_super_admin_is_reinstated_as_super_admin(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $otherSuperAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('revoke', $otherSuperAdmin->id);

        $log = \App\Models\AdminActionLog::where('action', 'revoked')->where('target_id', $otherSuperAdmin->id)->firstOrFail();

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('reinstate', $log->id);

        $this->assertTrue($otherSuperAdmin->fresh()->isSuperAdmin());
    }

    public function test_reinstate_button_only_shows_for_revoked_admins_not_already_reinstated(): void
    {
        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        $component = Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('revoke', $scopedAdmin->id);

        $component->assertSee('wire:click="reinstate(', false);

        $log = \App\Models\AdminActionLog::where('action', 'revoked')->where('target_id', $scopedAdmin->id)->firstOrFail();

        $component->call('reinstate', $log->id)
            ->assertDontSee('wire:click="reinstate(', false);
    }

    public function test_cannot_grant_access_to_a_nonexistent_email(): void
    {
        $superAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->set('createMode', 'existing')
            ->set('existingEmail', 'nobody-here@example.com')
            ->call('promoteExisting')
            ->assertHasErrors('existingEmail');
    }

    public function test_super_admin_can_block_and_unblock_an_admin(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('block', $scopedAdmin->id);

        $this->assertTrue($scopedAdmin->fresh()->isBlocked());
        // Blocking doesn't touch their standing - still a scoped admin with
        // the same permissions once unblocked.
        $this->assertTrue($scopedAdmin->fresh()->isAdmin());
        $this->assertSame(['vendors'], $scopedAdmin->fresh()->admin_permissions);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('unblock', $scopedAdmin->id);

        $this->assertFalse($scopedAdmin->fresh()->isBlocked());
    }

    public function test_super_admin_cannot_block_their_own_account(): void
    {
        $superAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('block', $superAdmin->id)
            ->assertStatus(403);
    }

    public function test_super_admin_can_delete_an_admin_account(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $scopedAdmin = User::factory()->scopedAdmin(['vendors'])->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('deleteAdmin', $scopedAdmin->id);

        $scopedAdmin->refresh();

        $this->assertTrue($scopedAdmin->isCustomer());
        $this->assertTrue($scopedAdmin->isBlocked());
        $this->assertNull($scopedAdmin->admin_permissions);

        // The account itself still exists - deleteAdmin() never touches the
        // users row, only role/permissions/blocked_at.
        $this->assertDatabaseHas('users', ['id' => $scopedAdmin->id]);
        $this->assertSame('deleted', \App\Models\AdminActionLog::orderByDesc('id')->first()->action);
    }

    public function test_super_admin_cannot_delete_their_own_account(): void
    {
        $superAdmin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->call('deleteAdmin', $superAdmin->id)
            ->assertStatus(403);
    }

    public function test_granting_access_to_an_existing_user_clears_any_block(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->admin()->create();
        $blockedCustomer = User::factory()->create(['blocked_at' => now()]);

        $this->actingAs($superAdmin);

        Livewire::test(\App\Livewire\Admin\AdminManager::class)
            ->set('createMode', 'existing')
            ->set('existingEmail', $blockedCustomer->email)
            ->set('selectedPermissions', ['vendors'])
            ->call('promoteExisting');

        $blockedCustomer->refresh();

        $this->assertTrue($blockedCustomer->isAdmin());
        $this->assertFalse($blockedCustomer->isBlocked());
    }
}
