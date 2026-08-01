@props(['lat', 'lng', 'vendorOrderId', 'destLat' => null, 'destLng' => null])

{{--
    delivery-map.js is loaded unconditionally from layouts/storefront.blade.php
    (same reasoning as qr-scanner.js) since this page is reached via
    wire:navigate - the alpine:init listener must already be registered.

    wire:ignore is critical here: Leaflet takes over this div's internal DOM
    directly, and Livewire's morph must never touch it.

    destLat/destLng (the customer's delivery address, resolved once by
    GeocodingService) are optional - they're null until geocoding succeeds,
    in which case only the live delivery-person marker is shown.
--}}
<div
    wire:ignore
    x-data="deliveryMap({ lat: {{ $lat }}, lng: {{ $lng }}, vendorOrderId: {{ $vendorOrderId }}, destLat: {{ $destLat ?? 'null' }}, destLng: {{ $destLng ?? 'null' }} })"
    x-init="init()"
    class="{{ $attributes->get('class', 'h-64 w-full') }}"
></div>
