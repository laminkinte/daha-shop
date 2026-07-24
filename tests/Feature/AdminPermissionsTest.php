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
        $this->assertFalse($created->is_super_admin);
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
        $this->assertFalse($otherSuperAdmin->is_super_admin);
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
        $this->assertFalse($scopedAdmin->is_super_admin);
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
}
