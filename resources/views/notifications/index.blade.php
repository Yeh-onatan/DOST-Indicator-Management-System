<x-layouts.app title="Notifications">
    <div class="w-full px-6 py-6">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:truncate sm:text-3xl sm:tracking-tight">
                    Notifications
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $unreadCount }} unread notification{{ $unreadCount !== 1 ? 's' : '' }}
                </p>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0 gap-2">
                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-md bg-white dark:bg-zinc-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700">
                            Mark All Read
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('notifications.clear-read') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                        Clear Read
                    </button>
                </form>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-zinc-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-700 sm:rounded-lg mb-6">
            <div class="px-4 py-4 sm:px-6">
                <form method="GET" action="{{ route('notifications.index') }}" class="flex flex-wrap gap-4">
                    <!-- Type Filter -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select name="type" id="type" class="rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white py-1.5 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="all" {{ request('type') == 'all' || !request('type') ? 'selected' : '' }}>All Types</option>
                            <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                            <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Success</option>
                            <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>Error</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select name="status" id="status" class="rounded-md border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white py-1.5 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="all" {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>All</option>
                            <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Unread</option>
                            <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="rounded-md bg-[#02aeef] px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-[#0299d5]">
                            Apply Filters
                        </button>
                        <a href="{{ route('notifications.index') }}" class="ml-2 rounded-md bg-gray-100 dark:bg-zinc-700 px-3 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-zinc-600">
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="bg-white dark:bg-zinc-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-700 sm:rounded-lg">
            @if($notifications->count() > 0)
                <ul role="list" class="divide-y divide-gray-100 dark:divide-zinc-700">
                    @foreach($notifications as $notification)
                        @php
                            $data = $notification->data ?? [];
                            $objectiveId = $data['objective_id'] ?? null;
                            $submitterName = $data['submitter_name'] ?? 'Unknown';
                            $approverName = $data['approver_name'] ?? null;
                            $returnLevel = $data['return_level'] ?? null;
                            $rejectionReason = $data['rejection_reason'] ?? null;
                        @endphp
                        <li class="group hover:bg-gray-50 dark:hover:bg-zinc-700/50 @if(!$notification->read_at) bg-blue-50/50 dark:bg-blue-900/10 @endif transition-colors" x-data="{ expanded: false }">
                            <div class="px-4 py-4 sm:px-6">
                                <!-- Header: Clickable to expand -->
                                <div class="flex items-start gap-4 cursor-pointer" @click="expanded = !expanded">
                                    <!-- Icon -->
                                    @php
                                        $iconBg = match($notification->type) {
                                            'success' => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
                                            'warning' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
                                            'error' => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
                                            default => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
                                        };
                                    @endphp

                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $iconBg }}">
                                        @if($notification->type === 'success')
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @elseif($notification->type === 'warning')
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        @elseif($notification->type === 'error')
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </div>

                                    <!-- Title & Message -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $notification->title }}
                                                    <svg class="w-4 h-4 inline-block ml-1 transition-transform x-bind:style="$expanded ? 'transform: rotate(180deg)' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </p>
                                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $notification->message }}
                                                </p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                                    {{ $notification->created_at->format('M d, Y - g:i A') }}
                                                    <span class="mx-1">•</span>
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </p>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center gap-3 self-center">
                                                @if(!$notification->read_at)
                                                    <form method="POST" action="{{ route('notifications.mark-read', $notification->id) }}" class="inline" @click.stop>
                                                        @csrf
                                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                            Mark Read
                                                        </button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this notification?')" @click.stop>
                                                    @csrf
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expanded Details -->
                                <div x-show="expanded" x-collapse class="mt-4 ml-14">
                                    <div class="bg-gray-50 dark:bg-zinc-900 rounded-lg p-4 border border-gray-200 dark:border-zinc-700">
                                        <div class="space-y-3 text-sm">
                                            @if($objectiveId)
                                                <div class="flex items-center justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Indicator ID:</span>
                                                    <span class="font-mono text-gray-900 dark:text-white">{{ $objectiveId }}</span>
                                                </div>
                                            @endif

                                            <div class="flex items-center justify-between">
                                                <span class="text-gray-600 dark:text-gray-400">Submitted by:</span>
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $submitterName }}</span>
                                            </div>

                                            @if($approverName)
                                                <div class="flex items-center justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Approved by:</span>
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ $approverName }}</span>
                                                </div>
                                            @endif

                                            @if($returnLevel && $notification->type === 'warning')
                                                <div class="flex items-center justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Returned from:</span>
                                                    <span class="font-medium text-orange-600 dark:text-orange-400">{{ $returnLevel }}</span>
                                                </div>
                                            @endif

                                            @if($rejectionReason)
                                                <div class="pt-2 border-t border-gray-200 dark:border-zinc-700">
                                                    <span class="text-gray-600 dark:text-gray-400">Reason:</span>
                                                    <p class="mt-1 text-gray-900 dark:text-white italic">"{{ $rejectionReason }}"</p>
                                                </div>
                                            @endif

                                            @if($objectiveId)
                                                <div class="pt-3 border-t border-gray-200 dark:border-zinc-700 mt-3">
                                                    <a href="/dashboard?search={{ $objectiveId }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#02aeef] text-white rounded-lg text-sm font-medium hover:bg-[#0299d5] transition-colors">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7" />
                                                        </svg>
                                                        View Indicator
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <!-- Pagination -->
                <div class="px-4 py-3 sm:px-6 border-t border-gray-200 dark:border-zinc-700">
                    {{ $notifications->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414-2.414a1 1 0 01-.707-.293h-3.172a1 1 0 01-.707.293l-2.414 2.414a1 1 0 01-.707.293H6" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No notifications</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if(request('type') || request('status'))
                            Try adjusting your filters to see more results.
                        @else
                            You're all caught up! No notifications to display.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
