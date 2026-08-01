const MIN_SEND_INTERVAL_MS = 10000;

document.addEventListener('alpine:init', () => {
    Alpine.data('agentLocation', () => ({
        error: null,
        _watchId: null,
        _lastSentAt: 0,

        init() {
            if (!navigator.geolocation) {
                this.error = 'Location sharing is not supported on this device.';
                return;
            }

            this._watchId = navigator.geolocation.watchPosition(
                (position) => this._handlePosition(position),
                () => {
                    this.error = 'Location access was denied. Your live position will not be shared with the customer.';
                },
                { enableHighAccuracy: true, maximumAge: 10000 },
            );
        },

        _handlePosition(position) {
            // watchPosition can fire far more often than we want to hit the
            // server - this throttle (not the browser) is what keeps traffic
            // reasonable.
            const now = Date.now();
            if (now - this._lastSentAt < MIN_SEND_INTERVAL_MS) {
                return;
            }
            this._lastSentAt = now;

            this.$wire.updateLocation(position.coords.latitude, position.coords.longitude);
        },

        destroy() {
            if (this._watchId !== null) {
                navigator.geolocation.clearWatch(this._watchId);
            }
        },
    }));
});
