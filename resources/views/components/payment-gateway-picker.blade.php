@props(['selected', 'gateways'])

<div class="grid grid-cols-2 gap-3">
    @foreach ($gateways as $gateway)
        <button type="button" wire:click="$set('selectedGateway', '{{ $gateway->value }}')"
            class="flex items-center gap-3 rounded-xl border-2 p-3 transition text-left {{ $selected === $gateway->value ? 'border-green-600 bg-green-50 shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300' }}">
            <span class="h-10 w-10 shrink-0 rounded-lg flex items-center justify-center text-white font-bold text-xs tracking-wide {{ $gateway->badgeColorClass() }}">
                {{ $gateway->initials() }}
            </span>
            <span class="min-w-0 flex-1 text-sm font-semibold text-gray-800 truncate">{{ $gateway->label() }}</span>
            <span class="shrink-0 h-5 w-5 rounded-full border-2 flex items-center justify-center transition {{ $selected === $gateway->value ? 'border-green-600 bg-green-600' : 'border-gray-300 bg-white' }}">
                @if ($selected === $gateway->value)
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                @endif
            </span>
        </button>
    @endforeach
</div>
