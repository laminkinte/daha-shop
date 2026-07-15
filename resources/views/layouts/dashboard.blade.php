<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-50 text-gray-900" x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed z-30 inset-y-0 left-0 w-64 bg-slate-900 text-slate-200 transform transition-transform lg:translate-x-0 lg:static lg:inset-auto flex flex-col">
            <div class="h-16 flex items-center px-6 text-lg font-bold border-b border-white/10 shrink-0">
                Daha <span class="text-green-400 ml-1">Shop</span>
            </div>

            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                @auth
                    @if (auth()->user()->isVendor())
                        <p class="px-3 pt-1 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Seller</p>
                        <x-dashboard-nav-link :href="route('vendor.dashboard')" :active="request()->routeIs('vendor.dashboard')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25v-2.25zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg></x-slot:icon>
                            Overview
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('vendor.products')" :active="request()->routeIs('vendor.products')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg></x-slot:icon>
                            Products
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('vendor.orders')" :active="request()->routeIs('vendor.orders')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></x-slot:icon>
                            Orders
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('vendor.payouts')" :active="request()->routeIs('vendor.payouts')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.725-.659 1.622-.659 2.003-.659.545 0 1.09.181 1.505.545" /></svg></x-slot:icon>
                            Payouts
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('vendor.qr-code')" :active="request()->routeIs('vendor.qr-code')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5A.75.75 0 014.5 3.75h4.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-4.5a.75.75 0 01-.75-.75v-4.5zM3.75 14.25a.75.75 0 01.75-.75h4.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-4.5a.75.75 0 01-.75-.75v-4.5zM13.5 4.5a.75.75 0 01.75-.75h4.5a.75.75 0 01.75.75v4.5a.75.75 0 01-.75.75h-4.5a.75.75 0 01-.75-.75v-4.5zM13.5 13.5h2.25v2.25H13.5V13.5zM18 13.5h.75v.75H18v-.75zM13.5 18h.75v.75h-.75V18zM18 18h.75v.75H18V18z" /></svg></x-slot:icon>
                            Shop QR Code
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('vendor.identity')" :active="request()->routeIs('vendor.identity')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a.75.75 0 00.75-.75V5.25a.75.75 0 00-.75-.75h-15a.75.75 0 00-.75.75v13.5c0 .414.336.75.75.75zM9 9.75a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0zM6 15a3 3 0 013-3h.75a3 3 0 013 3v.75H6V15z" /></svg></x-slot:icon>
                            Identity Verification
                            @if (auth()->user()->vendor?->needsIdDocumentRetake() || auth()->user()->vendor?->needsSelfieRetake())
                                <span class="ml-1 inline-block h-2 w-2 rounded-full bg-red-500"></span>
                            @endif
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('vendor.subscription')" :active="request()->routeIs('vendor.subscription')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 4.5h16.5a1.5 1.5 0 011.5 1.5v12a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V6a1.5 1.5 0 011.5-1.5z" /></svg></x-slot:icon>
                            Subscription
                        </x-dashboard-nav-link>
                    @elseif(auth()->user()->isAdmin())
                        <p class="px-3 pt-1 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Overview</p>
                        <x-dashboard-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg></x-slot:icon>
                            Overview
                        </x-dashboard-nav-link>
                        <p class="px-3 pt-4 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Catalog</p>
                        <x-dashboard-nav-link :href="route('admin.vendors')" :active="request()->routeIs('admin.vendors')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6 21v-3.375c0-.621.504-1.125 1.125-1.125h1.5c.621 0 1.125.504 1.125 1.125V21" /></svg></x-slot:icon>
                            Vendors
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg></x-slot:icon>
                            Products
                        </x-dashboard-nav-link>
                        <p class="px-3 pt-4 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Operations</p>
                        <x-dashboard-nav-link :href="route('admin.orders')" :active="request()->routeIs('admin.orders')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></x-slot:icon>
                            Orders
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.dispatch')" :active="request()->routeIs('admin.dispatch')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25h5.401c.585 0 1.09.408 1.212.98l1.244 5.85c.084.399-.012.812-.264 1.129M14.25 7.5v8.25m-6-8.25H3.375c-.621 0-1.125.504-1.125 1.125v9.75c0 .621.504 1.125 1.125 1.125h1.5" /></svg></x-slot:icon>
                            Dispatch
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.reconciliation')" :active="request()->routeIs('admin.reconciliation')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.725-.659 1.622-.659 2.003-.659.545 0 1.09.181 1.505.545" /></svg></x-slot:icon>
                            Reconciliation
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.agents')" :active="request()->routeIs('admin.agents')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></x-slot:icon>
                            Delivery Agents
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.delivery-zones')" :active="request()->routeIs('admin.delivery-zones')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg></x-slot:icon>
                            Delivery Zones
                        </x-dashboard-nav-link>
                        <p class="px-3 pt-4 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Trust &amp; Safety</p>
                        <x-dashboard-nav-link :href="route('admin.blacklist')" :active="request()->routeIs('admin.blacklist')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg></x-slot:icon>
                            Blacklist
                        </x-dashboard-nav-link>
                    @elseif(auth()->user()->isAgent())
                        <p class="px-3 pt-1 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Agent</p>
                        <x-dashboard-nav-link :href="route('agent.deliveries')" :active="request()->routeIs('agent.deliveries')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25h5.401c.585 0 1.09.408 1.212.98l1.244 5.85c.084.399-.012.812-.264 1.129M14.25 7.5v8.25m-6-8.25H3.375c-.621 0-1.125.504-1.125 1.125v9.75c0 .621.504 1.125 1.125 1.125h1.5" /></svg></x-slot:icon>
                            Assigned Deliveries
                        </x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('agent.remittance')" :active="request()->routeIs('agent.remittance')">
                            <x-slot:icon><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.725-.659 1.622-.659 2.003-.659.545 0 1.09.181 1.505.545" /></svg></x-slot:icon>
                            Cash Remittance
                        </x-dashboard-nav-link>
                    @endif
                @endauth
            </nav>

            <div class="shrink-0 border-t border-white/10 p-4">
                @auth
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-9 w-9 rounded-full bg-green-600 flex items-center justify-center text-sm font-semibold text-white shrink-0">
                            {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400 capitalize">{{ auth()->user()->role->value }}</p>
                        </div>
                    </div>
                @endauth
                <a href="{{ route('storefront.home') }}" wire:navigate class="flex items-center gap-2 text-xs text-slate-400 hover:text-white mb-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    Back to storefront
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 text-xs text-slate-400 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                        Log Out
                    </button>
                </form>
            </div>
        </aside>

        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-black/40 lg:hidden"></div>

        <div class="flex-1 min-w-0">
            <!-- Topbar -->
            <div class="h-16 bg-white/80 backdrop-blur border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-10">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-800">{{ $header ?? '' }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 hidden sm:inline">{{ auth()->user()->name ?? '' }}</span>
                    <div class="h-8 w-8 rounded-full bg-slate-800 flex items-center justify-center text-xs font-semibold text-white">
                        {{ Str::of(auth()->user()->name ?? '?')->substr(0, 1)->upper() }}
                    </div>
                </div>
            </div>

            <main class="p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    <livewire:phone-verification-prompt />

    @livewireScripts
</body>
</html>
