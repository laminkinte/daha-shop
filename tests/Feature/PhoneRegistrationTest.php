<?php

namespace Tests\Feature;

use App\Jobs\SendOtpSms;
use App\Livewire\PhoneVerificationPrompt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PhoneRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_with_phone_and_pin_creates_a_pending_unverified_account(): void
    {
        Bus::fake([SendOtpSms::class]);

        Volt::test('pages.auth.register')
            ->set('registrationMethod', 'phone')
            ->set('name', 'Phone User')
            ->set('phone', '+2348055501234')
            ->set('pin', '1234')
            ->set('pin_confirmation', '1234')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::where('phone', '+2348055501234')->firstOrFail();

        $this->assertTrue($user->uses_pin);
        $this->assertNull($user->phone_verified_at);
        $this->assertStringEndsWith('@phone.dahashop.internal', $user->email);

        Bus::assertDispatched(SendOtpSms::class, fn (SendOtpSms $job) => $job->phone === '+2348055501234');
    }

    public function test_pin_must_be_exactly_four_digits_and_confirmed(): void
    {
        Volt::test('pages.auth.register')
            ->set('registrationMethod', 'phone')
            ->set('name', 'Phone User')
            ->set('phone', '+2348055501235')
            ->set('pin', '12')
            ->set('pin_confirmation', '99')
            ->call('register')
            ->assertHasErrors(['pin']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_email_registration_also_creates_a_pending_unverified_account(): void
    {
        Bus::fake([SendOtpSms::class]);

        Volt::test('pages.auth.register')
            ->set('registrationMethod', 'email')
            ->set('name', 'Email User')
            ->set('email', 'emailuser@example.com')
            ->set('phone', '+2348055501236')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::where('email', 'emailuser@example.com')->firstOrFail();

        $this->assertFalse($user->uses_pin);
        $this->assertNull($user->phone_verified_at);

        Bus::assertDispatched(SendOtpSms::class);
    }

    public function test_verification_prompt_shows_for_unverified_users_and_hides_once_verified(): void
    {
        $user = User::factory()->create(['phone_verified_at' => null]);

        Livewire::actingAs($user)
            ->test(PhoneVerificationPrompt::class)
            ->assertSee('Complete Your Registration');

        $user->update(['phone_verified_at' => now()]);

        Livewire::actingAs($user->fresh())
            ->test(PhoneVerificationPrompt::class)
            ->assertDontSee('Complete Your Registration');
    }

    public function test_verification_prompt_does_not_show_for_guests(): void
    {
        Livewire::test(PhoneVerificationPrompt::class)
            ->assertDontSee('Complete Your Registration');
    }

    public function test_entering_the_correct_code_verifies_the_phone_number(): void
    {
        $user = User::factory()->create(['phone' => '+2348055509999', 'phone_verified_at' => null]);

        Bus::fake([SendOtpSms::class]);
        app(\App\Services\OtpService::class)->generate($user->phone, 'phone_verification');

        $code = null;
        Bus::assertDispatched(SendOtpSms::class, function (SendOtpSms $dispatched) use (&$code) {
            $code = $dispatched->code;

            return true;
        });

        Livewire::actingAs($user)
            ->test(PhoneVerificationPrompt::class)
            ->set('code', $code)
            ->call('verify')
            ->assertSet('message', null);

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_wrong_code_does_not_verify_and_shows_a_message(): void
    {
        $user = User::factory()->create(['phone_verified_at' => null]);
        app(\App\Services\OtpService::class)->generate($user->phone, 'phone_verification');

        Livewire::actingAs($user)
            ->test(PhoneVerificationPrompt::class)
            ->set('code', '000000')
            ->call('verify')
            ->assertSet('message', fn ($message) => ! empty($message));

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_dismissing_the_prompt_hides_it_without_verifying(): void
    {
        $user = User::factory()->create(['phone_verified_at' => null]);

        Livewire::actingAs($user)
            ->test(PhoneVerificationPrompt::class)
            ->assertSee('Complete Your Registration')
            ->call('dismiss')
            ->assertDontSee('Complete Your Registration');

        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_pin_accounts_lock_out_after_three_attempts_but_password_accounts_survive_three(): void
    {
        $pinUser = User::factory()->create(['uses_pin' => true, 'password' => bcrypt('1234')]);
        $passwordUser = User::factory()->create(['uses_pin' => false, 'password' => bcrypt('correct-password')]);

        // PIN account: 3 wrong attempts should trip the tighter lockout.
        for ($i = 0; $i < 3; $i++) {
            Volt::test('pages.auth.login')
                ->set('form.login', $pinUser->email)
                ->set('form.password', 'wrong')
                ->call('login');
        }

        Volt::test('pages.auth.login')
            ->set('form.login', $pinUser->email)
            ->set('form.password', '1234')
            ->call('login')
            ->assertHasErrors('form.login')
            ->assertSee('seconds')
            ->assertNoRedirect();

        // Password account: 3 wrong attempts should NOT lock it out yet (limit is 5),
        // so the correct password on the 4th attempt still succeeds.
        for ($i = 0; $i < 3; $i++) {
            Volt::test('pages.auth.login')
                ->set('form.login', $passwordUser->email)
                ->set('form.password', 'wrong')
                ->call('login');
        }

        Volt::test('pages.auth.login')
            ->set('form.login', $passwordUser->email)
            ->set('form.password', 'correct-password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect();
    }
}
