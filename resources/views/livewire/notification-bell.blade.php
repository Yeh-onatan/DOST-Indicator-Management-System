<div class="relative" x-data="{ open: @entangle('showDropdown', false) }">
    <!-- Bell Icon Button -->
    <button wire:click="toggleDropdown" class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4h.006C.501 6.388 2.524 4.5 5 4.5c1.389 0 2.5.672 2.5 1.5a2.5 2.5 0 00-5 0c0-.828 1.111-1.5 2.5-1.5 2.476 0 4.5-2.112 4.5-5.006a2.5 2.5 0 10-4.994.006C2.524 4.5 4.612 6.388 5.006 11zM11 17h6" />
        </svg>

        <!-- Unread Badge -->
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown -->
    @if($showDropdown)
        <div class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             wire:click.away="closeDropdown">

            <!-- Header -->
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between bg-gray-50 rounded-t-lg">
                <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                @if($unreadCount > 0)
                    <a href="{{ route('notifications.index') }}" class="text-xs text-blue-600 hover:text-blue-800">View all</a>
                @endif
            </div>

            <!-- Notifications List -->
            <div class="max-h-96 overflow-y-auto">
                @php
                    $notifications = \App\Services\NotificationService::make()->getNotifications(auth()->user(), 5);
                @endphp

                @if($notifications->count() > 0)
                    @foreach($notifications as $notif)
                        @if($notif->action_url)
                            <a href="{{ route('notifications.mark-read', $notif->id) }}" class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors @if($notif->read_at) bg-gray-50/50 @endif">
                        @else
                            <a href="{{ route('notifications.index') }}" class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors @if($notif->read_at) bg-gray-50/50 @endif">
                        @endif

                            <!-- Icon based on type -->
                            @php
                                $iconBg = match($notif->type) {
                                    'success' => 'bg-green-100 text-green-600',
                                    'warning' => 'bg-orange-100 text-orange-600',
                                    'error' => 'bg-red-100 text-red-600',
                                    default => 'bg-blue-100 text-blue-600',
                                };
                            @endphp

                            <div class="flex items-start gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $iconBg }}">
                                    @if($notif->type === 'success')
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @elseif($notif->type === 'warning')
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    @elseif($notif->type === 'error')
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $notif->title }}</p>
                                    <p class="text-xs text-gray-600 truncate">{{ $notif->message }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                                </div>
                                @if(!$notif->read_at)
                                    <span class="h-2 w-2 rounded-full bg-[#02aeef] mt-2"></span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                @else
                    <div class="px-4 py-8 text-center text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414-2.414a1 1 0 01-.707-.293h-3.172a1 1 0 01-.707.293l-2.414 2.414a1 1 0 01-.707.293H6" />
                        </svg>
                        <p class="text-sm">No notifications yet</p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
                <a href="{{ route('notifications.index') }}" class="block text-center text-sm text-blue-600 hover:text-blue-800">
                    View all notifications →
                </a>
            </div>
        </div>
    @endif
</div>
