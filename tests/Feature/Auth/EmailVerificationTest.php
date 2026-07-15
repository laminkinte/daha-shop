<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response
            ->assertSeeVolt('pages.auth.verify-email')
            ->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_registering_dispatches_the_verification_notification_for_email_accounts(): void
    {
        Notification::fake();

        \Livewire\Volt\Volt::test('pages.auth.register')
            ->set('name', 'Verify Me')
            ->set('email', 'verifyme@example.com')
            ->set('phone', '+2348033330000')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $user = User::where('email', 'verifyme@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_a_phone_pin_account_is_treated_as_already_email_verified(): void
    {
        $user = User::factory()->create(['uses_pin' => true, 'email_verified_at' => null]);

        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_dashboard_blocks_an_unverified_email_account_but_not_a_phone_pin_account(): void
    {
        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)->get(route('dashboard'))->assertRedirect(route('verification.notice'));

        $pinUser = User::factory()->create(['uses_pin' => true, 'email_verified_at' => null, 'role' => \App\Enums\UserRole::Customer]);
        $this->actingAs($pinUser)->get(route('dashboard'))->assertRedirect(route('storefront.home'));
    }
}
