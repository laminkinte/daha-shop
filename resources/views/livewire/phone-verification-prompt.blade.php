<div>
    @if ($shouldShow)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 text-center">
                <div class="mx-auto h-14 w-14 rounded-full bg-green-100 flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                </div>

                <h2 class="text-lg font-bold text-gray-900">Complete Your Registration</h2>
                <p class="text-sm text-gray-500 mt-2">
                    We sent a 6-digit code by SMS to <span class="font-medium">{{ auth()->user()->phone }}</span>
                    to verify your account.
                </p>

                @if ($message)
                    <div class="mt-4 rounded-md bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">{{ $message }}</div>
                @endif

                @if ($resent)
                    <div class="mt-4 rounded-md bg-green-50 border border-green-200 text-green-700 px-4 py-2 text-sm">A new code has been sent.</div>
                @endif

                <input type="text" wire:model="code" maxlength="6" placeholder="••••••"
                    class="mt-6 w-full text-center text-2xl tracking-[0.5em] rounded-md border-gray-300 focus:border-green-500 focus:ring-green-500 py-3">

                <button wire:click="verify" wire:loading.attr="disabled" class="mt-4 w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-md disabled:opacity-60">
                    Verify Phone Number
                </button>

                <div class="mt-4 flex items-center justify-between text-sm">
                    <button wire:click="resend" class="text-green-700 hover:underline">
                        Resend code
                    </button>
                    <button wire:click="dismiss" class="text-gray-400 hover:text-gray-600">
                        Remind me later
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
