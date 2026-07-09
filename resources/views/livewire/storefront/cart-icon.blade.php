<a href="{{ route('storefront.cart') }}" wire:navigate class="relative hover:text-green-200">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.877-4.985 2.542-7.611l.132-.516A1.125 1.125 0 0019.68 4.5H5.42m2.08 9.75L5.42 4.5M7.5 14.25L5.106 5.272M7.5 14.25L5.42 4.5" />
    </svg>
    @if ($count > 0)
        <span class="absolute -top-2 -right-2 bg-yellow-400 text-green-900 text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
            {{ $count }}
        </span>
    @endif
</a>
