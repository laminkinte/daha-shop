<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanToPayTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('storefront.scan-to-pay'))->assertRedirect(route('login'));
    }

    public function test_an_authenticated_customer_can_open_the_scanner(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('storefront.scan-to-pay'))
            ->assertOk()
            ->assertSee('Scan to Pay');
    }
}
