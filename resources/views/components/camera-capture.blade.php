@props(['wireModel', 'label' => 'Capture Photo'])

@once
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
        };
    }
</script>
@endonce

<div x-data="cameraCaptureField('{{ $wireModel }}')" x-init="init()" class="border border-gray-200 rounded-lg p-3">
    <template x-if="!captured">
        <div>
            <video x-ref="video" autoplay playsinline muted class="w-full rounded-md bg-gray-900 aspect-video object-cover"></video>
            <p x-show="error" x-text="error" class="text-xs text-red-600 mt-2"></p>
            <button type="button" @click="capture()" :disabled="!ready" class="mt-2 w-full bg-green-700 disabled:opacity-50 hover:bg-green-800 text-white text-sm font-semibold py-2 rounded-md">
                {{ $label }}
            </button>
        </div>
    </template>
    <template x-if="captured">
        <div>
            <img :src="capturedSrc" class="w-full rounded-md object-cover aspect-video">
            <p x-show="uploading" class="text-xs text-gray-500 mt-2">Uploading&hellip;</p>
            <p x-show="error" x-text="error" class="text-xs text-red-600 mt-2"></p>
            <button type="button" @click="retake()" class="mt-2 w-full border border-gray-300 text-gray-700 text-sm font-semibold py-2 rounded-md">
                Retake
            </button>
        </div>
    </template>
</div>
