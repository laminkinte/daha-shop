@props(['lat', 'lng', 'vendorOrderId'])

{{--
    delivery-map.js is loaded unconditionally from layouts/storefront.blade.php
    (same reasoning as qr-scanner.js) since this page is reached via
    wire:navigate - the alpine:init listener must already be registered.

    wire:ignore is critical here: Leaflet takes over this div's internal DOM
    directly, and Livewire's morph must never touch it.
--}}
<div
    wire:ignore
    x-data="deliveryMap({ lat: {{ $lat }}, lng: {{ $lng }}, vendorOrderId: {{ $vendorOrderId }} })"
    x-init="init()"
    class="{{ $attributes->get('class', 'h-64 w-full') }}"
></div>
