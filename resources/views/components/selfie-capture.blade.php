@props(['wireModel', 'label' => 'Capture Selfie'])

{{--
    The selfie-capture.js Vite entry is loaded from register.blade.php itself
    (unconditionally, on first page load), not from here. This component only
    ever appears in the DOM after a Livewire step transition (an AJAX-driven
    DOM morph, not a fresh page load), and browsers do not reliably execute
    <script> tags that are inserted that way - the module needs to already be
    loaded and its `alpine:init` listener already registered before Alpine
    discovers this element and calls x-init.
--}}

<div x-data="selfieCapture('{{ $wireModel }}')" x-init="init()" class="rounded-xl border border-gray-200 bg-gray-50 p-3">
    <template x-if="!captured">
        <div>
            <div class="relative rounded-lg overflow-hidden bg-gray-900 aspect-video">
                <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover" :class="ready ? 'opacity-100' : 'opacity-0'"></video>

                <!-- Framing guide: turns green once the face is well positioned -->
                <div x-show="ready" x-cloak class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div class="w-2/5 aspect-[3/4] rounded-full border-[3px] transition-colors duration-300"
                        :class="faceOk ? 'border-emerald-400' : 'border-white/60'"></div>
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

            <div x-show="ready && !error" class="mt-2">
                <p class="text-xs text-center font-medium" :class="faceOk ? 'text-emerald-700' : 'text-gray-500'" x-text="statusText"></p>

                <div x-show="modelsLoaded" x-cloak class="mt-1.5 h-1.5 rounded-full bg-gray-200 overflow-hidden">
                    <div class="h-full bg-emerald-500 transition-all duration-150 ease-linear" :style="'width: ' + (holdProgress * 100) + '%'"></div>
                </div>
            </div>

            <div x-show="error" x-cloak class="mt-2 rounded-lg bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-xs" x-text="error"></div>

            <button type="button" @click="capture()" :disabled="!ready"
                class="mt-3 w-full inline-flex items-center justify-center gap-2 bg-green-700 disabled:opacity-50 hover:bg-green-800 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" /></svg>
                <span x-text="modelsLoaded ? 'Capture manually' : '{{ $label }}'"></span>
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
