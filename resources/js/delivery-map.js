// Leaflet's CSS is tiny, so it's imported statically here (unlike the JS
// library itself below, which is lazy-loaded) - the cost is negligible even
// though this file loads unconditionally from the storefront layout.
import 'leaflet/dist/leaflet.css';

document.addEventListener('alpine:init', () => {
    Alpine.data('deliveryMap', ({ lat, lng, vendorOrderId }) => ({
        map: null,
        marker: null,

        async init() {
            // Lazily loaded for the same reason jsQR/face-api.js are lazy in
            // qr-scanner.js/selfie-capture.js: a static top-level import
            // would delay this module registering with Alpine until the
            // download finishes, risking losing the race against Livewire's
            // own Alpine.start() call.
            const mod = await import('leaflet');
            const L = mod.default ?? mod;

            // Leaflet's default marker icon paths are computed relative to
            // its own CSS by default, which breaks under Vite's asset
            // pipeline (icons render as broken images) unless explicitly
            // re-pointed at the bundled asset URLs.
            const iconUrl = (await import('leaflet/dist/images/marker-icon.png')).default;
            const iconRetinaUrl = (await import('leaflet/dist/images/marker-icon-2x.png')).default;
            const shadowUrl = (await import('leaflet/dist/images/marker-shadow.png')).default;
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({ iconUrl, iconRetinaUrl, shadowUrl });

            this.map = L.map(this.$el).setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(this.map);

            this.marker = L.marker([lat, lng]).addTo(this.map);

            // Livewire 3 browser event, dispatched by
            // OrderTracking::refreshAgentLocation() on each 10s poll tick.
            // Moving the existing marker/map (not re-creating them) is what
            // keeps this compatible with wire:ignore on the container below.
            Livewire.on(`agent-location-updated.${vendorOrderId}`, ({ lat, lng }) => {
                this.marker.setLatLng([lat, lng]);
                this.map.panTo([lat, lng]);
            });
        },

        destroy() {
            // Without this, navigating away and back via wire:navigate would
            // call L.map() again on a DOM node Leaflet already initialized,
            // which throws "Map container is already initialized".
            this.map?.remove();
        },
    }));
});
