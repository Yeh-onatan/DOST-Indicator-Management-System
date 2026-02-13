<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-[var(--text)]">Admin Dashboard</h1>
    </div>

    {{-- Tab Navigation --}}
    <div class="border-b border-[var(--border)]">
        <nav class="flex gap-4 overflow-x-auto">
            <button
                wire:click="setTab('overview')"
                class="px-4 py-2 text-sm font-semibold border-b-2 transition whitespace-nowrap {{ $activeTab === 'overview' ? 'border-[var(--color-accent)] text-[var(--color-accent)]' : 'border-transparent text-[var(--text-muted)] hover:text-[var(--text)]' }}"
            >
                Overview
            </button>
            <button
                wire:click="setTab('regions')"
                class="px-4 py-2 text-sm font-semibold border-b-2 transition whitespace-nowrap {{ $activeTab === 'regions' ? 'border-[var(--color-accent)] text-[var(--color-accent)]' : 'border-transparent text-[var(--text-muted)] hover:text-[var(--text)]' }}"
            >
                Regions
            </button>
            <button
                wire:click="setTab('offices')"
                class="px-4 py-2 text-sm font-semibold border-b-2 transition whitespace-nowrap {{ $activeTab === 'offices' ? 'border-[var(--color-accent)] text-[var(--color-accent)]' : 'border-transparent text-[var(--text-muted)] hover:text-[var(--text)]' }}"
            >
                Offices
            </button>
            <button
                wire:click="setTab('users')"
                class="px-4 py-2 text-sm font-semibold border-b-2 transition whitespace-nowrap {{ $activeTab === 'users' ? 'border-[var(--color-accent)] text-[var(--color-accent)]' : 'border-transparent text-[var(--text-muted)] hover:text-[var(--text)]' }}"
            >
                Users
            </button>
            <button
                wire:click="setTab('agencies')"
                class="px-4 py-2 text-sm font-semibold border-b-2 transition whitespace-nowrap {{ $activeTab === 'agencies' ? 'border-[var(--color-accent)] text-[var(--color-accent)]' : 'border-transparent text-[var(--text-muted)] hover:text-[var(--text)]' }}"
            >
                Agencies
            </button>
            <button
                wire:click="setTab('categories')"
                class="px-4 py-2 text-sm font-semibold border-b-2 transition whitespace-nowrap {{ $activeTab === 'categories' ? 'border-[var(--color-accent)] text-[var(--color-accent)]' : 'border-transparent text-[var(--text-muted)] hover:text-[var(--text)]' }}"
            >
                Categories
            </button>
            <button
                wire:click="setTab('stratplan')"
                class="px-4 py-2 text-sm font-semibold border-b-2 transition whitespace-nowrap {{ $activeTab === 'stratplan' ? 'border-[var(--color-accent)] text-[var(--color-accent)]' : 'border-transparent text-[var(--text-muted)] hover:text-[var(--text)]' }}"
            >
                StratPlan Manager
            </button>
        </nav>
    </div>

    {{-- Tab Content --}}
    @if($activeTab === 'overview')
        {{-- Overview Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl border border-[var(--border)] bg-[var(--card-bg)] p-4">
                <div class="text-sm text-[var(--text-muted)] mb-1">Total Offices</div>
                <div class="text-3xl font-bold text-[var(--text)]">{{ $stats['total_offices'] }}</div>
                <div class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $stats['active_offices'] }} active</div>
            </div>
            <div class="rounded-xl border border-[var(--border)] bg-[var(--card-bg)] p-4">
                <div class="text-sm text-[var(--text-muted)] mb-1">Total Users</div>
                <div class="text-3xl font-bold text-[var(--text)]">{{ $stats['total_users'] }}</div>
                <div class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $stats['active_users'] }} verified</div>
            </div>
            <div class="rounded-xl border border-[var(--border)] bg-[var(--card-bg)] p-4">
                <div class="text-sm text-[var(--text-muted)] mb-1">Total Agencies</div>
                <div class="text-3xl font-bold text-[var(--text)]">{{ $stats['total_agencies'] }}</div>
                <div class="text-xs text-[var(--text-muted)] mt-1">Agencies</div>
            </div>
            <div class="rounded-xl border border-[var(--border)] bg-[var(--card-bg)] p-4">
                <div class="text-sm text-[var(--text-muted)] mb-1">Total Indicators</div>
                <div class="text-3xl font-bold text-[var(--text)]">{{ $stats['total_indicators'] }}</div>
                <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['pending_approvals'] }} Pending Approval</div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="rounded-xl border border-[var(--border)] bg-[var(--card-bg)] p-6">
            <h2 class="text-xl font-bold text-[var(--text)] mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <button
                    wire:click="setTab('regions')"
                    class="p-4 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:border-[var(--color-accent)] transition text-left"
                >
                    <div class="mb-2 text-[var(--color-accent)]"><x-icon name="globe" class="w-7 h-7" /></div>
                    <div class="font-semibold text-[var(--text)]">Manage Regions</div>
                    <div class="text-sm text-[var(--text-muted)] mt-1">Add or edit regions</div>
                </button>
                <button
                    wire:click="setTab('offices')"
                    class="p-4 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:border-[var(--color-accent)] transition text-left"
                >
                    <div class="mb-2 text-[var(--color-accent)]"><x-icon name="map" class="w-7 h-7" /></div>
                    <div class="font-semibold text-[var(--text)]">Manage Offices</div>
                    <div class="text-sm text-[var(--text-muted)] mt-1">Configure PSTOs, ROs, and H.O.</div>
                </button>
                <button
                    wire:click="setTab('users')"
                    class="p-4 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:border-[var(--color-accent)] transition text-left"
                >
                    <div class="mb-2 text-[var(--color-accent)]"><x-icon name="users" class="w-7 h-7" /></div>
                    <div class="font-semibold text-[var(--text)]">Manage Users</div>
                    <div class="text-sm text-[var(--text-muted)] mt-1">Create accounts and assign roles</div>
                </button>
                <button
                    wire:click="setTab('agencies')"
                    class="p-4 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:border-[var(--color-accent)] transition text-left"
                >
                    <div class="mb-2 text-[var(--color-accent)]"><x-icon name="building" class="w-7 h-7" /></div>
                    <div class="font-semibold text-[var(--text)]">Manage Agencies</div>
                    <div class="text-sm text-[var(--text-muted)] mt-1">Add and configure agencies</div>
                </button>
                <button
                    wire:click="setTab('categories')"
                    class="p-4 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:border-[var(--color-accent)] transition text-left"
                >
                    <div class="mb-2 text-[var(--color-accent)]"><x-icon name="tag" class="w-7 h-7" /></div>
                    <div class="font-semibold text-[var(--text)]">Manage Categories</div>
                    <div class="text-sm text-[var(--text-muted)] mt-1">Create and manage indicator categories</div>
                </button>
                <button
                    wire:click="setTab('stratplan')"
                    class="p-4 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:border-[var(--color-accent)] transition text-left"
                >
                    <div class="mb-2 text-[var(--color-accent)]"><x-icon name="chart-bar" class="w-7 h-7" /></div>
                    <div class="font-semibold text-[var(--text)]">Manage StratPlan</div>
                    <div class="text-sm text-[var(--text-muted)] mt-1">Manage Pillars, Outcomes, Strategies</div>
                </button>
            </div>
        </div>

    @elseif($activeTab === 'regions')
        <livewire:admin.region-manager />

    @elseif($activeTab === 'offices')
        <livewire:admin.office-manager />

    @elseif($activeTab === 'users')
        <livewire:admin.user-manager />

    @elseif($activeTab === 'agencies')
        <livewire:admin.agency-manager />

    @elseif($activeTab === 'categories')
        <livewire:admin.category-manager />

    @elseif($activeTab === 'stratplan')
        <livewire:admin.strategic-plan-manager />

    @endif
</div>