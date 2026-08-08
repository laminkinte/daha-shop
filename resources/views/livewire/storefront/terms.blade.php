<div>
    <x-storefront.page-hero
        badge="Last updated {{ now()->format('F Y') }}"
        title="Terms of Service"
        subtitle="The ground rules for buying and selling on Daha Shop."
    />

    @php
        $sections = [
            'using' => ['title' => 'Using Daha Shop', 'body' => 'By placing an order on Daha Shop, you agree to pay the seller the full order amount in cash at the point of delivery, unless the order is fulfilled through another payment method explicitly offered at checkout.'],
            'orders' => ['title' => 'Orders and Delivery', 'body' => 'Orders are confirmed by SMS after checkout. Delivery timeframes vary by seller and location. Repeated failed delivery attempts (for example, being unavailable to accept cash payment) may result in the order being cancelled.'],
            'sellers' => ['title' => 'Sellers', 'body' => 'Sellers must complete identity verification before listing products and are responsible for the accuracy of their product listings, pricing, and stock availability.'],
            'payments' => ['title' => 'Payments', 'body' => 'Delivery fees, where applicable, are collected digitally through our supported payment gateways. Product costs are collected in cash by the delivery agent or seller at the point of delivery.'],
            'changes' => ['title' => 'Changes', 'body' => 'We may update these terms from time to time. Continued use of Daha Shop after changes are posted constitutes acceptance of the updated terms.'],
        ];
    @endphp

    <div
        x-data="{ active: '{{ array_key_first($sections) }}' }"
        x-init="$nextTick(() => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => { if (entry.isIntersecting) active = entry.target.id })
            }, { rootMargin: '-15% 0px -70% 0px' });
            $refs.sections.querySelectorAll('section[id]').forEach(s => observer.observe(s));
        })"
        class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-14 sm:-mt-16 relative z-10 pb-16"
    >
        <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6 sm:gap-8 items-start">
            <nav class="hidden lg:block sticky top-24 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 px-2 mb-2">On this page</div>
                <ul class="space-y-0.5">
                    @foreach ($sections as $id => $section)
                        <li>
                            <a href="#{{ $id }}"
                                class="block px-2 py-1.5 rounded-lg text-sm transition-colors"
                                :class="active === '{{ $id }}' ? 'bg-green-50 text-green-700 font-semibold' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'">
                                {{ $loop->iteration }}. {{ $section['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="lg:hidden -mx-4 px-4 mb-2 overflow-x-auto scrollbar-none">
                <div class="flex gap-2 w-max">
                    @foreach ($sections as $id => $section)
                        <a href="#{{ $id }}"
                            class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full transition-colors"
                            :class="active === '{{ $id }}' ? 'bg-green-600 text-white' : 'bg-white border border-gray-200 text-gray-600'">
                            {{ $loop->iteration }}. {{ $section['title'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div x-ref="sections" class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-100">
                @foreach ($sections as $id => $section)
                    <section id="{{ $id }}" class="p-6 sm:p-8 scroll-mt-24">
                        <div class="flex items-start gap-4">
                            <span class="shrink-0 h-8 w-8 rounded-full bg-gradient-to-br from-green-600 to-emerald-500 text-white text-sm font-bold flex items-center justify-center">{{ $loop->iteration }}</span>
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 mb-2">{{ $section['title'] }}</h2>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ $section['body'] }}</p>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</div>
