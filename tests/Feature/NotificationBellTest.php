<?php

namespace Tests\Feature;

use App\Livewire\NotificationBell;
use App\Models\User;
use App\Notifications\InAppAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_unread_notifications_and_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new InAppAlert(title: 'Cash collected', message: 'Test message', url: '/somewhere'));
        $user->notify(new InAppAlert(title: 'Payout paid', message: 'Another message', url: '/somewhere-else'));

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSee('2') // unread badge
            ->assertSee('Cash collected')
            ->assertSee('Payout paid');
    }

    public function test_marking_a_single_notification_read_reduces_the_unread_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new InAppAlert(title: 'Cash collected', message: 'Test message', url: '/somewhere'));

        $notificationId = $user->notifications()->first()->id;

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSee('1')
            ->call('markRead', $notificationId)
            ->assertDontSee('Mark all read');

        $this->assertNotNull($user->notifications()->first()->read_at);
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_marking_all_read_clears_the_unread_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new InAppAlert(title: 'One', message: 'a', url: '/a'));
        $user->notify(new InAppAlert(title: 'Two', message: 'b', url: '/b'));

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSee('Mark all read')
            ->call('markAllRead')
            ->assertDontSee('Mark all read');

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $owner->notify(new InAppAlert(title: 'Private', message: 'a', url: '/a'));

        $notificationId = $owner->notifications()->first()->id;

        Livewire::actingAs($intruder)
            ->test(NotificationBell::class)
            ->call('markRead', $notificationId);

        $this->assertNull($owner->notifications()->first()->read_at);
    }
}
