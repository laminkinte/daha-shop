{{--
    Defines the global cameraCaptureField() function used by <x-camera-capture>.
    Include this once, unconditionally, near the top of any page that uses
    <x-camera-capture> from inside a conditionally-rendered section (e.g. a
    step wizard) - see the comment in camera-capture.blade.php for why.
--}}
<script>
    function cameraCaptureField(propertyName) {
        return {
            stream: null,
            ready: false,
            captured: false,
            capturedSrc: null,
            uploading: false,
            error: null,
            init() {
                if (!navigator.mediaDevices?.getUserMedia) {
                    this.error = 'Camera is not supported on this browser.';
                    return;
                }

                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                    .then((stream) => {
                        this.stream = stream;
                        this.$refs.video.srcObject = stream;
                        this.ready = true;
                    })
                    .catch(() => {
                        this.error = 'Camera access was denied. Please allow camera access and try again.';
                    });
            },
            capture() {
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
                this.$wire.set(propertyName, null);
                this.$nextTick(() => this.init());
            },
            destroy() {
                this.stream?.getTracks().forEach((track) => track.stop());
            },
        };
    }
</script>
