@props(['href', 'active' => false])

<a href="{{ $href }}" wire:navigate
    {{ $attributes->merge(['class' => ($active ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white') . ' block rounded-md px-3 py-2 font-medium']) }}>
    {{ $slot }}
</a>
