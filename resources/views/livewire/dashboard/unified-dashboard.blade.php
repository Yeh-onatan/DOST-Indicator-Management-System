<div class="min-h-screen bg-gray-50/50">
    <div class="w-full max-w-full px-4 sm:px-6 lg:px-8 space-y-6 transition-all duration-300 {{ auth()->user()->isSuperAdmin() && count($selectedIndicators) > 0 ? 'pb-32' : '' }}">

        @php
            $user = auth()->user();
        @endphp

        {{-- 1. WELCOME BANNER --}}
        <div class="rounded-2xl relative shadow-lg group text-[#003B5C]"
             style="background: linear-gradient(135deg, #003B5C 0%, #004A72 30%, #007FB1 70%, #02aeef 100%);">

            {{-- Decorative Pattern Overlay --}}
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white via-transparent to-transparent overflow-hidden rounded-2xl"></div>

            <div class="relative p-6 md:p-8 flex flex-col md:flex-row items-start justify-between gap-6">
                
                {{-- Left Side: Titles & Location --}}
                <div class="space-y-4 flex-1">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-black tracking-tight text-white drop-shadow-md">
                            DOST Indicators Management System
                        </h1>
                        <h2 class="text-xl font-medium text-blue-100 mt-1">
                            @if($user->isPSTO()) Your Indicators Dashboard
                            @elseif($user->isRO()) Regional Management Dashboard
                            @elseif($user->canActAsHeadOfOffice()) Approvals & Oversight Dashboard
                            @else Monitoring & Evaluation Dashboard
                            @endif
                        </h2>
                    </div>

                    {{-- UNIFIED LOCATION BADGE --}}
                    @if($user->office)
                        <div class="inline-flex items-center bg-white/10 backdrop-blur-md rounded-xl p-2 pr-5 border border-white/10 transition hover:bg-white/15 hover:border-white/30">
                            <div class="h-10 w-10 bg-white/20 rounded-lg flex items-center justify-center mr-3 shrink-0 shadow-inner">
                                <x-icon name="location" class="w-5 h-5 text-white" />
                            </div>
                            <div class="flex flex-col justify-center">
                                @if($user->office->region)
                                    <span class="text-[10px] font-bold text-blue-200 uppercase tracking-widest leading-none mb-1">
                                        {{ $user->office->region->name }}
                                    </span>
                                @endif
                                <span class="text-sm font-bold text-white leading-none">
                                    {{ $user->office->name }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right Side: Notifications & Status --}}
                <div class="flex items-center gap-3">
                    {{-- Status Badge --}}
                    @if(!$user->isSuperAdmin())
                        @if($mandatoryProgress['total'] > 0 && ($mandatoryProgress['total'] - $mandatoryProgress['completed']) > 0)
                            <div class="hidden md:flex items-center gap-2 px-3 py-2 bg-amber-500/20 border border-amber-400/30 rounded-lg">
                                <svg class="w-4 h-4 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span class="text-xs text-amber-200">{{ $mandatoryProgress['total'] - $mandatoryProgress['completed'] }} pending</span>
                            </div>
                        @endif
                    @endif

                    @if($pendingApprovalsCount > 0)
                        <div class="hidden md:flex items-center gap-2 px-3 py-2 bg-[#02aeef]/20 border border-[#02aeef]/30 rounded-lg">
                            <svg class="w-4 h-4 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-xs text-blue-200">{{ $pendingApprovalsCount }} to review</span>
                        </div>
                    @endif

                    {{-- Notification Bell with Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="if($wire.unreadCount > 0) { $wire.markAllNotificationsRead(); } open = !open" class="relative p-2.5 bg-white/10 hover:bg-white/20 rounded-xl border border-white/10 transition">
                            <x-icon name="bell" class="w-5 h-5 text-white" />
                            @if($this->unreadCount > 0)
                                <span class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white">
                                    {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
                                </span>
                            @endif
                        </button>

                        {{-- Dropdown with Recent Notifications --}}
                        <div x-show="open" x-cloak
                             @click.outside="open = false"
                             @close-notification-dropdown.window="open = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-2xl border border-gray-200 z-[9999]">

                            <div class="p-3 border-b border-gray-100">
                                <h3 class="font-semibold text-gray-900 text-sm">Notifications</h3>
                            </div>

                            <div class="max-h-80 overflow-y-auto">
                                @if(count($this->recentNotifications) > 0)
                                    @foreach($this->recentNotifications as $notif)
                                        <a href="{{ route('notifications.index') }}"
                                             class="block px-3 py-2.5 border-b border-gray-50 hover:bg-gray-50 transition-colors @if($notif['read_at']) bg-gray-50/30 @endif">
                                            @php
                                                $icon = match($notif['type']) {
                                                    'success' => '✅',
                                                    'warning' => '⚠️',
                                                    'error' => '❌',
                                                    'info' => 'ℹ️',
                                                    default => '📢',
                                                };
                                                $createdAt = isset($notif['created_at']) ? \Carbon\Carbon::parse($notif['created_at'])->diffForHumans() : '';
                                            @endphp

                                            <div class="flex items-start gap-2.5">
                                                <div class="text-sm flex-shrink-0 mt-0.5">{{ $icon }}</div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $notif['title'] }}</p>
                                                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $notif['message'] }}</p>
                                                    <p class="text-xs text-gray-400 mt-1">{{ $createdAt }}</p>
                                                </div>
                                                @if(!$notif['read_at'])
                                                    <span class="h-2 w-2 rounded-full bg-[#02aeef] mt-1.5 flex-shrink-0"></span>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                @else
                                    <div class="px-3 py-6 text-center text-gray-500">
                                        <p class="text-sm">No notifications</p>
                                    </div>
                                @endif
                            </div>

                            @if($this->unreadCount > 0)
                            <div class="p-2 border-t border-gray-100 bg-gray-50 rounded-b-xl flex gap-2">
                                <button wire:click="markAllNotificationsRead" class="flex-1 text-xs text-blue-600 hover:text-blue-800 font-medium py-1.5 px-3 rounded-lg hover:bg-blue-50 transition-colors">
                                    Mark all read
                                </button>
                                <a href="{{ route('notifications.index') }}" class="text-xs text-gray-600 hover:text-gray-800 font-medium py-1.5 px-3 rounded-lg hover:bg-gray-100 transition-colors">
                                    View all
                                </a>
                            </div>
                            @else
                            <div class="p-2 border-t border-gray-100 bg-gray-50 rounded-b-xl text-center">
                                <a href="{{ route('notifications.index') }}" class="text-xs text-gray-600 hover:text-gray-800 font-medium">
                                    View all notifications →
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- User Avatar / Profile --}}
                    <div class="hidden md:flex items-center gap-2 pl-2 border-l border-white/10">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-sm font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="hidden lg:block">
                            <p class="text-xs font-medium text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-blue-200">{{ auth()->user()->role ?? 'User' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. STATS CARDS --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <button wire:click="$set('statusFilter', null)" class="rounded-xl border border-gray-200 bg-white p-4 transition-all text-left hover:border-[#02aeef] shadow-sm {{ !$statusFilter ? 'ring-2 ring-[#02aeef]' : '' }}">
                <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Total Indicators</div>
            </button>
            <button wire:click="$set('statusFilter', 'approved')" class="rounded-xl border border-gray-200 bg-white p-4 transition-all text-left hover:border-green-500 shadow-sm {{ $statusFilter === 'approved' ? 'ring-2 ring-green-500' : '' }}">
                <div class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</div>
                <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Approved</div>
            </button>
            <button wire:click="$set('statusFilter', 'pending')" class="rounded-xl border border-gray-200 bg-white p-4 transition-all text-left hover:border-[#02aeef] shadow-sm {{ $statusFilter === 'pending' ? 'ring-2 ring-[#02aeef]' : '' }}">
                <div class="text-2xl font-bold text-[#02aeef]">{{ $stats['pending'] }}</div>
                <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Pending</div>
            </button>
            <button wire:click="$set('statusFilter', 'returned')" class="rounded-xl border border-gray-200 bg-white p-4 transition-all text-left hover:border-red-400 shadow-sm {{ $statusFilter === 'returned' ? 'ring-2 ring-red-500' : '' }}">
                <div class="text-2xl font-bold text-red-600">{{ $stats['returned'] }}</div>
                <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Returned</div>
            </button>
            <button wire:click="$set('statusFilter', 'DRAFT')" class="rounded-xl border border-gray-200 bg-white p-4 transition-all text-left hover:border-gray-400 shadow-sm {{ $statusFilter === 'DRAFT' ? 'ring-2 ring-gray-400' : '' }}">
                <div class="text-2xl font-bold text-gray-600">{{ $stats['draft'] }}</div>
                <div class="text-xs text-gray-500 mt-1 uppercase tracking-wide">Draft</div>
            </button>
        </div>

        {{-- 2.5. ROLE-SPECIFIC ANALYTICS --}}
        {{-- Office Breakdown (for RO, HO, SA, Admin) --}}
        @if(($user->isRO() || $user->canActAsHeadOfOffice() || $user->isSA() || $user->isAdministrator()) && count($officeBreakdown) > 0)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">
                        @if($user->isRO()) PSTO Performance Overview
                        @elseif($user->canActAsHeadOfOffice()) Office Performance Overview
                        @else Office Breakdown
                        @endif
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Indicator completion rate by office</p>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Office</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Returned</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($officeBreakdown as $office)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $office['office_name'] }}</td>
                                        <td class="px-4 py-2 text-sm text-center text-gray-600">{{ $office['total'] }}</td>
                                        <td class="px-4 py-2 text-sm text-center text-green-600">{{ $office['approved'] }}</td>
                                        <td class="px-4 py-2 text-sm text-center text-blue-600">{{ $office['pending'] }}</td>
                                        <td class="px-4 py-2 text-sm text-center text-red-600">{{ $office['rejected'] }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                                @if($office['completion_rate'] >= 80) bg-green-100 text-green-800
                                                @elseif($office['completion_rate'] >= 50) bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800
                                                @endif">
                                                {{ $office['completion_rate'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Performance Trends (for HO, SA, Admin) --}}
        @if(($user->canActAsHeadOfOffice() || $user->isSA() || $user->isAdministrator()) && count($performanceTrends) > 1)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Performance Trends Over Time</h3>
                    <p class="text-sm text-gray-500 mt-1">Annual approval rate trend</p>
                </div>
                <div class="p-4">
                    <div class="flex items-end gap-2 h-32">
                        @foreach($performanceTrends as $trend)
                            <div class="flex-1 flex flex-col items-center">
                                <div class="w-full bg-gray-100 rounded-t-lg relative" style="height: {{ $trend['rate'] * 1.2 }}px; min-height: 4px;">
                                    <div class="absolute inset-0 bg-gradient-to-t from-[#003B5C] to-[#02aeef] rounded-t-lg opacity-80"></div>
                                </div>
                                <div class="mt-2 text-xs font-medium text-gray-600">{{ $trend['year'] }}</div>
                                <div class="text-xs text-gray-500">{{ $trend['rate'] }}%</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Regional Comparison (for SA, Admin) --}}
        @if(($user->isSA() || $user->isAdministrator()) && count($regionalComparison) > 0)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Regional Comparison</h3>
                    <p class="text-sm text-gray-500 mt-1">Performance by region</p>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($regionalComparison as $region)
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-gray-900">{{ $region['region_name'] }}</h4>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                    @if($region['rate'] >= 80) bg-green-100 text-green-800
                                    @elseif($region['rate'] >= 50) bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ $region['rate'] }}%
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <span>{{ $region['approved'] }}/{{ $region['total'] }} approved</span>
                            </div>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full
                                    @if($region['rate'] >= 80) bg-green-500
                                    @elseif($region['rate'] >= 50) bg-yellow-500
                                    @else bg-red-500
                                    @endif"
                                    style="width: {{ $region['rate'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 3. FILTER BAR --}}
        <x-dashboard.filter-bar
            :user="$user"
            :indicatorCategories="$indicatorCategories"
            :offices="$offices"
            :years="$years"
            :pillars="$pillars ?? null"
            :outcomes="$outcomes ?? null"
            :strategies="$strategies ?? null"
            :categoryFilter="$categoryFilter"
            :officeFilter="$officeFilter"
            :yearFilter="$yearFilter"
            :statusFilter="$statusFilter"
            :pillarFilter="$pillarFilter"
            :outcomeFilter="$outcomeFilter"
            :strategyFilter="$strategyFilter"
            :search="$search"
            :startDate="$startDate"
            :endDate="$endDate"
            :sortBy="$sortBy"
            :sortDirection="$sortDirection"
            :myIndicatorsOnly="$myIndicatorsOnly"
        />

        {{-- 4. TABLE --}}
