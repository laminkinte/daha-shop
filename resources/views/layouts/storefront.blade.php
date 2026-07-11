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
<body class="font-sans antialiased bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    <header class="bg-green-700 text-white sticky top-0 z-40 shadow" x-data="{ mobileSearchOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-2 sm:gap-4">
                <a href="{{ route('storefront.home') }}" wire:navigate class="text-xl font-bold tracking-tight shrink-0">
                    Daha <span class="text-green-200">Shop</span>
                </a>

                <form action="{{ route('storefront.home') }}" method="GET" class="hidden md:flex flex-1 max-w-xl">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search products..."
                        class="w-full rounded-l-md border-0 px-4 py-2 text-gray-900 focus:ring-2 focus:ring-green-400"
                    >
                    <button type="submit" class="rounded-r-md bg-green-900 px-4 py-2 hover:bg-green-950">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                    </button>
                </form>

                <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                    <button @click="mobileSearchOpen = !mobileSearchOpen" class="md:hidden hover:text-green-200" title="Search">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    </button>

                    <a href="{{ route('storefront.wishlist') }}" wire:navigate class="hover:text-green-200" title="Wishlist">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                    </a>

                    <livewire:storefront.cart-icon />

                    @auth
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-1 hover:text-green-200">
                                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute right-0 mt-2 w-48 bg-white text-gray-800 rounded-md shadow-lg py-1 text-sm">
                                @if (auth()->user()->isVendor())
                                    <a href="{{ route('vendor.dashboard') }}" class="block px-4 py-2 hover:bg-gray-100">Vendor Dashboard</a>
                                @elseif(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 hover:bg-gray-100">Admin Dashboard</a>
                                @elseif(auth()->user()->isAgent())
                                    <a href="{{ route('agent.deliveries') }}" class="block px-4 py-2 hover:bg-gray-100">Agent App</a>
                                @endif
                                <a href="{{ route('storefront.orders') }}" wire:navigate class="block px-4 py-2 hover:bg-gray-100">My Orders</a>
                                <a href="{{ route('profile') }}" wire:navigate class="block px-4 py-2 hover:bg-gray-100">Profile</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">Log Out</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="hover:text-green-200">Login</a>
                        <a href="{{ route('register') }}" wire:navigate class="bg-white text-green-700 px-3 py-1.5 rounded-md font-medium hover:bg-green-50">Sign Up</a>
                    @endauth
                </div>
            </div>

            <div x-show="mobileSearchOpen" x-cloak x-transition class="md:hidden pb-3">
                <form action="{{ route('storefront.home') }}" method="GET" class="flex">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search products..."
                        autofocus
                        class="w-full rounded-l-md border-0 px-4 py-2 text-gray-900 focus:ring-2 focus:ring-green-400"
                    >
                    <button type="submit" class="rounded-r-md bg-green-900 px-4 py-2 hover:bg-green-950">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1 pb-16 md:pb-0">
        {{ $slot }}
    </main>

    <footer class="bg-gray-900 text-gray-400 text-sm mt-12 mb-16 md:mb-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <span>&copy; {{ date('Y') }} Daha Shop — Pay with cash when your order arrives.</span>
            @guest
                <a href="{{ route('register') }}?as=seller" wire:navigate class="text-green-400 hover:text-green-300 font-medium">
                    Become a Seller &rarr;
                </a>
            @endguest
        </div>
    </footer>

    <!-- Mobile bottom icon bar -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-gray-200 flex items-stretch" style="padding-bottom: env(safe-area-inset-bottom)">
        <a href="{{ route('storefront.home') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 {{ request()->routeIs('storefront.home') ? 'text-green-700' : 'text-gray-500' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
            <span class="text-[10px] font-medium">Home</span>
        </a>

        <a href="{{ route('storefront.wishlist') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 {{ request()->routeIs('storefront.wishlist') ? 'text-green-700' : 'text-gray-500' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
            <span class="text-[10px] font-medium">Wishlist</span>
        </a>

        <livewire:storefront.cart-icon variant="bottom" />

        @auth
            <a href="{{ route('storefront.orders') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 {{ request()->routeIs('storefront.orders*') ? 'text-green-700' : 'text-gray-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                <span class="text-[10px] font-medium">Orders</span>
            </a>

            <a href="{{ route('profile') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 {{ request()->routeIs('profile') ? 'text-green-700' : 'text-gray-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <span class="text-[10px] font-medium">Account</span>
            </a>
        @else
            <a href="{{ route('login') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 {{ request()->routeIs('login') ? 'text-green-700' : 'text-gray-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H3" /></svg>
                <span class="text-[10px] font-medium">Login</span>
            </a>

            <a href="{{ route('register') }}" wire:navigate class="flex-1 flex flex-col items-center justify-center py-2 gap-0.5 {{ request()->routeIs('register') ? 'text-green-700' : 'text-gray-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
                <span class="text-[10px] font-medium">Sign Up</span>
            </a>
        @endauth
    </nav>

    <livewire:phone-verification-prompt />

    @livewireScripts
</body>
</html>
