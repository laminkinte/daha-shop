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
<body class="font-sans antialiased bg-gray-100 text-gray-900" x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed z-30 inset-y-0 left-0 w-64 bg-gray-900 text-gray-200 transform transition-transform lg:translate-x-0 lg:static lg:inset-auto">
            <div class="h-16 flex items-center px-6 text-lg font-bold border-b border-gray-800">
                MarketHub <span class="text-green-400 ml-1">NG</span>
            </div>
            <nav class="p-4 space-y-1 text-sm">
                @auth
                    @if (auth()->user()->isVendor())
                        <x-dashboard-nav-link :href="route('vendor.dashboard')" :active="request()->routeIs('vendor.dashboard')">Overview</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('vendor.products')" :active="request()->routeIs('vendor.products')">Products</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('vendor.orders')" :active="request()->routeIs('vendor.orders')">Orders</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('vendor.payouts')" :active="request()->routeIs('vendor.payouts')">Payouts</x-dashboard-nav-link>
                    @elseif(auth()->user()->isAdmin())
                        <x-dashboard-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">Overview</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.vendors')" :active="request()->routeIs('admin.vendors')">Vendors</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.orders')" :active="request()->routeIs('admin.orders')">Orders</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.dispatch')" :active="request()->routeIs('admin.dispatch')">Dispatch</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.reconciliation')" :active="request()->routeIs('admin.reconciliation')">Reconciliation</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.agents')" :active="request()->routeIs('admin.agents')">Delivery Agents</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.delivery-zones')" :active="request()->routeIs('admin.delivery-zones')">Delivery Zones</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('admin.blacklist')" :active="request()->routeIs('admin.blacklist')">Blacklist</x-dashboard-nav-link>
                    @elseif(auth()->user()->isAgent())
                        <x-dashboard-nav-link :href="route('agent.deliveries')" :active="request()->routeIs('agent.deliveries')">Assigned Deliveries</x-dashboard-nav-link>
                        <x-dashboard-nav-link :href="route('agent.remittance')" :active="request()->routeIs('agent.remittance')">Cash Remittance</x-dashboard-nav-link>
                    @endif
                @endauth
            </nav>
            <div class="absolute bottom-0 inset-x-0 p-4 border-t border-gray-800">
                <a href="{{ route('storefront.home') }}" wire:navigate class="block text-xs text-gray-400 hover:text-white mb-3">&larr; Back to storefront</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-gray-400 hover:text-white">Log Out</button>
                </form>
            </div>
        </aside>

        <div class="flex-1 lg:ml-0">
            <!-- Topbar -->
            <div class="h-16 bg-white shadow flex items-center justify-between px-4 sm:px-6">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <h1 class="text-lg font-semibold text-gray-800">{{ $header ?? '' }}</h1>
                <span class="text-sm text-gray-500">{{ auth()->user()->name ?? '' }}</span>
            </div>

            <main class="p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
