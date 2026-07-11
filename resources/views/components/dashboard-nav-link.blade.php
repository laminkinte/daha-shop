@props(['href', 'active' => false])

<a href="{{ $href }}" wire:navigate
    {{ $attributes->merge(['class' => ($active
        ? 'bg-white/10 text-white shadow-sm ring-1 ring-white/10'
        : 'text-slate-400 hover:bg-white/5 hover:text-white') . ' group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors']) }}>
    @isset($icon)
        <span class="shrink-0 {{ $active ? 'text-green-400' : 'text-slate-500 group-hover:text-slate-300' }}">{{ $icon }}</span>
    @endisset
    <span>{{ $slot }}</span>
</a>
