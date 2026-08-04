<?php

namespace Tests\Feature;

use App\Livewire\Admin\DeliveryZoneManager;
use App\Models\DeliveryFee;
use App\Models\DeliveryZone;
use App\Models\State;
use App\Models\User;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryZoneManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_delivery_zone_with_a_fee(): void
    {
        $this->seed(NigeriaGeographySeeder::class);
        $admin = User::factory()->admin()->create();
        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(DeliveryZoneManager::class)
            ->set('stateId', $lagos->id)
            ->set('lgaId', $ikeja->id)
            ->set('fee', '1500')
            ->call('create')
            ->assertHasNoErrors();

        $zone = DeliveryZone::where('lga_id', $ikeja->id)->firstOrFail();
        $this->assertSame(150000, $zone->fees->firstWhere('vendor_id', null)->fee);
    }

    public function test_edit_prefills_the_form_with_the_zones_existing_values(): void
    {
        $this->seed(NigeriaGeographySeeder::class);
        $admin = User::factory()->admin()->create();
        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();

        $zone = DeliveryZone::create(['name' => 'Ikeja Zone', 'state_id' => $lagos->id, 'lga_id' => $ikeja->id]);
        DeliveryFee::create(['delivery_zone_id' => $zone->id, 'vendor_id' => null, 'fee' => 150000]);

        Livewire::actingAs($admin)
            ->test(DeliveryZoneManager::class)
            ->call('edit', $zone->id)
            ->assertSet('stateId', $lagos->id)
            ->assertSet('lgaId', $ikeja->id)
            ->assertSet('fee', '1500.00')
            ->assertSet('showForm', true);
    }

    public function test_resubmitting_an_existing_zone_updates_its_fee_instead_of_duplicating(): void
    {
        $this->seed(NigeriaGeographySeeder::class);
        $admin = User::factory()->admin()->create();
        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();

        $zone = DeliveryZone::create(['name' => 'Ikeja Zone', 'state_id' => $lagos->id, 'lga_id' => $ikeja->id]);
        DeliveryFee::create(['delivery_zone_id' => $zone->id, 'vendor_id' => null, 'fee' => 150000]);

        Livewire::actingAs($admin)
            ->test(DeliveryZoneManager::class)
            ->call('edit', $zone->id)
            ->set('fee', '2000')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertSame(1, DeliveryZone::count());
        $this->assertSame(200000, $zone->fresh()->fees->firstWhere('vendor_id', null)->fee);
    }

    public function test_cancel_resets_the_form(): void
    {
        $this->seed(NigeriaGeographySeeder::class);
        $admin = User::factory()->admin()->create();
        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();

        $zone = DeliveryZone::create(['name' => 'Ikeja Zone', 'state_id' => $lagos->id, 'lga_id' => $ikeja->id]);
        DeliveryFee::create(['delivery_zone_id' => $zone->id, 'vendor_id' => null, 'fee' => 150000]);

        Livewire::actingAs($admin)
            ->test(DeliveryZoneManager::class)
            ->call('edit', $zone->id)
            ->call('cancel')
            ->assertSet('showForm', false)
            ->assertSet('stateId', null)
            ->assertSet('lgaId', null)
            ->assertSet('fee', '');
    }
}
