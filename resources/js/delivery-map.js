// Leaflet's CSS is tiny, so it's imported statically here (unlike the JS
// library itself below, which is lazy-loaded) - the cost is negligible even
// though this file loads unconditionally from the storefront layout.
import 'leaflet/dist/leaflet.css';

document.addEventListener('alpine:init', () => {
    Alpine.data('deliveryMap', ({ lat, lng, vendorOrderId, destLat = null, destLng = null }) => ({
        map: null,
        marker: null,
        _unsubscribe: null,

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

            this.marker = L.marker([lat, lng]).addTo(this.map).bindPopup('Your delivery');

            // The destination point (customer's address) is resolved once,
            // server-side, by GeocodingService - it never moves, so unlike
            // the delivery marker it needs no Livewire.on() listener. It's a
            // plain circleMarker (not the default pin) so the two points
            // stay visually distinct at a glance.
            if (destLat !== null && destLng !== null) {
                const destMarker = L.circleMarker([destLat, destLng], {
                    radius: 9,
                    color: '#dc2626',
                    fillColor: '#dc2626',
                    fillOpacity: 0.9,
                    weight: 2,
                }).addTo(this.map).bindPopup('Delivery address');

                this.map.fitBounds(L.featureGroup([this.marker, destMarker]).getBounds(), { padding: [40, 40], maxZoom: 16 });
            }

            // Leaflet computes its own size/layout from the container's
            // dimensions at the moment init() runs - if that happens before
            // the browser has fully settled layout/paint for this element
            // (easy to hit here, since this all runs inside an async Alpine
            // component on a Livewire-rendered page), the map and anything
            // stacked on top of it can render incorrectly until something
            // else forces a repaint (e.g. using the zoom control). Forcing
            // this on the next tick is Leaflet's own documented fix.
            setTimeout(() => this.map.invalidateSize(), 0);

            // Livewire 3 browser event, dispatched by
            // OrderTracking::refreshAgentLocation() on each 10s poll tick.
            // Moving the existing marker (not re-creating it) is what keeps
            // this compatible with wire:ignore on the container below. Only
            // auto-pan when there's no destination point to keep in frame -
            // with one, the initial fitBounds already shows both ends and
            // re-panning on every tick would fight the user's own zoom/pan.
            this._unsubscribe = Livewire.on(`agent-location-updated.${vendorOrderId}`, ({ lat, lng }) => {
                this.marker.setLatLng([lat, lng]);
                if (destLat === null || destLng === null) {
                    this.map.panTo([lat, lng]);
                }
            });
        },

        destroy() {
            // Without this, navigating away and back via wire:navigate would
            // call L.map() again on a DOM node Leaflet already initialized,
            // which throws "Map container is already initialized".
            this.map?.remove();

            // Livewire.on() listeners otherwise outlive this component -
            // without unsubscribing, revisiting this page via wire:navigate
            // stacks up duplicate listeners for the same event.
            this._unsubscribe?.();
        },
    }));
});
