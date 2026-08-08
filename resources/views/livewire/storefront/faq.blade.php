<div x-data="{ query: '' }">
    <x-storefront.page-hero
        badge="We've got answers"
        title="Frequently Asked Questions"
        subtitle="Everything you need to know about ordering, paying, and selling on Daha Shop."
    >
        <div class="mt-6 relative max-w-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white/60 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
            <input
                type="text"
                x-model="query"
                placeholder="Search questions..."
                class="w-full rounded-xl border-0 bg-white/15 backdrop-blur-sm ring-1 ring-white/25 pl-11 pr-4 py-2.5 text-sm text-white placeholder-white/60 focus:ring-2 focus:ring-white/50 focus:bg-white/20"
            >
        </div>
    </x-storefront.page-hero>

    @php
        $faqs = [
            'How does cash on delivery work?' => 'Place your order with no upfront payment. Your order is confirmed by SMS, and you pay cash directly to the delivery agent (or the seller, for self-delivered orders) when it arrives.',
            'Can I track my order?' => "Yes. Once your order is out for delivery, open it from My Orders to see a live map of the delivery agent's position, along with a step-by-step status tracker.",
            'What if I\'m not able to accept cash delivery?' => "If a delivery attempt fails, we'll retry or reach out to reschedule. Repeated failed attempts may result in the order being cancelled.",
            'Is there a delivery fee?' => 'Delivery fees vary by seller and location, and are shown at checkout before you confirm your order. Delivery fees are paid digitally (via OPay, Paystack, or Monnify) - only the cost of the items themselves is paid in cash on delivery.',
            'How do I become a seller?' => 'Sign up and choose "Seller" during registration. You\'ll need to submit a valid ID and a live selfie for verification - an admin reviews every new seller before they can start listing products.',
            'Can I return a product?' => "Contact the seller directly through your order, or reach out via our Contact page if you need help resolving an issue with an order.",
        ];
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-14 sm:-mt-16 relative z-10 pb-16">
        <div class="max-w-3xl mx-auto space-y-3" x-data="{ open: null }">
            @foreach ($faqs as $question => $answer)
                <div
                    data-search="{{ Str::lower($question.' '.$answer) }}"
                    x-show="query === '' || $el.dataset.search.includes(query.toLowerCase())"
                    x-transition
                    class="bg-white rounded-2xl border shadow-sm overflow-hidden transition-colors duration-200"
                    :class="open === {{ $loop->index }} ? 'border-green-200 shadow-md' : 'border-gray-100'"
                >
                    <button type="button" @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}"
                        class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left group">
                        <span class="text-sm font-semibold text-gray-800 transition-colors" :class="open === {{ $loop->index }} ? 'text-green-700' : ''">{{ $question }}</span>
                        <span class="h-7 w-7 rounded-full flex items-center justify-center shrink-0 transition-colors" :class="open === {{ $loop->index }} ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" :class="open === {{ $loop->index }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                        </span>
                    </button>
                    <div x-show="open === {{ $loop->index }}" x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                        {{ $answer }}
                    </div>
                </div>
            @endforeach

            <div x-show="query !== '' && ! Array.from($el.parentElement.children).some(el => el.dataset.search?.includes(query.toLowerCase()))" x-cloak class="text-center py-10 text-sm text-gray-400">
                No questions match "<span x-text="query" class="font-medium text-gray-600"></span>". Try a different search, or
                <a href="{{ route('storefront.contact') }}" wire:navigate class="text-green-700 font-semibold hover:underline">contact us</a> directly.
            </div>
        </div>

        <div class="mt-10 text-center text-sm text-gray-500">
            Still need help? <a href="{{ route('storefront.contact') }}" wire:navigate class="text-green-700 font-semibold hover:underline">Contact us</a>.
        </div>
    </div>
</div>
