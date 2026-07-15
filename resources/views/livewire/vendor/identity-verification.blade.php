<div>
    {{-- Loaded unconditionally, same reasoning as register.blade.php: these
         must already be registered before Alpine discovers the camera/selfie
         components below, which can otherwise be mounted via a Livewire
         morph rather than a fresh page load. --}}
    <x-camera-capture-scripts />
    @vite('resources/js/selfie-capture.js')

    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Identity Verification</h1>
        <p class="text-sm text-gray-500 mt-1">Your KYC documents, reviewed by an admin before your account is approved.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 max-w-lg space-y-6">
        <div>
            <div class="flex items-center justify-between">
                <x-input-label :value="__('ID Document Photo')" />
                @if (! $vendor->needsIdDocumentRetake())
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                        {{ $vendor->id_document_path ? 'Submitted' : 'Not provided' }}
                    </span>
                @endif
            </div>

            @if ($vendor->needsIdDocumentRetake())
                <div class="mt-2 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <strong>An admin asked you to retake this photo:</strong> {{ $vendor->id_document_rejection_reason }}
                </div>

                <div class="mt-3">
                    <select wire:model="idDocumentType" class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 text-sm">
                        <option value="national_id">National ID Card</option>
                        <option value="passport">International Passport</option>
                    </select>
                    <x-input-error :messages="$errors->get('idDocumentType')" class="mt-2" />
                </div>

                <div x-data="{ mode: 'upload' }" class="mt-3">
                    <div class="flex gap-2 mb-2 text-xs">
                        <button type="button" @click="mode = 'upload'" :class="mode === 'upload' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-3 py-1.5 rounded-md font-medium">
                            Upload Photo
                        </button>
                        <button type="button" @click="mode = 'camera'" :class="mode === 'camera' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-3 py-1.5 rounded-md font-medium">
                            Use Camera
                        </button>
                    </div>

                    <div x-show="mode === 'upload'">
                        <input type="file" wire:model="idDocument" accept="image/*" class="block w-full text-sm">
                        <div wire:loading wire:target="idDocument" class="text-xs text-gray-500 mt-1">Checking image clarity&hellip;</div>
                    </div>

                    <template x-if="mode === 'camera'">
                        <div>
                            <x-camera-capture wireModel="idDocument" label="Capture ID Document" />
                        </div>
                    </template>
                </div>

                <x-input-error :messages="$errors->get('idDocument')" class="mt-2" />
                @if ($idDocumentClarityWarning)
                    <p class="text-xs text-red-600 mt-1">{{ $idDocumentClarityWarning }}</p>
                @endif

                <button type="button" wire:click="resubmitIdDocument" class="mt-3 w-full bg-green-700 hover:bg-green-800 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                    Resubmit ID Document
                </button>
            @elseif ($vendor->id_document_path)
                <p class="text-xs text-gray-500 mt-1">Your submitted ID document is awaiting or has passed review.</p>
            @else
                <p class="text-xs text-gray-500 mt-1">You haven't submitted an ID document yet.</p>
            @endif
        </div>

        <div class="pt-6 border-t border-gray-100">
            <div class="flex items-center justify-between">
                <x-input-label :value="__('Live Selfie')" />
                @if (! $vendor->needsSelfieRetake())
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                        {{ $vendor->selfie_path ? 'Submitted' : 'Not provided' }}
                    </span>
                @endif
            </div>

            @if ($vendor->needsSelfieRetake())
                <div class="mt-2 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <strong>An admin asked you to retake this photo:</strong> {{ $vendor->selfie_rejection_reason }}
                </div>

                <p class="text-xs text-gray-500 mt-2 mb-2">
                    Center your face in the frame &mdash; it captures automatically once you're positioned and holding still.
                </p>

                <x-selfie-capture wireModel="selfie" label="Capture Selfie" />

                <x-input-error :messages="$errors->get('selfie')" class="mt-2" />
                @if ($selfieClarityWarning)
                    <p class="text-xs text-red-600 mt-1">{{ $selfieClarityWarning }}</p>
                @endif

                <button type="button" wire:click="resubmitSelfie" class="mt-3 w-full bg-green-700 hover:bg-green-800 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                    Resubmit Selfie
                </button>
            @elseif ($vendor->selfie_path)
                <p class="text-xs text-gray-500 mt-1">Your submitted selfie is awaiting or has passed review.</p>
            @else
                <p class="text-xs text-gray-500 mt-1">You haven't submitted a selfie yet.</p>
            @endif
        </div>
    </div>
</div>
