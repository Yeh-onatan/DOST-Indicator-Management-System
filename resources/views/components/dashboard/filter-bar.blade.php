@props([
    'user' => null,
    'indicatorCategories' => null,
    'offices' => null,
    'years' => null,
    'pillars' => null,
    'outcomes' => null,
    'strategies' => null,
    'categoryFilter' => null,
    'officeFilter' => null,
    'yearFilter' => null,
    'statusFilter' => null,
    'pillarFilter' => null,
    'outcomeFilter' => null,
    'strategyFilter' => null,
    'search' => null,
    'startDate' => null,
    'endDate' => null,
    'sortBy' => null,
    'sortDirection' => null,
    'myIndicatorsOnly' => false,
])

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div class="flex flex-wrap items-center gap-3">
        {{-- Category Filter --}}
        @if($user->isSA() || $user->isAdministrator() || $user->canActAsHeadOfOffice() || $user->isRO() || $user->isPSTO())
            <x-nested-filter
                label="Category"
                :options="['' => 'All Categories'] + $indicatorCategories->pluck('name', 'slug')->toArray()"
                wireModel="categoryFilter"
            />
        @endif

        {{-- Office Filter (for SA, Admin, HO, RO) --}}
        @if(($user->isSA() || $user->isAdministrator() || $user->canActAsHeadOfOffice() || $user->isRO()) && $offices->count() > 0)
            <x-nested-filter
                label="Office"
                :options="['' => $user->canActAsHeadOfOffice() ? 'Your Office Scope' : ($user->isRO() ? 'Your RO + PSTOs' : 'All Offices')] + $offices->pluck('name', 'id')->toArray()"
                wireModel="officeFilter"
            />
        @endif

        {{-- Year Filter --}}
        <x-nested-filter
            label="Year"
            :options="['' => 'All Years'] + array_combine($years->toArray(), $years->toArray())"
            wireModel="yearFilter"
        />

        {{-- Status Filter --}}
        <x-nested-filter
            label="Status"
            :options="[
                '' => 'All Status',
                'draft' => 'Draft',
                'submitted_to_ro' => 'Submitted to RO',
                'submitted_to_ho' => 'Submitted to H.O.',
                'submitted_to_ousec' => 'Submitted to OUSEC',
                'submitted_to_admin' => 'Submitted to Admin',
                'submitted_to_superadmin' => 'Submitted to SuperAdmin',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'returned_to_psto' => 'Returned to PSTO',
                'returned_to_agency' => 'Returned to Agency',
                'returned_to_ro' => 'Returned to RO',
                'returned_to_ho' => 'Returned to HO',
                'returned_to_ousec' => 'Returned to OUSEC',
                'returned_to_admin' => 'Returned to Admin',
                'reopened' => 'Reopened',
            ]"
            wireModel="statusFilter"
        />

        {{-- Search --}}
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search..." class="h-9 px-4 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 focus:ring-[#02aeef] focus:border-[#02aeef] min-w-[200px] flex-1" />

        {{-- More Filters Dropdown (for less frequently used filters) --}}
        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
            <button
                @click="open = !open"
                class="h-9 px-4 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50 transition flex items-center gap-2"
            >
                <span>More</span>
                <svg x-cloak x-show="!open" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                <svg x-cloak x-show="open" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                @click.stop
                class="absolute z-50 mt-2 w-80 rounded-lg shadow-lg bg-white border border-gray-200 p-4"
                style="display: none;"
            >
                <div class="space-y-4">
                    {{-- Date Range --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Date Range</h4>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-[10px] text-gray-400 mb-1">From</label>
                                <input type="date" wire:model.lazy="startDate" class="w-full h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 focus:ring-[#02aeef] focus:border-[#02aeef]">
                            </div>
                            <div class="flex-1">
                                <label class="block text-[10px] text-gray-400 mb-1">To</label>
                                <input type="date" wire:model.lazy="endDate" class="w-full h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 focus:ring-[#02aeef] focus:border-[#02aeef]">
                            </div>
                        </div>
                        @if($startDate || $endDate)
                            <button wire:click="$set('startDate', null); $set('endDate', null)" class="mt-2 text-xs text-red-500 hover:text-red-700">Clear dates</button>
                        @endif
                    </div>

                    {{-- Sort Controls --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Sort By</h4>
                        <div class="flex gap-2">
                            @php
                                $sortOptions = [
                                    ['value' => 'created_at', 'label' => 'Date Created'],
                                    ['value' => 'updated_at', 'label' => 'Last Updated'],
                                    ['value' => 'target_value', 'label' => 'Target Value'],
                                ];
                                if ($categoryFilter && strtolower($categoryFilter) === 'strategic_plan') {
                                    $sortOptions[] = ['value' => 'pillar_value', 'label' => 'Pillar'];
                                    $sortOptions[] = ['value' => 'outcome_value', 'label' => 'Outcome'];
                                    $sortOptions[] = ['value' => 'strategy_value', 'label' => 'Strategy'];
                                }
                            @endphp
                            <select wire:model.live="sortBy" class="flex-1 h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 focus:ring-2 focus:ring-[#00AEEF] focus:border-[#00AEEF]">
                                @foreach($sortOptions as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <button wire:click="toggleSortDirection" class="h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 hover:bg-gray-50 transition flex items-center justify-center gap-1">
                                @if($sortDirection === 'asc')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m-4 4l4-4" />
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m4-4l4 4m-4-4l4-4" />
                                    </svg>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions Group --}}
        <div class="flex items-center gap-2 ml-auto">
            {{-- My Indicators Toggle --}}
            <label class="flex items-center gap-2 h-9 px-3 rounded-lg border border-gray-300 bg-white cursor-pointer hover:bg-gray-50 transition">
                <input type="checkbox" wire:model.live="myIndicatorsOnly" class="w-4 h-4 rounded border-gray-300 text-[#00AEEF] focus:ring-[#00AEEF]">
                <span class="text-sm text-gray-700">My Indicators</span>
            </label>

            <button wire:click="clearFilters" class="h-9 px-4 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
                Clear
            </button>

            <button wire:click="export" class="h-9 rounded-lg text-white px-4 text-sm font-semibold transition hover:opacity-90" style="background-color: #00AEEF">
                Export
            </button>

            {{-- Create Button --}}
            @if(auth()->user()->isPSTO() || auth()->user()->isRO() || auth()->user()->isAgency()
                || auth()->user()->isSA() || auth()->user()->isAdministrator() || auth()->user()->isSuperAdmin())
                <button wire:click="openQuickForm" class="h-9 px-6 rounded-lg bg-[#003B5C] text-white text-sm font-semibold hover:bg-[#002b42] transition">
                    Create
                </button>
            @endif
        </div>
    </div>

    {{-- Dynamic Sub-Filters (show when Strategic Plan selected) --}}
    @if($categoryFilter && strtolower($categoryFilter) === 'strategic_plan')
        <div class="mt-3 pt-3 border-t border-gray-200 flex flex-wrap items-center gap-3">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Strategic Plan Filters:</span>

            @if(isset($pillars) && $pillars->isNotEmpty())
                <x-nested-filter
                    label="Pillar"
                    :options="['' => 'All Pillars'] + $pillars->pluck('value', 'id')->toArray()"
                    wireModel="pillarFilter"
                />
            @endif

            @if(isset($outcomes) && $outcomes->isNotEmpty())
                <x-nested-filter
                    label="Outcome"
                    :options="['' => 'All Outcomes'] + $outcomes->pluck('value', 'id')->toArray()"
                    wireModel="outcomeFilter"
                />
            @endif

            @if(isset($strategies) && $strategies->isNotEmpty())
                <x-nested-filter
                    label="Strategy"
                    :options="['' => 'All Strategies'] + $strategies->pluck('value', 'id')->toArray()"
                    wireModel="strategyFilter"
                    :searchable="true"
                />
            @endif
        </div>
    @endif

    {{-- Active Filters Display --}}
    @if($categoryFilter || $officeFilter || $yearFilter || $statusFilter || $pillarFilter || $outcomeFilter || $strategyFilter || $search || $startDate || $endDate)
        <div class="mt-3 pt-3 border-t border-gray-200 flex flex-wrap items-center gap-2">
            @if($categoryFilter)
                @php $catName = $indicatorCategories->where('slug', $categoryFilter)->first()?->name ?? $categoryFilter; @endphp
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                    Category: {{ $catName }}
                    <button wire:click="clearFilter('category')" class="hover:text-blue-900 ml-1">&times;</button>
                </span>
            @endif

            @if($officeFilter)
                @php $officeName = $offices->where('id', $officeFilter)->first()?->name ?? $officeFilter; @endphp
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                    Office: {{ $officeName }}
                    <button wire:click="clearFilter('office')" class="hover:text-blue-900 ml-1">&times;</button>
                </span>
            @endif

            @if($yearFilter)
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                    Year: {{ $yearFilter }}
                    <button wire:click="clearFilter('year')" class="hover:text-blue-900 ml-1">&times;</button>
                </span>
            @endif

            @if($statusFilter)
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                    Status: {{ str_replace('_', ' ', ucfirst($statusFilter)) }}
                    <button wire:click="clearFilter('status')" class="hover:text-blue-900 ml-1">&times;</button>
                </span>
            @endif

            @if($pillarFilter && isset($pillars))
                @php $pillarName = $pillars->where('id', $pillarFilter)->first()?->value ?? $pillarFilter; @endphp
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                    Pillar: {{ $pillarName }}
                    <button wire:click="clearFilter('pillar')" class="hover:text-blue-900 ml-1">&times;</button>
                </span>
            @endif

            @if($outcomeFilter && isset($outcomes))
                @php $outcomeName = $outcomes->where('id', $outcomeFilter)->first()?->value ?? $outcomeFilter; @endphp
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                    Outcome: {{ $outcomeName }}
                    <button wire:click="clearFilter('outcome')" class="hover:text-blue-900 ml-1">&times;</button>
                </span>
            @endif

            @if($strategyFilter && isset($strategies))
                @php $strategyName = $strategies->where('id', $strategyFilter)->first()?->value ?? $strategyFilter; @endphp
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                    Strategy: {{ $strategyName }}
                    <button wire:click="clearFilter('strategy')" class="hover:text-blue-900 ml-1">&times;</button>
                </span>
            @endif

            @if($search)
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                    Search: {{ $search }}
                    <button wire:click="clearFilter('search')" class="hover:text-blue-900 ml-1">&times;</button>
                </span>
            @endif

            @if($startDate || $endDate)
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                    Date: {{ $startDate ?? '...' }} to {{ $endDate ?? '...' }}
                    <button wire:click="$set('startDate', null); $set('endDate', null)" class="hover:text-blue-900 ml-1">&times;</button>
                </span>
            @endif
        </div>
    @endif
</div>
