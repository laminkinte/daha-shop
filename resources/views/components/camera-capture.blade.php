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

<div x-data="cameraCaptureField('{{ $wireModel }}')" x-init="init()" class="rounded-xl border border-gray-200 bg-gray-50 p-3">
    <template x-if="!captured">
        <div>
            <div class="relative rounded-lg overflow-hidden bg-gray-900 aspect-video">
                <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover" :class="ready ? 'opacity-100' : 'opacity-0'"></video>

                <!-- Framing guide, shown once the camera is ready -->
                <div x-show="ready" x-cloak class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div class="w-2/5 aspect-[3/4] rounded-full border-2 border-white/60"></div>
                </div>

                <!-- Waiting for camera permission -->
                <div x-show="!ready && !error" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 gap-2">
                    <svg class="animate-spin h-6 w-6 text-white/70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span class="text-xs text-white/70">Requesting camera access&hellip;</span>
                </div>
            </div>

            <div x-show="error" x-cloak class="mt-2 rounded-lg bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-xs" x-text="error"></div>

            <button type="button" @click="capture()" :disabled="!ready"
                class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-green-700 disabled:opacity-50 hover:bg-green-800 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" /></svg>
                {{ $label }}
            </button>
        </div>
    </template>
    <template x-if="captured">
        <div>
            <div class="relative rounded-lg overflow-hidden aspect-video">
                <img :src="capturedSrc" class="w-full h-full object-cover">

                <div x-show="uploading" class="absolute inset-0 bg-black/40 flex items-center justify-center">
                    <svg class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                <div x-show="!uploading && !error" class="absolute top-2 right-2 h-7 w-7 rounded-full bg-emerald-500 flex items-center justify-center shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </div>
            </div>

            <p x-show="uploading" class="text-xs text-gray-500 mt-2 text-center">Uploading&hellip;</p>
            <p x-show="!uploading && !error" class="text-xs text-emerald-700 font-medium mt-2 text-center">Photo captured</p>
            <div x-show="error" x-cloak class="mt-2 rounded-lg bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-xs" x-text="error"></div>

            <button type="button" @click="retake()"
                class="mt-3 w-full inline-flex items-center justify-center gap-2 border border-gray-300 bg-white text-gray-700 text-sm font-semibold py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                Retake
            </button>
        </div>
    </template>
</div>
