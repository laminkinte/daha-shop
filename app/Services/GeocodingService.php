<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves a saved delivery address to a lat/lng point so it can be plotted
 * on the live tracking map alongside the delivery agent/vendor's position,
 * using Nominatim (OpenStreetMap's free geocoding service - no API key,
 * consistent with the Leaflet+OSM map itself).
 *
 * Nigerian addresses are often free-text and short on detail (no house
 * numbers, informal area names), so the resulting point is only as precise
 * as what Nominatim can match - typically the street or neighbourhood, not
 * the exact doorstep. The result is cached on the address once resolved so
 * this only ever runs once per address, not on every page load.
 */
class GeocodingService
{
    public function geocode(Address $address): void
    {
        if ($address->lat !== null && $address->lng !== null) {
            return;
        }

        $query = implode(', ', array_filter([
            $address->street_address,
            $address->area,
            $address->lga?->name,
            $address->state?->name,
            'Nigeria',
        ]));

        try {
            $response = Http::withHeaders(['User-Agent' => 'DahaShop/1.0 (support@dahashop.ng)'])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'ng',
                ]);

            $result = $response->json('0');

            if (! $response->successful() || ! $result) {
                return;
            }

            $address->update([
                'lat' => (float) $result['lat'],
                'lng' => (float) $result['lon'],
            ]);
        } catch (\Throwable $e) {
            // Geocoding is a nice-to-have for the tracking map, not
            // essential - the text address is already shown elsewhere on
            // the page, so a failure here must never break order tracking.
            Log::warning('Address geocoding failed', ['address_id' => $address->id, 'error' => $e->getMessage()]);
        }
    }
}
