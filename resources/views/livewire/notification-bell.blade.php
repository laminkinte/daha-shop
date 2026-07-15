<div class="relative" x-data @click.outside="$wire.open = false">
    <button wire:click="toggle" class="relative p-2 text-gray-500 hover:text-gray-700 rounded-full hover:bg-gray-100 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute top-0.5 right-0.5 h-4 w-4 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="$wire.open" x-cloak
        class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-white rounded-xl border border-gray-100 shadow-lg z-30 max-h-96 overflow-y-auto">
        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
            <span class="text-sm font-semibold text-gray-800">Notifications</span>
            @if ($unreadCount > 0)
                <button wire:click="markAllRead" class="text-xs text-green-700 hover:text-green-800 font-medium">Mark all read</button>
            @endif
        </div>

        @forelse ($notifications as $notification)
            <a href="{{ $notification->data['url'] ?? '#' }}" wire:navigate
                wire:click="markRead('{{ $notification->id }}')"
                class="block px-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors {{ $notification->read_at ? '' : 'bg-green-50/50' }}">
                <div class="text-sm font-medium text-gray-800">{{ $notification->data['title'] ?? '' }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ $notification->data['message'] ?? '' }}</div>
                <div class="text-[11px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
            </a>
        @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400">No notifications yet.</div>
        @endforelse
    </div>
</div>
