<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col lg:flex-row">
            <!-- Branding panel -->
            <div class="bg-green-700 text-white lg:w-2/5 xl:w-1/3 flex flex-col justify-between px-6 sm:px-10 py-8 lg:py-12">
                <a href="{{ route('storefront.home') }}" wire:navigate class="text-2xl font-bold tracking-tight">
                    @if ($logoUrl = app_logo_url())
                        <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" class="h-10">
                    @else
                        Daha <span class="text-green-200">Shop</span>
                    @endif
                </a>

                <div class="hidden lg:block my-12">
                    <h1 class="text-3xl xl:text-4xl font-bold leading-tight">Shop smarter.<br>Pay when it arrives.</h1>
                    <p class="mt-4 text-green-100 max-w-sm">
                        No card required &mdash; confirm your order by SMS and pay cash on delivery,
                        from verified sellers, tracked every step of the way.
                    </p>

                    <ul class="mt-8 space-y-3 text-sm text-green-100">
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Cash on delivery, nationwide
                        </li>
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Verified, admin-approved sellers
                        </li>
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Real-time delivery tracking
                        </li>
                    </ul>
                </div>

                <p class="hidden lg:block text-xs text-green-200">&copy; {{ date('Y') }} Daha Shop</p>
            </div>

            <!-- Form panel -->
            <div class="flex-1 flex items-center justify-center px-6 py-10 sm:py-16 bg-gray-50">
                <div class="w-full sm:max-w-md bg-white px-6 py-8 sm:px-8 sm:py-10 shadow-sm rounded-xl border border-gray-100">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
