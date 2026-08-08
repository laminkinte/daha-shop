@props(['badge' => null, 'title', 'subtitle' => null])

<div class="relative overflow-hidden bg-gradient-to-br from-green-800 via-green-700 to-emerald-600 text-white">
    <div class="absolute -top-24 -right-16 h-72 w-72 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-28 -left-20 h-72 w-72 rounded-full bg-emerald-400/20 blur-3xl pointer-events-none"></div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 sm:pt-20 pb-24 sm:pb-28"
        x-data="{ shown: false }" x-init="requestAnimationFrame(() => shown = true)">
        <div class="transition-all duration-700 ease-out" :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            @if ($badge)
                <span class="inline-flex items-center gap-1.5 bg-white/15 ring-1 ring-white/20 text-xs font-semibold px-3 py-1 rounded-full mb-4 backdrop-blur-sm">
                    {{ $badge }}
                </span>
            @endif

            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">{{ $title }}</h1>

            @if ($subtitle)
                <p class="mt-3 text-green-50/90 text-sm sm:text-base max-w-2xl leading-relaxed">{{ $subtitle }}</p>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>
