import * as faceapi from 'face-api.js';

const HOLD_MS = 1200;
const DETECT_INTERVAL_MS = 200;
const TOO_FAR_RATIO = 0.26;
const TOO_CLOSE_RATIO = 0.8;
const CENTER_TOLERANCE_X = 0.18;
const CENTER_TOLERANCE_Y = 0.22;

document.addEventListener('alpine:init', () => {
    Alpine.data('selfieCapture', (propertyName) => ({
        stream: null,
        ready: false,
        captured: false,
        capturedSrc: null,
        uploading: false,
        error: null,
        modelsLoaded: false,
        faceOk: false,
        holdProgress: 0,
        statusText: 'Starting camera…',
        _interval: null,
        _holdStart: null,

        async init() {
            if (!navigator.mediaDevices?.getUserMedia) {
                this.error = 'Camera is not supported on this browser.';
                return;
            }

            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
                this.modelsLoaded = true;
            } catch (e) {
                // Real-time face detection is a progressive enhancement - if the
                // model can't load (offline, blocked request, etc.) the manual
                // capture button below still works.
                this.modelsLoaded = false;
            }

            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                .then((stream) => {
                    this.stream = stream;
                    this.$refs.video.srcObject = stream;
                    this.ready = true;
                    this.statusText = this.modelsLoaded
                        ? 'Position your face in the frame'
                        : 'Camera ready — tap to capture';

                    if (this.modelsLoaded) {
                        this._startDetection();
                    }
                })
                .catch(() => {
                    this.error = 'Camera access was denied. Please allow camera access and try again.';
                });
        },

        _startDetection() {
            const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });

            this._interval = setInterval(async () => {
                if (!this.ready || this.captured) {
                    return;
                }

                const video = this.$refs.video;

                if (!video.videoWidth) {
                    return;
                }

                let detection;

                try {
                    detection = await faceapi.detectSingleFace(video, options);
                } catch (e) {
                    return;
                }

                if (!detection) {
                    this._resetHold('Position your face in the frame');
                    return;
                }

                const { x, y, width, height } = detection.box;
                const vw = video.videoWidth;
                const vh = video.videoHeight;
                const centerX = x + width / 2;
                const centerY = y + height / 2;
                const sizeRatio = width / vw;

                const centeredX = Math.abs(centerX - vw / 2) < vw * CENTER_TOLERANCE_X;
                const centeredY = Math.abs(centerY - vh / 2) < vh * CENTER_TOLERANCE_Y;

                if (sizeRatio <= TOO_FAR_RATIO) {
                    this._resetHold('Move closer');
                } else if (sizeRatio >= TOO_CLOSE_RATIO) {
                    this._resetHold('Move back a little');
                } else if (!centeredX || !centeredY) {
                    this._resetHold('Center your face in the frame');
                } else {
                    this.faceOk = true;
                    this.statusText = 'Hold still…';

                    if (!this._holdStart) {
                        this._holdStart = Date.now();
                    }

                    this.holdProgress = Math.min(1, (Date.now() - this._holdStart) / HOLD_MS);

                    if (this.holdProgress >= 1) {
                        this.capture();
                    }
                }
            }, DETECT_INTERVAL_MS);
        },

        _resetHold(message) {
            this.faceOk = false;
            this._holdStart = null;
            this.holdProgress = 0;
            this.statusText = message;
        },

        capture() {
            if (this._interval) {
                clearInterval(this._interval);
                this._interval = null;
            }

            const video = this.$refs.video;
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            this.capturedSrc = canvas.toDataURL('image/jpeg', 0.9);
            this.captured = true;
            this.uploading = true;
            this.error = null;

            canvas.toBlob((blob) => {
                const file = new File([blob], propertyName + '.jpg', { type: 'image/jpeg' });
                this.$wire.upload(propertyName, file,
                    () => { this.uploading = false; },
                    () => { this.uploading = false; this.error = 'Upload failed, please try again.'; }
                );
            }, 'image/jpeg', 0.9);

            this.stream?.getTracks().forEach((track) => track.stop());
        },

        retake() {
            this.captured = false;
            this.capturedSrc = null;
            this.error = null;
            this.faceOk = false;
            this.holdProgress = 0;
            this._holdStart = null;
            this.$wire.set(propertyName, null);
            this.$nextTick(() => this.init());
        },

        destroy() {
            if (this._interval) {
                clearInterval(this._interval);
            }

            this.stream?.getTracks().forEach((track) => track.stop());
        },
    }));
});
