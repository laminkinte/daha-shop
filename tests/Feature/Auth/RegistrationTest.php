<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('phone', '+2348011112222')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_customer_completes_registration_in_two_steps_via_the_wizard(): void
    {
        Volt::test('pages.auth.register')
            ->assertSet('step', 1)
            ->set('accountType', 'customer')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('name', 'Wizard Customer')
            ->set('email', 'wizard-customer@example.com')
            ->set('phone', '+2348011119999')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_step_two_cannot_be_advanced_past_without_required_fields(): void
    {
        Volt::test('pages.auth.register')
            ->set('accountType', 'seller')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->call('nextStep')
            ->assertHasErrors(['name', 'email', 'phone', 'password'])
            ->assertSet('step', 2);
    }

    public function test_seller_wizard_requires_all_four_steps_before_registering(): void
    {
        Volt::test('pages.auth.register')
            ->set('accountType', 'seller')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('name', 'Wizard Seller')
            ->set('email', 'wizard-seller@example.com')
            ->set('phone', '+2348011118888')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('nextStep')
            ->assertSet('step', 3)
            ->call('nextStep')
            ->assertHasErrors(['business_name', 'business_phone', 'business_address'])
            ->assertSet('step', 3)
            ->set('business_name', 'Wizard Shop')
            ->set('business_phone', '+2348011117777')
            ->set('business_address', '1 Wizard Street')
            ->call('nextStep')
            ->assertSet('step', 4);
    }

    public function test_previous_step_navigates_back_without_validating(): void
    {
        Volt::test('pages.auth.register')
            ->set('accountType', 'seller')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->call('previousStep')
            ->assertSet('step', 1)
            ->assertHasNoErrors();
    }
}
