<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-[var(--text)]">Agency Management</h2>
        <button
            wire:click="openCreate"
            class="px-4 py-2 rounded-lg bg-[var(--color-accent)] text-[var(--color-accent-foreground)] font-semibold hover:opacity-90 transition"
        >
            + Add Agency
        </button>
    </div>

    {{-- Search --}}
    <div class="flex gap-3">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search agencies..."
            class="flex-1 h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)]"
        >
    </div>

    {{-- Table --}}
    <div class="rounded-xl border border-[var(--border)] bg-[var(--card-bg)] overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-[var(--bg)] border-b border-[var(--border)]">
                <tr class="text-left text-[var(--text-muted)]">
                    <th class="px-4 py-3 font-semibold">Agency ID</th>
                    <th class="px-4 py-3 font-semibold">Code</th>
                    <th class="px-4 py-3 font-semibold">Acronym</th>
                    <th class="px-4 py-3 font-semibold">Name</th>
                    <th class="px-4 py-3 font-semibold">Clusters</th>
                    <th class="px-4 py-3 font-semibold">Head Office</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)]">
                @forelse($agencies as $agency)
                    <tr class="hover:bg-[var(--bg)]/50 transition">
                        <td class="px-4 py-3 font-semibold text-[var(--text)]">{{ $agency->agency_id ?? '—' }}</td>
                        <td class="px-4 py-3 text-[var(--text-muted)]">{{ $agency->code ?? '—' }}</td>
                        <td class="px-4 py-3 text-[var(--text-muted)]">{{ $agency->acronym ?? '—' }}</td>
                        <td class="px-4 py-3 text-[var(--text)]">{{ $agency->name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                {{ strtoupper($agency->cluster ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($agency->head_user_id)
                                @php
                                    $ho = \App\Models\User::find($agency->head_user_id);
                                @endphp
                                @if($ho)
                                    <span class="px-2 py-1 text-xs rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                        {{ $ho->name }}
                                    </span>
                                @else
                                    <span class="text-[var(--text-muted)]">—</span>
                                @endif
                            @else
                                <span class="text-[var(--text-muted)] text-xs">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($agency->is_active)
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    Active
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <button
                                    wire:click="openEdit({{ $agency->id }})"
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-semibold"
                                >
                                    Edit
                                </button>
                                <button
                                    wire:click="delete({{ $agency->id }})"
                                    wire:confirm="Are you sure you want to delete this agency?"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-semibold"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-[var(--text-muted)]">
                            No agencies found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-[var(--border)] bg-[var(--bg)]/50">
            {{ $agencies->links() }}
        </div>
    </div>

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showModal') }">
            <div x-show="show" x-transition.opacity class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div x-show="show" x-transition class="fixed inset-0 flex items-center justify-center p-4">
                <div class="relative bg-white dark:bg-neutral-800 rounded-xl shadow-2xl w-full max-w-2xl" @click.outside="$wire.closeModal()">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-[var(--text)] mb-4">
                            {{ $isEditing ? 'Edit Agency' : 'Add Agency' }}
                        </h3>

                        <form wire:submit.prevent="save" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--text)] mb-1">Agency ID</label>
                                    <input
                                        wire:model="agency_id"
                                        type="text"
                                        placeholder="Reference (optional)"
                                        class="w-full h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)]"
                                    >
                                    @error('agency_id') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-[var(--text)] mb-1">Code *</label>
                                    <input
                                        wire:model="code"
                                        type="text"
                                        placeholder="e.g., ASTI, PCAARRD"
                                        class="w-full h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)]"
                                        required
                                    >
                                    @error('code') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-[var(--text)] mb-1">Acronym</label>
                                    <input
                                        wire:model="acronym"
                                        type="text"
                                        placeholder="e.g., ASTI"
                                        class="w-full h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)]"
                                    >
                                    @error('acronym') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-[var(--text)] mb-1">Cluster *</label>
                                    <select
                                        wire:model="cluster"
                                        class="w-full h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)]"
                                        required
                                    >
                                        <option value="main">Main</option>
                                        <option value="council">Council</option>
                                        <option value="rdi">R&D Institute</option>
                                        <option value="ssi">S&T Service Institute</option>
                                        <option value="collegial">Collegial/Advisory</option>
                                    </select>
                                    @error('cluster') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-[var(--text)] mb-1">Head Office</label>
                                    <select
                                        wire:model="head_user_id"
                                        class="w-full h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)]"
                                    >
                                        <option value="">-- No Head Office --</option>
                                        @foreach($hoUsers as $ho)
                                            <option value="{{ $ho->id }}">{{ $ho->name }} ({{ $ho->role }})</option>
                                        @endforeach
                                    </select>
                                    @error('head_user_id') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-2">
                                    <label class="block text-sm font-semibold text-[var(--text)] mb-1">Name *</label>
                                    <input
                                        wire:model="name"
                                        type="text"
                                        placeholder="Agency full name"
                                        class="w-full h-10 rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 text-sm text-[var(--text)]"
                                        required
                                    >
                                    @error('name') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div class="flex items-center">
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            wire:model="is_active"
                                            type="checkbox"
                                            class="rounded border-[var(--border)] text-[var(--color-accent)] focus:ring-[var(--color-accent)]"
                                        >
                                        <span class="ml-2 text-sm font-semibold text-[var(--text)]">Active</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-[var(--text)] mb-1">Description</label>
                                <textarea
                                    wire:model="description"
                                    rows="3"
                                    placeholder="Brief description"
                                    class="w-full rounded-lg border border-[var(--border)] bg-[var(--bg)] px-3 py-2 text-sm text-[var(--text)]"
                                ></textarea>
                                @error('description') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button
                                    type="button"
                                    wire:click="closeModal"
                                    class="flex-1 px-4 py-2 rounded-lg border border-[var(--border)] bg-[var(--bg)] text-[var(--text)] font-semibold hover:bg-[var(--card-bg)] transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="flex-1 px-4 py-2 rounded-lg bg-[var(--color-accent)] text-[var(--color-accent-foreground)] font-semibold hover:opacity-90 transition"
                                >
                                    {{ $isEditing ? 'Update' : 'Create' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
