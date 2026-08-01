<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\State;
use App\Models\User;
use App\Services\GeocodingService;
use Database\Seeders\NigeriaGeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeAddress(): Address
    {
        $this->seed(NigeriaGeographySeeder::class);
        $state = State::first();
        $lga = $state->lgas()->first();
        $user = User::factory()->create();

        return Address::create([
            'user_id' => $user->id,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'label' => 'Home',
            'area' => 'Area',
            'street_address' => 'Street',
            'phone' => '+2348000000000',
        ]);
    }

    public function test_geocode_saves_the_first_matching_result(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '6.5244', 'lon' => '3.3792'],
            ], 200),
        ]);

        $address = $this->makeAddress();

        app(GeocodingService::class)->geocode($address);

        $address->refresh();
        $this->assertEqualsWithDelta(6.5244, (float) $address->lat, 0.0001);
        $this->assertEqualsWithDelta(3.3792, (float) $address->lng, 0.0001);
    }

    public function test_geocode_does_nothing_when_no_result_is_found(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $address = $this->makeAddress();

        app(GeocodingService::class)->geocode($address);

        $address->refresh();
        $this->assertNull($address->lat);
        $this->assertNull($address->lng);
    }

    public function test_geocode_does_nothing_when_the_request_fails(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response(null, 500),
        ]);

        $address = $this->makeAddress();

        app(GeocodingService::class)->geocode($address);

        $address->refresh();
        $this->assertNull($address->lat);
        $this->assertNull($address->lng);
    }

    public function test_geocode_skips_the_request_entirely_once_already_resolved(): void
    {
        Http::fake();

        $address = $this->makeAddress();
        $address->update(['lat' => 1.1, 'lng' => 2.2]);

        app(GeocodingService::class)->geocode($address);

        Http::assertNothingSent();
    }
}
