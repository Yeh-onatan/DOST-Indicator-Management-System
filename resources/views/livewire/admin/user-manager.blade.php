<div class="user-manager-wrapper">
    <div class="p-6 space-y-6">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-[var(--text)] tracking-tight">User Management</h2>
                <p class="text-sm text-[var(--text-muted)]">Create, manage, and assign roles to system users.</p>
            </div>
            <button wire:click="openCreate" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-[var(--color-accent)] text-[var(--color-accent-foreground)] font-semibold hover:opacity-90 transition-all shadow-sm active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
                Add New User
            </button>
        </div>

        {{-- Filters Bar --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 bg-[var(--card-bg)] p-3 rounded-xl border border-[var(--border)] shadow-sm">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-[var(--text-muted)] pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name or email..." class="w-full h-10 pl-9 rounded-lg border border-[var(--border)] bg-[var(--bg)] text-sm text-[var(--text)] focus:ring-2 focus:ring-[var(--color-accent)] transition">
            </div>

            <select wire:model.live="roleFilter" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)] focus:ring-2 focus:ring-[var(--color-accent)] transition">
                <option value="">All Roles</option>
                <option value="super_admin">Super Admin</option>
                <option value="administrator">Administrator</option>
                <option value="execom">EXECOM</option>
                <option value="head_officer">Head of Office</option>
                <option value="ro">Regional Office (RO)</option>
                <option value="psto">Provincial Office (PSTO)</option>
                <option value="agency">Agency</option>
                <option value="ousec_ro">OUSEC-RO</option>
                <option value="ousec_sts">OUSEC-STS</option>
                <option value="ousec_rd">OUSEC-RD</option>
            </select>

            <select wire:model.live="officeFilter" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)] focus:ring-2 focus:ring-[var(--color-accent)] transition">
                <option value="">All Offices</option>
                @foreach($offices as $office)
                    <option value="{{ $office->id }}">{{ $office->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="agencyFilter" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)] focus:ring-2 focus:ring-[var(--color-accent)] transition">
                <option value="">All Agencies</option>
                <option value="none">No Agency Assigned</option>
                @foreach($agencies as $agency)
                    <option value="{{ $agency->id }}">{{ $agency->acronym ?: $agency->name }}</option>
                @endforeach
            </select>

            {{-- Sort By --}}
            <div class="flex gap-2">
                <select wire:model.live="sortBy" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)] focus:ring-2 focus:ring-[var(--color-accent)] transition">
                    <option value="role_hierarchy">Role Hierarchy</option>
                    <option value="name">Name</option>
                    <option value="email">Email</option>
                    <option value="created_at">Created Date</option>
                </select>

                <button wire:click="sortDirection = $sortDirection === 'asc' ? 'desc' : 'asc'"
                        class="h-10 w-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:bg-[var(--card-bg)] text-[var(--text)] flex items-center justify-center transition"
                        title="Sort direction: {{ $sortDirection === 'asc' ? 'Ascending' : 'Descending' }}">
                    @if($sortDirection === 'asc')
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 4h13M3 8h9m-9 4h6m4 4v4m-3 0l4-4m0 0l4 4m0 0l4 4"/>
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 20h13M3 16h9m-9 4v-4m4 4l-4-4m0 0l-4-4"/>
                        </svg>
                    @endif
                </button>
            </div>
        </div>

        {{-- RBAC & Workflow Management Tabs --}}
        <div class="bg-[var(--card-bg)] rounded-xl border border-[var(--border)] shadow-sm">
            {{-- Tab Navigation --}}
            <div class="flex gap-1 px-1 pt-1 border-b border-[var(--border)] overflow-x-auto">
                <button wire:click="setActiveTab('users')"
                        class="px-4 py-2.5 font-semibold text-sm rounded-t-lg transition-all whitespace-nowrap {{ $activeTab === 'users' ? 'bg-[var(--bg)] text-[var(--color-accent)] border-t-2 border-[var(--color-accent)] -mb-px' : 'text-[var(--text-muted)] hover:text-[var(--text)] hover:bg-[var(--bg)]/50' }}">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Users
                    </span>
                </button>
                <button wire:click="setActiveTab('roles')"
                        class="px-4 py-2.5 font-semibold text-sm rounded-t-lg transition-all whitespace-nowrap {{ $activeTab === 'roles' ? 'bg-[var(--bg)] text-[var(--color-accent)] border-t-2 border-[var(--color-accent)] -mb-px' : 'text-[var(--text-muted)] hover:text-[var(--text)] hover:bg-[var(--bg)]/50' }}">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Roles & Permissions
                    </span>
                </button>
            </div>

            {{-- Tab Content --}}
            <div class="p-6">
                @switch($activeTab)
                    @case('users')
                        {{-- Original User Management Content (Table Section) --}}
                    @break

                    @case('roles')
                        {{-- Roles & Permissions Section --}}
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-[var(--text)]">Roles & Permissions</h3>
                                    <p class="text-sm text-[var(--text-muted)]">Manage what each role can do in the system</p>
                                </div>
                            </div>

                            {{-- Roles Grid --}}
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                                @foreach($roles as $roleItem)
                                    <div class="relative group">
                                        <div class="p-3 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:border-[var(--color-accent)] transition cursor-pointer"
                                             @if($selectedRole && $selectedRole->id === $roleItem->id) style="border-color: var(--color-accent); background: var(--color-accent)/10;" @endif
                                             wire:click="selectRole({{ $roleItem->id }})">
                                            <div class="font-semibold text-sm text-[var(--text)]">{{ $roleItem->display_name }}</div>
                                            <div class="text-xs text-[var(--text-muted)] mt-1">
                                                {{ $roleItem->permissions->count() }} permission{{ $roleItem->permissions->count() !== 1 ? 's' : '' }}
                                            </div>
                                            @if($roleItem->is_system)
                                                <span class="text-[9px] text-[var(--color-accent)] font-semibold">System Role</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Selected Role Permissions --}}
                            @if($selectedRole)
                                <div class="mt-4 pt-4 border-t border-[var(--border)]">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="font-bold text-[var(--text)]">Configure {{ $selectedRole->display_name }}</h4>
                                        <button wire:click="cancelRoleEdit" class="text-sm text-[var(--text-muted)] hover:text-[var(--text)]">Cancel</button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                                        @foreach($permissionCategories as $category => $permissions)
                                            <div class="space-y-2">
                                                <div class="text-xs font-bold uppercase tracking-wider text-[var(--text-muted)] sticky top-0 bg-[var(--bg)] py-1">{{ $category }}</div>
                                                @foreach($permissions as $permission)
                                                    <label class="flex items-center gap-2 text-sm text-[var(--text)] cursor-pointer hover:bg-[var(--bg)] p-1.5 rounded">
                                                        <input type="checkbox"
                                                               wire:model.live="selectedPermissions"
                                                               value="{{ $permission->id }}"
                                                               class="rounded border-[var(--border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                        <span class="flex-1">{{ $permission->description }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-4 flex justify-end gap-3">
                                        <button wire:click="cancelRoleEdit" class="px-4 py-2 rounded-lg border border-[var(--border)] text-[var(--text)] font-semibold text-sm hover:bg-[var(--bg)] transition">Cancel</button>
                                        <button wire:click="saveRolePermissions" class="px-4 py-2 rounded-lg bg-[var(--color-accent)] text-[var(--color-accent-foreground)] font-semibold text-sm hover:opacity-90 transition">Save Permissions</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @break
                @endswitch
            </div>
        </div>

        {{-- Table Section - Show only when Users tab is active --}}
        @if($activeTab === 'users')
        <div class="rounded-xl border border-[var(--border)] bg-[var(--card-bg)] shadow-sm overflow-hidden transition-all">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm leading-normal">
                    <thead class="bg-[var(--bg)] border-b border-[var(--border)]">
                        <tr class="text-left text-[var(--text-muted)] uppercase text-[11px] font-bold tracking-wider">
                            <th class="px-5 py-4">User Details</th>
                            <th class="px-5 py-4">Account Info</th>
                            <th class="px-5 py-4">Role</th>
                            <th class="px-5 py-4">Office / Agency</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @forelse($users as $user)
                            <tr class="hover:bg-[var(--bg)]/40 transition-colors group">
                                <td class="px-5 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-[var(--text)] text-base">{{ $user->name }}</span>
                                        <span class="text-xs text-[var(--text-muted)]">{{ $user->email }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-[var(--text-muted)] font-medium">
                                    {{ $user->username ?? 'No Username' }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide
                                        @if($user->role === \App\Models\User::ROLE_SUPER_ADMIN) bg-purple-100 text-purple-700
                                        @elseif($user->role === \App\Models\User::ROLE_ADMIN) bg-pink-100 text-pink-700
                                        @elseif($user->role === \App\Models\User::ROLE_EXECOM) bg-indigo-100 text-indigo-700
                                        @elseif($user->role === \App\Models\User::ROLE_HO) bg-blue-100 text-blue-700
                                        @elseif($user->role === \App\Models\User::ROLE_RO) bg-cyan-100 text-cyan-700
                                        @elseif($user->role === \App\Models\User::ROLE_PSTO) bg-emerald-100 text-emerald-700
                                        @elseif($user->role === \App\Models\User::ROLE_AGENCY) bg-amber-100 text-amber-700
                                        @elseif($user->role === 'ousec_ro') bg-orange-100 text-orange-700
                                        @elseif($user->role === 'ousec_sts') bg-rose-100 text-rose-700
                                        @elseif($user->role === 'ousec_rd') bg-violet-100 text-violet-700
                                        @else bg-gray-100 text-gray-700
                                        @endif">
                                        {{ str_replace('_', ' ', $user->role === \App\Models\User::ROLE_HO ? 'Head of Office' : $user->role) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[var(--text)] font-medium">{{ $user->office?->name ?: ($user->agency?->name ?: '--') }}</span>
                                        @if($user->agency)
                                            <span class="text-[10px] text-[var(--text-muted)] uppercase tracking-tighter">{{ $user->agency->acronym }}</span>
                                        @endif
                                        @if($user->role === \App\Models\User::ROLE_HO && $user->agency)
                                            <span class="text-[9px] text-blue-600 font-semibold">(HO for Agency)</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button wire:click="openEdit({{ $user->id }})" class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-all" title="Edit User">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </button>
                                        @if($user->id !== auth()->id())
                                            <button wire:click="delete({{ $user->id }})" wire:confirm="Permanent action: Are you sure you want to delete this user account?" class="p-2 rounded-lg text-red-600 hover:bg-red-50 transition-all" title="Delete User">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-20 text-center text-[var(--text-muted)]">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-[var(--border)] bg-[var(--bg)]/30">
                {{ $users->links() }}
            </div>
        </div>
        @endif {{-- End Users Table Section --}}

        {{-- Modal --}}
        @if($showModal)
            <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                <div wire:click="closeModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>

                <div class="relative bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200">
                    <div class="px-6 py-4 border-b border-[var(--border)] flex items-center justify-between bg-[var(--bg)]/50">
                        <h3 class="text-lg font-bold text-[var(--text)]">{{ $isEditing ? 'Update User Account' : 'Create New User' }}</h3>
                        <button wire:click="closeModal" class="text-[var(--text-muted)] hover:text-[var(--text)] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="px-6 py-6 max-h-[75vh] overflow-y-auto custom-scrollbar">
                        <form wire:submit.prevent="save" class="space-y-5">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Full Name</label>
                                    <input wire:model="name" type="text" class="w-full mt-1 h-11 rounded-xl border-[var(--border)] bg-[var(--bg)] text-[var(--text)] px-4 focus:ring-2 focus:ring-[var(--color-accent)] transition">
                                    @error('name') <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Email</label>
                                        <input wire:model="email" type="email" class="w-full mt-1 h-11 rounded-xl border-[var(--border)] bg-[var(--bg)] text-[var(--text)] px-4 focus:ring-2 focus:ring-[var(--color-accent)] transition">
                                        @error('email') <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Username</label>
                                        <input wire:model="username" type="text" class="w-full mt-1 h-11 rounded-xl border-[var(--border)] bg-[var(--bg)] text-[var(--text)] px-4 focus:ring-2 focus:ring-[var(--color-accent)] transition">
                                        @error('username') <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)]">System Role</label>
                                    <select wire:model.live="role" class="w-full mt-1 h-11 rounded-xl border-[var(--border)] bg-[var(--bg)] text-[var(--text)] px-4 focus:ring-2 focus:ring-[var(--color-accent)] transition">
                                        <option value="psto">PSTO (Provincial User)</option>
                                        <option value="ro">RO (Regional User)</option>
                                        <option value="head_officer">Head of Office</option>
                                        <option value="agency">Agency User</option>
                                        <option value="administrator">Administrator</option>
                                        <option value="super_admin">Super Admin</option>
                                        <option value="execom">EXECOM</option>
                                        <option value="ousec_ro">OUSEC-RO (Regional Operations)</option>
                                        <option value="ousec_sts">OUSEC-STS (S&T Services)</option>
                                        <option value="ousec_rd">OUSEC-RD (R&D)</option>
                                    </select>
                                    @error('role') <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p> @enderror
                                </div>

                                {{-- OUSEC Type Selector --}}
                                @if(in_array($role, ['ousec_ro', 'ousec_sts', 'ousec_rd']))
                                    <div class="animate-in slide-in-from-top duration-200">
                                        <div class="p-4 rounded-xl border-2 border-orange-200 bg-orange-50 dark:bg-orange-900/20">
                                            <div class="flex items-center gap-2 mb-2">
                                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h6a2 2 0 012 2v14a2 2 0 01-2 2H9a2 2 0 01-2-2V5a2 2 0 012-2h6z"></path>
                                                </svg>
                                                <span class="text-sm font-semibold text-orange-800">OUSEC Role Information</span>
                                            </div>
                                            <p class="text-xs text-orange-700 dark:text-orange-300">
                                                @if($role === 'ousec_ro') Handles regional/PSTO indicators
                                                @elseif($role === 'ousec_sts') Handles SSI and Collegial agencies
                                                @elseif($role === 'ousec_rd') Handles Councils and RDIs
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                {{-- HO ASSIGNMENT TYPE SELECTOR --}}
                                @if($role === 'head_officer')
                                    <div class="animate-in slide-in-from-top duration-200">
                                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)]">HO Assignment Type</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" wire:model.live="hoAssignmentType" value="office" class="rounded border-[var(--border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                <span class="text-sm text-[var(--text)]">Assign to Region</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" wire:model.live="hoAssignmentType" value="agency" class="rounded border-[var(--border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                                <span class="text-sm text-[var(--text)]">Assign to Agency</span>
                                            </label>
                                        </div>
                                    </div>
                                @endif

                                {{-- CONDITIONALLY SHOW AGENCY SELECT --}}
                                @if($role === 'agency' || ($role === 'head_officer' && $hoAssignmentType === 'agency'))
                                    <div class="animate-in slide-in-from-top duration-200">
                                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Select Attached Agency</label>
                                        <select wire:model="agency_id" class="w-full mt-1 h-11 rounded-xl border-[var(--border)] bg-[var(--bg)] text-[var(--text)] px-4 focus:ring-2 focus:ring-[var(--color-accent)] transition">
                                            <option value="">-- Choose Agency --</option>
                                            @foreach($agencies as $agency)
                                                <option value="{{ $agency->id }}">{{ $agency->name }} ({{ $agency->acronym }})</option>
                                            @endforeach
                                        </select>
                                        @error('agency_id') <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                {{-- CONDITIONALLY SHOW REGION/OFFICE SELECT --}}
                                @if(in_array($role, ['ro', 'psto']) || $role === 'head_officer')
                                    <div class="grid grid-cols-2 gap-4 animate-in slide-in-from-top duration-200">
                                        <div>
                                            <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Region</label>
                                            <select wire:model.live="region_id" class="w-full mt-1 h-11 rounded-xl border-[var(--border)] bg-[var(--bg)] text-[var(--text)] px-4 focus:ring-2 focus:ring-[var(--color-accent)] transition">
                                                <option value="">None / National</option>
                                                @foreach($regions as $region)
                                                    <option value="{{ $region->id }}">{{ $region->code }} - {{ $region->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if($role === 'psto')
                                            <div>
                                                <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Office</label>
                                                <select wire:model="office_id" class="w-full mt-1 h-11 rounded-xl border-[var(--border)] bg-[var(--bg)] text-[var(--text)] px-4 focus:ring-2 focus:ring-[var(--color-accent)] transition">
                                                    <option value="">-- Select Office --</option>
                                                    @foreach($filteredOffices as $office)
                                                        <option value="{{ $office->id }}">{{ $office->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Password {{ $isEditing ? '(Optional)' : '(Required)' }}</label>
                                        <input wire:model="password" type="password" placeholder="••••••••" class="w-full mt-1 h-11 rounded-xl border-[var(--border)] bg-[var(--bg)] text-[var(--text)] px-4 focus:ring-2 focus:ring-[var(--color-accent)] transition">
                                        @error('password') <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Confirm Password</label>
                                        <input wire:model="password_confirmation" type="password" placeholder="••••••••" class="w-full mt-1 h-11 rounded-xl border-[var(--border)] bg-[var(--bg)] text-[var(--text)] px-4 focus:ring-2 focus:ring-[var(--color-accent)] transition">
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 pt-4">
                                <button type="button" wire:click="closeModal" class="flex-1 px-4 py-3 rounded-xl border border-[var(--border)] bg-[var(--bg)] text-[var(--text)] font-bold text-sm hover:bg-[var(--card-bg)] transition active:scale-95">Cancel</button>
                                <button type="submit" class="flex-1 px-4 py-3 rounded-xl bg-[var(--color-accent)] text-[var(--color-accent-foreground)] font-bold text-sm hover:opacity-90 shadow-lg shadow-[var(--color-accent)]/20 transition active:scale-95">
                                    {{ $isEditing ? 'Save Changes' : 'Create Account' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>