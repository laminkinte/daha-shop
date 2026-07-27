{{--
    qr-scanner.js is loaded unconditionally from layouts/storefront.blade.php (not
    inlined here) for the same reason selfie-capture.js is loaded from
    register.blade.php: this component can be reached via wire:navigate from
    another page, and browsers don't reliably execute freshly-inserted <script>
    tags - the alpine:init listener needs to already be registered.
--}}
<div wire:ignore x-data="qrScanner()" x-init="init()" class="rounded-xl overflow-hidden bg-gray-900 relative aspect-[3/4] max-w-sm mx-auto">
    <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover" :class="ready ? 'opacity-100' : 'opacity-0'"></video>

    <!-- Scanning viewfinder: corner brackets over the camera feed -->
    <div x-show="ready && !found" x-cloak class="pointer-events-none absolute inset-0 flex items-center justify-center">
        <div class="relative w-2/3 aspect-square">
            <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 rounded-tl-lg border-green-400"></div>
            <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 rounded-tr-lg border-green-400"></div>
            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 rounded-bl-lg border-green-400"></div>
            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 rounded-br-lg border-green-400"></div>
            <div class="absolute inset-x-0 top-0 h-0.5 bg-green-400/80 animate-pulse"></div>
        </div>
    </div>

    <!-- Payment link found -->
    <div x-show="found" x-cloak class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/40">
        <div class="h-16 w-16 rounded-full bg-emerald-500 flex items-center justify-center shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
        </div>
    </div>

    <!-- Waiting for camera permission -->
    <div x-show="!ready && !error" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 gap-2">
        <svg class="animate-spin h-6 w-6 text-white/70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <span class="text-xs text-white/70">Requesting camera access&hellip;</span>
    </div>

    <div x-show="ready" x-cloak class="absolute bottom-3 inset-x-3 text-center">
        <span class="inline-block rounded-full bg-black/50 text-white text-xs font-medium px-3 py-1.5" x-text="statusText"></span>
    </div>

    <div x-show="error" x-cloak class="absolute inset-0 flex items-center justify-center p-6">
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-xs text-center" x-text="error"></div>
    </div>
</div>