<div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-auto max-h-[400px]">
        <table class="w-full text-sm table-auto">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-left text-gray-500 uppercase tracking-wider text-xs">
                    {{-- Checkbox Column for SuperAdmin Bulk Operations --}}
                    @if($user->isSuperAdmin())
                        <th class="w-10 px-2 py-3 font-semibold text-center">
                            <input type="checkbox" wire:model.live="selectAll" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </th>
                    @endif
                    <th class="px-4 py-3 font-semibold">Indicator</th>
                    <th class="px-4 py-3 font-semibold">Category</th>
                    @if(!$user->isPSTO()) <th class="px-4 py-3 font-semibold">Office</th> @endif

                    {{-- Dynamic Accomplishments Header - spans based on max years from component --}}
                    <th class="px-4 py-3 font-semibold text-center" colspan="{{ $maxYears }}">
                        Accomplishments
                    </th>
                    
                    <th class="px-4 py-3 font-semibold text-center">Perf (%)</th>
                    @if($this->categoryFieldsForTable && count($this->categoryFieldsForTable) > 0)
                        @foreach($this->categoryFieldsForTable as $catField)
                            @if($catField['field_name'] === 'pillar')
                                <th class="px-4 py-3 font-semibold @if($sortBy === 'pillar_value') bg-blue-100 @endif">
                                    Pillar
                                    @if($sortBy === 'pillar_value')
                                        <span class="ml-1 text-lg @if($sortDirection === 'asc') text-blue-600 @else text-blue-800 @endif">
                                            @if($sortDirection === 'asc') ▲ @else ▼ @endif
                                        </span>
                                    @endif
                                </th>
                            @elseif($catField['field_name'] === 'outcome')
                                <th class="px-4 py-3 font-semibold @if($sortBy === 'outcome_value') bg-blue-100 @endif">
                                    Outcome
                                    @if($sortBy === 'outcome_value')
                                        <span class="ml-1 text-lg @if($sortDirection === 'asc') text-blue-600 @else text-blue-800 @endif">
                                            @if($sortDirection === 'asc') ▲ @else ▼ @endif
                                        </span>
                                    @endif
                                </th>
                            @elseif($catField['field_name'] === 'strategy')
                                <th class="px-4 py-3 font-semibold @if($sortBy === 'strategy_value') bg-blue-100 @endif">
                                    Strategy
                                    @if($sortBy === 'strategy_value')
                                        <span class="ml-1 text-lg @if($sortDirection === 'asc') text-blue-600 @else text-blue-800 @endif">
                                            @if($sortDirection === 'asc') ▲ @else ▼ @endif
                                        </span>
                                    @endif
                                </th>
                            @else
                                <th class="px-4 py-3 font-semibold">{{ $catField['field_label'] }}</th>
                            @endif
                        @endforeach
                    @endif
                    <th class="px-4 py-3 font-semibold text-center">Status</th>
                    <th class="px-4 py-3 text-center font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($objectives as $obj)
                    <tr class="hover:bg-blue-50/50 transition group">
                        {{-- Checkbox for SuperAdmin --}}
                        @if($user->isSuperAdmin())
                            <td class="w-10 px-2 py-3 text-center">
                                <input type="checkbox" value="{{ $obj->id }}" wire:model.live="selectedIndicators"
                                    class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </td>
                        @endif
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900 truncate max-w-xs" title="{{ $obj->indicator }}">{{ $obj->indicator ?? '—' }}</div>
                            @if($obj->is_mandatory) <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">MANDATORY</span> @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $indicatorCategories->firstWhere('slug', $obj->category ?? ($obj->chapter->category ?? '-'))?->name ?? ucfirst(str_replace('_', ' ', $obj->category ?? '-')) }}
                        </td>
                        @if(!$user->isPSTO()) <td class="px-4 py-3 text-gray-500">{{ $obj->office?->name ?? '—' }}</td> @endif
                        
                        {{-- Dynamic Year Columns --}}
                        @php
                            // STEP 1: ALWAYS build full year range from target_period first
                            $years = [];
                            $period = $obj->target_period ?? '';

                            if (strpos($period, '-') !== false) {
                                // Range: "2027-2029"
                                $parts = explode('-', $period);
                                if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                                    $startYear = (int)$parts[0];
                                    $endYear = (int)$parts[1];
                                    $yearCount = $endYear - $startYear + 1;

                                    // Calculate target per year from target_value
                                    $targetPerYear = $yearCount > 0 ? ($obj->target_value ?? 0) / $yearCount : 0;

                                    for ($y = $startYear; $y <= $endYear; $y++) {
                                        $years[$y] = [
                                            'target' => $targetPerYear,
                                            'actual' => 0
                                        ];
                                    }
                                }
                            } else {
                                // Single year: "2027"
                                $years[$period] = [
                                    'target' => $obj->target_value ?? 0,
                                    'actual' => 0
                                ];
                            }

                            // STEP 2: Populate target from annual_plan_targets_series (more specific than target_value)
                            $targets = collect($obj->annual_plan_targets_series ?? []);
                            if (!$targets->isEmpty()) {
                                foreach ($years as $year => $data) {
                                    $found = $targets->firstWhere('year', (int)$year);
                                    if ($found && isset($found['value'])) {
                                        $years[$year]['target'] = (float)$found['value'];
                                    }
                                }
                            }

                            // STEP 3: Populate actual from accomplishments_series (handles both 'year' and 'period' keys)
                            $actuals = collect($obj->accomplishments_series ?? []);
                            if (!$actuals->isEmpty()) {
                                $findActual = function($actuals, $year) {
                                    $found = $actuals->first(function($item) use ($year) {
                                        return (isset($item['year']) && $item['year'] == $year)
                                            || (isset($item['period']) && $item['period'] == (string)$year);
                                    });
                                    return $found['value'] ?? 0;
                                };

                                foreach ($years as $year => $data) {
                                    $years[$year]['actual'] = (float)$findActual($actuals, $year);
                                }
                            }

                            ksort($years); // Sort by year
                            $totalActual = array_sum(array_column($years, 'actual'));
                            $totalTarget = array_sum(array_column($years, 'target'));
                        @endphp
                        
                        {{-- Output each year as a separate <td> - Always render exactly maxYears cells --}}
                        @if(count($years) > 0)
                            @foreach($years as $year => $data)
                                <td class="px-3 py-2 border-r border-gray-100 text-center min-w-[100px]">
                                    <div class="text-xs font-bold text-gray-700 mb-2">{{ $year }}</div>
                                    <div class="flex justify-center gap-2 text-xs whitespace-nowrap">
                                        <div class="flex flex-col items-center">
                                            <span class="text-gray-500 font-semibold">T</span>
                                            <span class="text-gray-900">{{ number_format($data['target']) }}</span>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-gray-500 font-semibold">A</span>
                                            @php
                                                $actualVal = $data['actual'] ?? 0;
                                                $targetVal = $data['target'] ?? 0;
                                                $percentage = $targetVal > 0 ? ($actualVal / $targetVal) * 100 : 0;

                                                // Color coding: 1-50% red, 51-80% yellow, 81-100% green
                                                if ($percentage >= 81) {
                                                    $actualColor = 'text-green-700 bg-green-100 hover:bg-green-200 dark:bg-green-900/40 dark:text-green-300 dark:hover:bg-green-900/60 font-semibold';
                                                } elseif ($percentage >= 51) {
                                                    $actualColor = 'text-yellow-700 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/40 dark:text-yellow-300 dark:hover:bg-yellow-900/60 font-semibold';
                                                } elseif ($percentage >= 1) {
                                                    $actualColor = 'text-red-700 bg-red-100 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 dark:hover:bg-red-900/60 font-semibold';
                                                } else {
                                                    $actualColor = 'text-[#02aeef] hover:bg-[#02aeef]/10 dark:hover:bg-[#02aeef]/30';
                                                }
                                            @endphp
                                            <div class="flex items-center gap-1">
                                                <span wire:click="startEditingYear({{ $obj->id }}, {{ $year }}, {{ $data['actual'] ?? 0 }})"
                                                    class="font-bold cursor-pointer px-1 rounded transition {{ $actualColor }}"
                                                    title="Click to update actual value (requires MFO + proof)">
                                                    {{ number_format($data['actual']) }}
                                                </span>
                                                @if($targetVal > 0 && $percentage > 0 && $percentage <= 100)
                                                    <span class="text-xs text-gray-400">({{ number_format($percentage, 0) }}%)</span>
                                                @endif
                                                @php
                                                    $hasProof = $this->proofsByObjectiveAndYear->has($obj->id . '|' . $year);
                                                    $proof = $hasProof ? $this->proofsByObjectiveAndYear->get($obj->id . '|' . $year)->first() : null;
                                                @endphp
                                                @if($proof)
                                                    <button wire:click="openProofViewer({{ $proof->id }})"
                                                            class="text-green-500 hover:text-green-700 transition p-0.5 rounded hover:bg-green-50"
                                                            title="Proof uploaded: {{ $proof->file_name }} (MFO: {{ $proof->mfo_reference ?? 'N/A' }})">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            @endforeach

                            {{-- CRITICAL: Pad with empty cells to match maxYears --}}
                            @php
                                $emptyCellsNeeded = $maxYears - count($years);
                            @endphp
                            @for($i = 0; $i < $emptyCellsNeeded; $i++)
                                <td class="px-3 py-2 border-r border-gray-100 text-center min-w-[100px]">
                                    <span class="text-gray-300 text-xs">—</span>
                                </td>
                            @endfor
                        @else
                            {{-- If no years data, render maxYears empty cells --}}
                            @for($i = 0; $i < $maxYears; $i++)
                                <td class="px-3 py-2 border-r border-gray-100 text-center min-w-[100px]">
                                    <span class="text-gray-300 text-xs">—</span>
                                </td>
                            @endfor
                        @endif
                        
                        <td class="px-4 py-3 text-center">
                            @if($totalTarget > 0)
                                @php
                                    $pct = ($totalActual / $totalTarget) * 100;
                                    $col = $pct >= 100 ? 'text-green-600 bg-green-50' : ($pct >= 50 ? 'text-yellow-600 bg-yellow-50' : 'text-red-600 bg-red-50');
                                @endphp
                                <span class="inline-block px-2 py-1 rounded text-xs font-bold {{ $col }}">{{ number_format($pct, 1) }}%</span>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        
                        @if($this->categoryFieldsForTable && count($this->categoryFieldsForTable) > 0)
                            @foreach($this->categoryFieldsForTable as $catField)
                                <td class="px-4 py-3">
                                    @php
                                        $dbColumn = $catField['db_column'];
                                        $fieldName = $catField['field_name'];

                                        // Handle pillar/outcome/strategy - show numeric value from relationship
                                        if ($fieldName === 'pillar') {
                                            $displayValue = $obj->pillar?->value ?? '—';
                                            $titleValue = $obj->pillar?->value ?? '';
                                        } elseif ($fieldName === 'outcome') {
                                            $displayValue = $obj->outcome?->value ?? '—';
                                            $titleValue = $obj->outcome?->value ?? '';
                                        } elseif ($fieldName === 'strategy') {
                                            $displayValue = $obj->strategy?->value ?? '—';
                                            $titleValue = $obj->strategy?->value ?? '';
                                        } else {
                                            $value = $obj->{$dbColumn} ?? '';
                                            if ($catField['field_type'] === 'textarea') {
                                                $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                                            } else {
                                                $displayValue = $value;
                                            }
                                            $titleValue = $value;
                                        }
                                    @endphp
                                    <div class="text-gray-600 {{ $catField['field_type'] === 'textarea' ? 'truncate max-w-[200px]' : '' }} font-mono" title="{{ $titleValue }}">
                                        {{ $displayValue }}
                                    </div>
                                </td>
                            @endforeach
                        @endif
                        <td class="px-4 py-3 text-center align-middle">
                            <span class="inline-flex items-center justify-center text-center px-2 py-1 rounded-full text-xs font-semibold max-w-[180px] whitespace-normal break-words {{
                                $obj->status === \App\Models\Indicator::STATUS_APPROVED ? 'bg-green-100 text-green-700' :
                                (in_array($obj->status, ['submitted_to_ro', 'submitted_to_ho', 'submitted_to_ousec', 'submitted_to_admin', 'submitted_to_superadmin']) ? 'bg-yellow-100 text-yellow-700' :
                                (in_array($obj->status, ['rejected', 'returned_to_psto', 'returned_to_agency', 'returned_to_ro', 'returned_to_ho', 'returned_to_ousec', 'returned_to_admin']) ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'))
                            }}">
                                @php
                                    $creatorName = $obj->submitter?->name ?? $obj->submitter?->username ?? 'Unknown';
                                    $isAgencyFlow = $obj->submitter && $obj->submitter->isAgency();
                                    $ousecLabel = $isAgencyFlow ? 'OUSEC-RD/STS' : 'OUSEC-RO';

                                    $statusLabel = match($obj->status) {
                                        'DRAFT' => "Draft by {$creatorName}",
                                        'submitted_to_ro' => 'To be approved by RO',
                                        'submitted_to_ho' => 'To be approved by HO',
                                        'submitted_to_ousec' => "To be approved by {$ousecLabel}",
                                        'submitted_to_admin' => 'To be approved by Admin',
                                        'submitted_to_superadmin' => 'To be approved by SuperAdmin',
                                        'returned_to_psto' => 'Returned to PSTO',
                                        'returned_to_ro' => 'Returned to RO',
                                        'returned_to_ho' => 'Returned to HO',
                                        'returned_to_ousec' => "Returned to {$ousecLabel}",
                                        'returned_to_admin' => 'Returned to Admin',
                                        'returned_to_agency' => 'Returned to Agency',
                                        'approved' => 'Approved',
                                        default => ucfirst(str_replace('_', ' ', $obj->status)),
                                    };
                                @endphp
                                {{ $statusLabel }}
                            </span>
                            @if($obj->status === 'rejected' && $obj->rejection_reason)
                                <div class="mt-1 text-[10px] text-red-600 italic max-w-[150px] truncate" title="{{ $obj->rejection_reason }}">
                                    {{ $obj->rejection_reason }}
                                </div>
                            @endif
                        </td>
                        <x-dashboard.table-actions :objective="$obj" :user="$user" :viewMode="$viewMode" :editingQuickFormId="$editingQuickFormId" />
                    </tr>
                @empty
                    <tr><td colspan="{{ $totalColumns }}" class="px-4 py-12 text-center text-gray-500">No indicators found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-200 bg-gray-50 relative z-50 flex items-center justify-between gap-4">
    {{-- Per-Page Input --}}
    <div class="flex items-center gap-2">
        <span class="text-sm text-gray-600">Show:</span>
        <input type="number"
               wire:model.live.debounce.300ms="perPage"
               min="1"
               max="500"
               class="w-20 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-white"
               placeholder="20">
        <span class="text-sm text-gray-600">per page</span>
    </div>

    {{-- Pagination Links --}}
    <div class="flex-1">{{ $objectives->links() }}</div>
