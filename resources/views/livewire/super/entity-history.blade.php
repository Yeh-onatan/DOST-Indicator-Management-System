<div>
    <!-- Entity History Header -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Audit History: {{ $entityTypeName }}
                @if($entityName)
                    <span class="text-gray-500 dark:text-gray-400">({{ Str::limit($entityName, 50) }})</span>
                @endif
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ID: {{ $entityId }}
            </p>
        </div>
        <button wire:click="$dispatch('close-modal')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
            <x-icon name="x" class="w-6 h-6" />
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <x-input-label for="search" :value="__('Search')" />
                <x-text-input
                    id="search"
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    :placeholder="__('Search descriptions...')"
                />
            </div>

            <!-- Action Filter -->
            <div>
                <x-input-label for="actionFilter" :value="__('Action Type')" />
                <select
                    id="actionFilter"
                    wire:model.live="actionFilter"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All Actions</option>
                    @foreach($availableActions as $action)
                        <option value="{{ $action }}">{{ \App\Models\AuditLog::firstWhere('action', $action)?->action_description ?? $action }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Per Page -->
            <div>
                <x-input-label for="perPage" :value="__('Per Page')" />
                <select
                    id="perPage"
                    wire:model.live="perPage"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <!-- Reset Filters Button -->
        @if($search || $actionFilter)
            <div class="mt-3">
                <button wire:click="resetFilters" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                    {{ __('Reset Filters') }}
                </button>
            </div>
        @endif
    </div>

    <!-- Audit Log Timeline -->
    @if($logs->count() > 0)
        <div class="space-y-4">
            @foreach($logs as $log)
                <div class="relative pl-8 pb-6 border-l-2 border-gray-200 dark:border-gray-700 last:pb-0">
                    <!-- Timeline Dot -->
                    <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full
                        @if($log->action_color === 'success') bg-green-500
                        @elseif($log->action_color === 'danger') bg-red-500
                        @elseif($log->action_color === 'warning') bg-yellow-500
                        @elseif($log->action_color === 'primary') bg-blue-500
                        @elseif($log->action_color === 'info') bg-purple-500
                        @else bg-gray-500
                        @endif
                    ">

                    </div>

                    <!-- Log Entry Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <!-- Action Badge -->
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($log->action_color === 'success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($log->action_color === 'danger') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @elseif($log->action_color === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @elseif($log->action_color === 'primary') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($log->action_color === 'info') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif
                                ">
                                    {{ $log->action_description }}
                                </span>

                                <!-- Timestamp -->
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->created_at->format('M j, Y g:i A') }}
                                </span>
                            </div>

                            <!-- Actor -->
                            @if($log->actor)
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span>{{ $log->actor->name }}</span>
                                    @if($log->actor->email)
                                        <span class="text-gray-400">({{ $log->actor->email }})</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">System</span>
                            @endif
                        </div>

                        <!-- Description -->
                        @if($log->description)
                            <p class="text-gray-700 dark:text-gray-300 mb-3">{{ $log->description }}</p>
                        @endif

                        <!-- Changes / Details -->
                        @if($log->changes)
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-md p-3 text-sm">
                                @if(isset($log->changes['diff']))
                                    <!-- Field-by-field diff -->
                                    <div class="space-y-2">
                                        @foreach($log->changes['diff'] as $field => $change)
                                            <div class="flex items-start gap-2">
                                                <span class="font-medium text-gray-700 dark:text-gray-300 min-w-[100px]">{{ $field }}:</span>
                                                <div class="flex-1">
                                                    @if($change['before'] !== null)
                                                        <span class="text-red-600 dark:text-red-400 line-through mr-2">{{ $change['before'] }}</span>
                                                    @endif
                                                    @if($change['after'] !== null)
                                                        <span class="text-green-600 dark:text-green-400">{{ $change['after'] }}</span>
                                                    @endif
                                                    @if($change['before'] === null && $change['after'] === null)
                                                        <span class="text-gray-500 italic">null</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif(isset($log->changes['deleted']))
                                    <!-- Deleted data snapshot -->
                                    <div class="text-red-600 dark:text-red-400">
                                        <span class="font-medium">Deleted Data:</span>
                                        <pre class="mt-2 text-xs overflow-x-auto">{{ json_encode($log->changes['deleted'], JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                @elseif(isset($log->changes['created']))
                                    <!-- Created data snapshot -->
                                    <div class="text-green-600 dark:text-green-400">
                                        <span class="font-medium">Created Data:</span>
                                        <pre class="mt-2 text-xs overflow-x-auto">{{ json_encode($log->changes['created'], JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                @elseif(isset($log->changes['from']) && isset($log->changes['to']))
                                    <!-- Workflow transition -->
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 rounded bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">
                                            {{ $log->changes['from'] }}
                                        </span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                        <span class="px-2 py-1 rounded bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                            {{ $log->changes['to'] }}
                                        </span>
                                    </div>
                                @else
                                    <!-- Raw JSON -->
                                    <pre class="text-xs overflow-x-auto">{{ json_encode($log->changes, JSON_PRETTY_PRINT) }}</pre>
                                @endif
                            </div>
                        @endif

                        <!-- Related Entity -->
                        @if($log->related_entity_type)
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>Related: {{ $log->related_entity_type }} #{{ $log->related_entity_id }}</span>
                            </div>
                        @endif

                        <!-- Metadata -->
                        <div class="mt-3 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                            @if($log->ip_address)
                                <span>IP: {{ $log->ip_address }}</span>
                            @endif
                            @if($log->batch_id)
                                <span>Batch: {{ substr($log->batch_id, 0, 8) }}...</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $logs->appends(['search' => $search, 'actionFilter' => $actionFilter, 'perPage' => $perPage])->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No History Found</h3>
            <p class="text-gray-500 dark:text-gray-400">No audit history exists for this {{ $entityTypeName }}.</p>
        </div>
    @endif
</div>
