<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Frequently Asked Questions</h1>

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

    <div class="space-y-3" x-data="{ open: null }">
        @foreach ($faqs as $question => $answer)
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <button type="button" @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}"
                    class="w-full flex items-center justify-between px-5 py-4 text-left">
                    <span class="text-sm font-semibold text-gray-800">{{ $question }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 shrink-0 transition-transform" :class="open === {{ $loop->index }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open === {{ $loop->index }}" class="px-5 pb-4 text-sm text-gray-600">
                    {{ $answer }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 text-sm text-gray-500">
        Still need help? <a href="{{ route('storefront.contact') }}" wire:navigate class="text-green-700 font-semibold hover:underline">Contact us</a>.
    </div>
</div>