</div>
    </div>

    {{-- SUPERADMIN: BULK OPERATIONS BAR --}}
    @if(auth()->user()->isSuperAdmin() && count($selectedIndicators) > 0)
        <div class="fixed bottom-0 left-0 right-0 bg-indigo-900 text-white p-4 z-40 shadow-lg border-t-4 border-indigo-700">
            <div class="container mx-auto flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="font-semibold">{{ count($selectedIndicators) }} indicators selected</span>
                    <button wire:click="selectedIndicators = []; selectAll = false" class="text-indigo-300 hover:text-white underline text-sm">Clear selection</button>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-indigo-300 mr-2">Bulk actions:</span>
                    <button wire:click="openBulkModal('bulkDelete')" class="px-3 py-2 bg-red-600 hover:bg-red-700 rounded text-sm font-medium">Delete All</button>
                    <button wire:click="openBulkModal('bulkReopen')" class="px-3 py-2 bg-[#02aeef] hover:bg-[#0299d5] rounded text-sm font-medium">Reopen All</button>
                    <button wire:click="openBulkModal('bulkApprove')" class="px-3 py-2 bg-green-600 hover:bg-green-700 rounded text-sm font-medium">Approve All</button>
                    <button wire:click="openBulkModal('bulkReject')" class="px-3 py-2 bg-orange-600 hover:bg-orange-700 rounded text-sm font-medium">Reject All</button>
                </div>
            </div>
        </div>
    @endif

    {{-- SUPERADMIN: FORCE STATUS MODAL --}}
    <div x-data="{ show: @entangle('showForceStatusModal') }"
            x-show="show"
            x-on:keydown.escape.window="show = false; $wire.closeForceStatusModal()"
            x-cloak
            class="fixed inset-0 z-[99999] overflow-y-auto"
            role="dialog"
            aria-modal="true">
           <div x-show="show" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" style="z-index: 1;"></div>

           <div class="flex min-h-full items-center justify-center p-4" style="position: relative; z-index: 2;">
               <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6"
                    @click.away="show = false; $wire.closeForceStatusModal()">

                   <div class="flex justify-center mb-4">
                       <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center">
                           <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                       </div>
                   </div>

                   <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">Force Status Change</h3>
                   <p class="text-sm text-gray-600 mb-4 text-center">
                       This will override the normal workflow and force the indicator to <strong>{{ $forceStatusTarget }}</strong> status.
                   </p>

                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1">Reason (required for audit)</label>
                       <textarea wire:model="overrideReason" rows="2" placeholder="Enter reason for this override..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                   </div>

                   <div class="flex justify-end gap-3">
                       <button wire:click="closeForceStatusModal()"
                               class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                           Cancel
                       </button>
                       <button wire:click="executeForceStatus()"
                               wire:loading.attr="disabled"
                               class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                           Force Status
                       </button>
                   </div>
               </div>
           </div>
       </div>

    {{-- SUPERADMIN: BULK OPERATIONS MODAL --}}
    <div x-data="{ show: @entangle('showBulkModal') }"
            x-show="show"
            x-on:keydown.escape.window="show = false; $wire.closeBulkModal()"
            x-cloak
            class="fixed inset-0 z-[9999999] overflow-y-auto"
            role="dialog"
            aria-modal="true">
           <div x-show="show" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" style="z-index: 1;"></div>

           <div class="flex min-h-full items-center justify-center p-4" style="position: relative; z-index: 2;">
               <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6"
                    @click.away="show = false; $wire.closeBulkModal()">

                   @if($bulkAction === 'bulkDelete')
                       <div class="flex justify-center mb-4">
                           <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                               <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                           </div>
                       </div>
                       <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">Bulk Delete Indicators</h3>
                       <p class="text-sm text-gray-600 mb-6 text-center">
                           Are you sure you want to delete {{ count($selectedIndicators) }} indicators? This action cannot be undone.
                       </p>
                   @elseif($bulkAction === 'bulkReopen')
                       <div class="flex justify-center mb-4">
                           <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                               <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                           </div>
                       </div>
                       <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">Bulk Reopen Indicators</h3>
                       <p class="text-sm text-gray-600 mb-6 text-center">
                           This will send {{ count($selectedIndicators) }} indicators back to draft status.
                       </p>
                   @elseif($bulkAction === 'bulkApprove')
                       <div class="flex justify-center mb-4">
                           <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                               <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                           </div>
                       </div>
                       <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">Bulk Approve Indicators</h3>
                       <p class="text-sm text-gray-600 mb-6 text-center">
                           This will approve {{ count($selectedIndicators) }} indicators without workflow.
                       </p>
                   @elseif($bulkAction === 'bulkReject')
                       <div class="flex justify-center mb-4">
                           <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center">
                               <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                           </div>
                       </div>
                       <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">Bulk Reject Indicators</h3>
                       <p class="text-sm text-gray-600 mb-6 text-center">
                           This will reject {{ count($selectedIndicators) }} indicators.
                       </p>
                   @endif

                   <div class="flex justify-end gap-3">
                       <button wire:click="closeBulkModal()"
                               class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                           Cancel
                       </button>
                       <button wire:click="executeBulkAction()"
                               wire:loading.attr="disabled"
                               class="px-4 py-2 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition
                               @if($bulkAction === 'bulkDelete') bg-red-600 hover:bg-red-700
                               @elseif($bulkAction === 'bulkReopen') bg-[#02aeef] hover:bg-[#0299d5]
                               @elseif($bulkAction === 'bulkApprove') bg-green-600 hover:bg-green-700
                               @else bg-orange-600 hover:bg-orange-700 @endif">
                           Confirm Bulk Action
                       </button>
                   </div>
               </div>
           </div>
       </div>

    {{-- 5. QUICK CREATE MODAL (Unified Form with Dynamic Fields) --}}
    <x-dashboard.modals.quick-form-modal
        :show="$showQuickForm"
        :viewMode="$viewMode"
        :isUpdateProgress="$isUpdateProgress"
        :adminBypassMode="$adminBypassMode"
        :editingQuickFormId="$editingQuickFormId"
        :indicatorCategories="$indicatorCategories"
        :dynamicFields="$dynamicFields"
        :pillars="$pillars"
        :outcomes="$outcomes"
        :strategies="$strategies"
        :quickForm="$quickForm"
        :dynamicValues="$dynamicValues"
        :breakdown="$breakdown"
        :chartData="$chartData ?? null"
        :indicatorHistory="$indicatorHistory ?? null"
        :indicatorProofs="$indicatorProofs"
    />

    {{-- REJECTION MODAL --}}
    <x-dashboard.modals.rejection-modal
        :show="$showRejectionModal"
        :rejectionReason="$rejectionReason"
    />

    {{-- ADMIN CONFIRMATION MODAL --}}
    <x-dashboard.modals.admin-confirm-modal
        :show="$showAdminConfirmModal"
        :adminConfirmAction="$adminConfirmAction"
        :adminConfirmTitle="$adminConfirmTitle"
        :adminConfirmMessage="$adminConfirmMessage"
    />

    {{-- APPROVAL CONFIRMATION MODAL --}}
    <x-dashboard.modals.approval-confirm-modal
        :show="$showApprovalConfirmModal"
        :approvalConfirmAction="$approvalConfirmAction"
        :approvalConfirmTitle="$approvalConfirmTitle"
        :approvalConfirmMessage="$approvalConfirmMessage"
        :approvalConfirmIndicator="$approvalConfirmIndicator"
    />

    {{-- DELETE CONFIRMATION MODAL --}}
    <x-dashboard.modals.delete-confirm-modal
        :show="$showDeleteConfirmModal"
    />

    {{-- USER MANAGEMENT PANEL --}}
    <x-dashboard.modals.user-management-panel
        :show="$showUserManagementPanel"
        :usersForManagement="$this->usersForManagement"
    />

    {{-- GLOBAL CONFIRMATION MODAL --}}
    <x-dashboard.modals.global-confirm-modal />

    {{-- MFO + PROOF MODAL FOR ACTUAL VALUE UPDATES --}}
    <div x-data="{ show: @entangle('showProofModal') }"
            x-show="show"
            x-on:keydown.escape.window="show = false; $wire.closeProofModal()"
            x-cloak
            class="fixed inset-0 z-[999999] overflow-y-auto"
            role="dialog"
            aria-modal="true">
           <div x-show="show" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" style="z-index: 1;"></div>

           <div class="flex min-h-full items-center justify-center p-4" style="position: relative; z-index: 2;">
               <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6"
                    @click.away="closeProofModal()">

                   <div class="flex justify-center mb-4">
                       <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                           <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                           </svg>
                       </div>
                   </div>

                   <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">Update Actual Value</h3>
                   <p class="text-sm text-gray-600 mb-6 text-center">
                       Year {{ $proofYear }} • Current: {{ number_format($proofActualValue) }}
                   </p>

                   {{-- New Actual Value Input --}}
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1">New Actual Value</label>
                       <input type="number" step="any" wire:model.defer="proofActualValue"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter new actual value">
                   </div>

                   {{-- MFO Reference --}}
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1">
                           MFO Reference <span class="text-red-500">*</span>
                       </label>
                       <input type="text" wire:model.defer="proofMfoReference"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter MFO reference number">
                       @error('proofMfoReference')
                           <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                       @enderror
                   </div>

                   {{-- Proof File Upload --}}
                   <div class="mb-4">
                       <label class="block text-sm font-medium text-gray-700 mb-1">
                           Proof Document <span class="text-red-500">*</span>
                       </label>
                       <input type="file" wire:model.defer="proofFile"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                       @error('proofFile')
                           <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                       @enderror
                       <p class="text-xs text-gray-500 mt-1">Accepted: PDF, DOC, DOCX, JPG, PNG (max 10MB)</p>
                       @if($proofFile)
                           <p class="text-xs text-green-600 mt-1">Selected: {{ $proofFile->getClientOriginalName() }}</p>
                       @endif
                   </div>

                   <div class="flex justify-end gap-3 mt-6">
                       <button wire:click="closeProofModal()"
                               class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                           Cancel
                       </button>
                       <button wire:click="saveYearWithProof()"
                               wire:loading.attr="disabled"
                               class="px-4 py-2 bg-[#02aeef] text-white rounded-lg hover:bg-[#0299d5] disabled:opacity-50 disabled:cursor-not-allowed transition">
                           Save Update
                       </button>
                   </div>
               </div>
           </div>
       </div>

    {{-- PROOF VIEWER MODAL --}}
    <div x-data="{ show: @entangle('showProofViewerModal') }"
            x-show="show"
            x-on:keydown.escape.window="show = false; $wire.closeProofViewer()"
            x-cloak
            class="fixed inset-0 z-[9999999] overflow-y-auto"
            role="dialog"
            aria-modal="true">
           <div x-show="show" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" style="z-index: 1;"></div>

           <div class="flex min-h-full items-center justify-center p-4" style="position: relative; z-index: 2;">
               <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl p-0"
                    @click.away="closeProofViewer()">

                   @if($viewingProof)
                       {{-- Header --}}
                       <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50 rounded-t-xl">
                           <div class="flex items-center gap-3">
                               <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                                   <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                       <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                   </svg>
                               </div>
                               <div>
                                   <h3 class="text-lg font-bold text-gray-900">Proof Document</h3>
                                   <p class="text-sm text-gray-500">{{ $viewingProof->file_name }}</p>
                               </div>
                           </div>
                           <button wire:click="closeProofViewer()" class="text-gray-500 hover:text-gray-700">
                               <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                               </svg>
                           </button>
                       </div>

                       {{-- Info Bar --}}
                       <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex flex-wrap items-center gap-4 text-xs text-gray-600">
                           <span><strong>Size:</strong> {{ $viewingProof->human_file_size }}</span>
                           <span><strong>Uploaded:</strong> {{ $viewingProof->created_at->format('M j, Y g:i A') }}</span>
                           <span><strong>By:</strong> {{ $viewingProof->uploader->name ?? 'Unknown' }}</span>
                           @if($viewingProof->year)
                               <span><strong>Year:</strong> <span class="text-blue-600 font-semibold">{{ $viewingProof->year }}</span></span>
                           @endif
                           @if($viewingProof->mfo_reference)
                               <span><strong>MFO:</strong> <span class="font-mono text-green-700">{{ $viewingProof->mfo_reference }}</span></span>
                           @endif
                       </div>

                       {{-- Preview Area --}}
                       <div class="p-4">
                           @php
                               $fileName = is_array($viewingProof->file_name) ? $viewingProof->file_name[0] : $viewingProof->file_name;
                               $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION) ?? '');
                           @endphp
                           @if(in_array($fileExt, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'jpe']))
                               {{-- Image Preview --}}
                               <div class="flex justify-center bg-gray-100 rounded-lg p-4">
                                   <img src="{{ $viewingProof->url }}" alt="Proof document" class="max-w-full max-h-[500px] object-contain rounded-lg shadow-sm">
                               </div>
                           @elseif($fileExt === 'pdf')
                               {{-- PDF Preview --}}
                               <div class="bg-gray-100 rounded-lg p-4">
                                   <div class="flex justify-center items-center h-[400] text-gray-500">
                                       <div class="text-center">
                                           <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2">
                                               <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                               <path d="M14 2v6h6M16 13H8"/>
                                               <path d="M16 17H8"/>
                                               <path d="M10 9H8"/>
                                           </svg>
                                           <p class="text-sm">PDF Preview</p>
                                           <p class="text-xs text-gray-400 mt-1">Click button below to view in new tab</p>
                                       </div>
                                   </div>
                               </div>
                           @else
                               {{-- Other file types --}}
                               <div class="flex justify-center items-center h-[200] bg-gray-100 rounded-lg">
                                   <div class="text-center">
                                       <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2">
                                           <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.707.293H19a2 2 0 012-2z"/>
                                           <path d="M14 2v6h6M16 13H8"/>
                                           <path d="M16 17H8"/>
                                           <path d="M10 9H8"/>
                                       </svg>
                                       <p class="text-sm text-gray-600">Document Preview</p>
                                       <p class="text-xs text-gray-400 mt-1">Click button below to view</p>
                                   </div>
                               </div>
                           @endif
                       </div>

                       {{-- Footer with Download Button --}}
                       <div class="p-4 border-t border-gray-200 bg-gray-50 rounded-b-xl flex justify-end gap-3">
                           <a href="{{ $viewingProof->url }}" target="_blank"
                              class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                               Open in New Tab
                           </a>
                           <button wire:click="closeProofViewer()"
                                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm font-medium">
                               Close
                           </button>
                       </div>
                   @endif
               </div>
           </div>
       </div>

{{-- IMPORT BUTTON - Always visible, outside main wrapper --}}
<x-dashboard.modals.import-modal
    :importFile="$importFile"
    :importErrors="$importErrors"
    :importedCount="$importedCount"
    :importing="$importing"
/>
</div>