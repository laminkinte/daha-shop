const SCAN_INTERVAL_MS = 200;
const RESUME_AFTER_INVALID_MS = 1500;

document.addEventListener('alpine:init', () => {
    Alpine.data('qrScanner', () => ({
        stream: null,
        ready: false,
        found: false,
        error: null,
        statusText: 'Starting camera…',
        _jsQR: null,
        _canvas: null,
        _scanning: false,
        _timeout: null,

        async init() {
            if (!navigator.mediaDevices?.getUserMedia) {
                this.error = 'Camera is not supported on this browser.';
                return;
            }

            // Lazily loaded, same reasoning as face-api.js in selfie-capture.js:
            // a static import would delay this module registering itself with
            // Alpine until after the jsQR download finishes, risking losing the
            // race against Livewire's own script calling Alpine.start() first.
            try {
                const mod = await import('jsqr');
                // jsQR ships as a single CJS export - depending on how the
                // bundler interops that, the resolved value is either the
                // function itself or a namespace object wrapping it as
                // .default. `?? mod` covers both shapes instead of assuming one.
                this._jsQR = mod.default ?? mod;
            } catch (e) {
                this.error = 'Could not load the scanner. Check your connection and try again.';
                return;
            }

            this._canvas = document.createElement('canvas');

            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then((stream) => {
                    this.stream = stream;
                    this.$refs.video.srcObject = stream;
                    this.ready = true;
                    this.statusText = 'Point your camera at the payment code';
                    this._scanning = true;
                    this._tick();
                })
                .catch(() => {
                    this.error = 'Camera access was denied. Please allow camera access and try again.';
                });
        },

        _tick() {
            if (!this._scanning) {
                return;
            }

            const video = this.$refs.video;

            if (!video || !video.videoWidth) {
                this._timeout = setTimeout(() => this._tick(), SCAN_INTERVAL_MS);
                return;
            }

            const canvas = this._canvas;
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

            let result;
            try {
                result = this._jsQR(imageData.data, imageData.width, imageData.height);
            } catch (e) {
                this.error = 'The scanner hit an unexpected error. Please reload and try again.';
                return;
            }

            if (!result) {
                this._timeout = setTimeout(() => this._tick(), SCAN_INTERVAL_MS);
                return;
            }

            this._handleDecoded(result.data);
        },

        _handleDecoded(text) {
            if (!/^https?:\/\//i.test(text)) {
                this.statusText = "That doesn't look like a payment code — keep trying";
                this._timeout = setTimeout(() => {
                    this.statusText = 'Point your camera at the payment code';
                    this._tick();
                }, RESUME_AFTER_INVALID_MS);
                return;
            }

            this._scanning = false;
            this.found = true;
            this.statusText = 'Payment link found — opening…';
            this.stream?.getTracks().forEach((track) => track.stop());

            window.location.href = text;
        },

        destroy() {
            this._scanning = false;

            if (this._timeout) {
                clearTimeout(this._timeout);
            }

            this.stream?.getTracks().forEach((track) => track.stop());
        },
    }));
});
