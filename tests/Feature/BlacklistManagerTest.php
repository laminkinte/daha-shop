<?php

namespace Tests\Feature;

use App\Livewire\Admin\BlacklistManager;
use App\Models\BlacklistedNumber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BlacklistManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_blacklist_by_phone_only(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(BlacklistManager::class)
            ->set('phone', '+2348011112222')
            ->set('reason', 'Repeated fake orders')
            ->call('add')
            ->assertHasNoErrors();

        $entry = BlacklistedNumber::first();
        $this->assertSame('+2348011112222', $entry->phone);
        $this->assertNull($entry->email);
    }

    public function test_admin_can_blacklist_by_email_only(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(BlacklistManager::class)
            ->set('email', 'bad.actor@example.com')
            ->set('reason', 'Chargeback fraud')
            ->call('add')
            ->assertHasNoErrors();

        $entry = BlacklistedNumber::first();
        $this->assertSame('bad.actor@example.com', $entry->email);
        $this->assertNull($entry->phone);
    }

    public function test_admin_can_blacklist_by_both_phone_and_email(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(BlacklistManager::class)
            ->set('phone', '+2348011113333')
            ->set('email', 'both@example.com')
            ->call('add')
            ->assertHasNoErrors();

        $entry = BlacklistedNumber::first();
        $this->assertSame('+2348011113333', $entry->phone);
        $this->assertSame('both@example.com', $entry->email);
    }

    public function test_adding_an_entry_requires_at_least_a_phone_or_an_email(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(BlacklistManager::class)
            ->call('add')
            ->assertHasErrors(['phone', 'email']);

        $this->assertSame(0, BlacklistedNumber::count());
    }

    public function test_email_must_be_a_valid_email_address(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(BlacklistManager::class)
            ->set('email', 'not-an-email')
            ->call('add')
            ->assertHasErrors(['email' => 'email']);
    }

    public function test_admin_can_remove_an_entry(): void
    {
        $admin = User::factory()->admin()->create();
        $entry = BlacklistedNumber::create(['phone' => '+2348011114444', 'blocked_at' => now()]);

        Livewire::actingAs($admin)
            ->test(BlacklistManager::class)
            ->call('remove', $entry->id);

        $this->assertSame(0, BlacklistedNumber::count());
    }
}
