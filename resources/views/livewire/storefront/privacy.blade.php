<div>
    <x-storefront.page-hero
        badge="Last updated {{ now()->format('F Y') }}"
        title="Privacy Policy"
        subtitle="What we collect, why we collect it, and who it's shared with."
    />

    @php
        $sections = [
            'collect' => ['title' => 'Information We Collect', 'body' => 'To process your orders, we collect your name, phone number, email (if provided), delivery address, and order history. Sellers additionally submit an ID document and a live selfie, used only for identity verification.'],
            'use' => ['title' => 'How We Use It', 'body' => "Your information is used to confirm and fulfil orders, share your delivery address and live location with the assigned delivery agent for that order, send order-status SMS/email updates, and verify seller identities before approval."],
            'location' => ['title' => 'Location Data', 'body' => "When an order is out for delivery, the delivery agent's (or self-delivering seller's) live location is shared with you so you can track your order. This location is only visible to the customer on that specific order, and only while it's actively out for delivery."],
            'sharing' => ['title' => 'Sharing', 'body' => "We don't sell your personal information. It's shared only with the seller and delivery agent fulfilling your order, and with payment providers when processing a digital payment (such as a delivery fee)."],
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
                                {{ $section['title'] }}
                            </a>
                        </li>
                    @endforeach
                    <li>
                        <a href="#contact"
                            class="block px-2 py-1.5 rounded-lg text-sm transition-colors"
                            :class="active === 'contact' ? 'bg-green-50 text-green-700 font-semibold' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'">
                            Contact
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="lg:hidden -mx-4 px-4 mb-2 overflow-x-auto scrollbar-none">
                <div class="flex gap-2 w-max">
                    @foreach ($sections as $id => $section)
                        <a href="#{{ $id }}"
                            class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full transition-colors"
                            :class="active === '{{ $id }}' ? 'bg-green-600 text-white' : 'bg-white border border-gray-200 text-gray-600'">
                            {{ $section['title'] }}
                        </a>
                    @endforeach
                    <a href="#contact"
                        class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full transition-colors"
                        :class="active === 'contact' ? 'bg-green-600 text-white' : 'bg-white border border-gray-200 text-gray-600'">
                        Contact
                    </a>
                </div>
            </div>

            <div x-ref="sections" class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-100">
                @foreach ($sections as $id => $section)
                    <section id="{{ $id }}" class="p-6 sm:p-8 scroll-mt-24">
                        <div class="flex items-start gap-4">
                            <span class="shrink-0 h-8 w-8 rounded-full bg-gradient-to-br from-green-600 to-emerald-500 text-white flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.248-8.25-3.286z" /></svg>
                            </span>
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 mb-2">{{ $section['title'] }}</h2>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ $section['body'] }}</p>
                            </div>
                        </div>
                    </section>
                @endforeach

                <section id="contact" class="p-6 sm:p-8 scroll-mt-24">
                    <div class="flex items-start gap-4">
                        <span class="shrink-0 h-8 w-8 rounded-full bg-gradient-to-br from-green-600 to-emerald-500 text-white flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 mb-2">Contact</h2>
                            <p class="text-sm text-gray-600 leading-relaxed">Questions about your data? Reach out via our <a href="{{ route('storefront.contact') }}" wire:navigate class="text-green-700 font-semibold hover:underline">Contact page</a>.</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
