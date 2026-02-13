<section class="w-full">
    <div class="relative mb-6 w-full page-narrow-sm">
        <h1 class="text-2xl font-extrabold text-[var(--text)]">Approvals</h1>
        <p class="text-[var(--text-muted)]">Manage the approval chain for submitted indicators</p>
        <flux:separator variant="subtle" />
    </div>

    @if (session()->has('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 border border-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100 border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div x-data="{ q: localStorage.getItem('approvals_q') ?? '', init(){ this.$watch('q', v => localStorage.setItem('approvals_q', v)) } }" class="mb-3 flex items-center justify-between gap-2 page-narrow-sm text-sm leading-snug">
        <div class="flex items-center gap-2">
            <input type="text" x-model="q" placeholder="Search objective, indicator..." class="w-64 max-w-[70vw] rounded border-[var(--border)] bg-[var(--card-bg)] px-3 py-1.5 text-sm focus:ring-1 focus:ring-blue-500" />
            <span class="text-xs text-[var(--text-muted)]">
                Pending: {{ $pending instanceof \Illuminate\Support\Collection ? $pending->count() : count($pending) }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="reload" class="px-3 py-1.5 rounded border border-[var(--border)] bg-[var(--card-bg)] text-sm hover:bg-[var(--bg)] transition">
                Refresh
            </button>
        </div>
    </div>

    <div class="border rounded shadow-sm overflow-hidden page-narrow-sm relative bg-[var(--card-bg)]" wire:poll.15s="reload">
        <div wire:loading class="absolute inset-0 bg-white/50 dark:bg-black/20 backdrop-blur-[1px] grid place-items-center z-10">
            <svg class="animate-spin h-6 w-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
        </div>
        
        <table class="w-full text-xs table-compact table-sticky">
            <thead>
                <tr class="bg-[var(--bg)] border-b text-[var(--text-muted)] uppercase tracking-wider">
                    <th class="text-left p-3 font-semibold">ID</th>
                    <th class="text-left p-3 font-semibold">Indicator / Objective</th>
                    <th class="text-left p-3 font-semibold">Agency/Office</th>
                    <th class="text-left p-3 font-semibold">Submitted By</th>
                    <th class="text-left p-3 font-semibold">Current Stage</th>
                    <th class="text-right p-3 font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)]">
                @forelse($pending as $row)
                    @php
                        $u = $row->submitter;
                        // Search blob creation
                        $__blob = strtolower(trim(($row->objective_result.' '.$row->indicator.' '.$row->dost_agency.' '.($u?->name ?? '').' '.($u?->email ?? ''))));
                    @endphp
                    <tr class="hover:bg-[var(--bg)]/50 transition-colors" wire:key="row-{{ $row->id }}" x-data x-show="q === '' || {{ json_encode($__blob) }}.includes(q.toLowerCase())">
                        <td class="p-3 font-mono text-[var(--text-muted)]">{{ $row->id }}</td>
                        <td class="p-3">
                            <div class="font-medium text-[var(--text)] truncate max-w-[30ch]" title="{{ $row->indicator }}">{{ $row->indicator }}</div>
                            <div class="text-[var(--text-muted)] text-[10px] truncate max-w-[30ch]">{{ $row->objective_result }}</div>
                        </td>
                        <td class="p-3">
                            <div>{{ $row->responsible_agency ?? $row->dost_agency }}</div>
                            <div class="text-[10px] text-[var(--text-muted)]">{{ $row->office->name ?? '' }}</div>
                        </td>
                        <td class="p-3">
                            <div>{{ $u?->name ?? '—' }}</div>
                        </td>
                        <td class="p-3">
                            @php
                                $statusColors = [
                                    'DRAFT' => 'bg-gray-100 text-gray-700',
                                    'submitted_to_ro' => 'bg-blue-100 text-blue-700',
                                    'submitted_to_ho' => 'bg-purple-100 text-purple-700',
                                    'submitted_to_ousec' => 'bg-purple-100 text-purple-700 dark:bg-purple-800 dark:text-purple-100',
                                    'submitted_to_admin' => 'bg-orange-100 text-orange-700',
                                    'returned_to_ro' => 'bg-red-50 text-red-600',
                                    'returned_to_psto' => 'bg-red-50 text-red-600',
                                    'returned_to_ousec' => 'bg-orange-100 text-orange-700 dark:bg-orange-800 dark:text-orange-100',
                                ];
                                $color = $statusColors[$row->status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide {{ $color }}">
                                {{ str_replace('_', ' ', $row->status) }}
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            <flux:button variant="outline" size="sm" class="px-3 py-1" wire:click="view({{ $row->id }})">Review</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-[var(--text-muted)]">No pending items for your approval level.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MODAL VIEWER --}}
    @if($viewingId && $viewing)
        <div class="fixed inset-0 z-[9999]" x-data="{ show: true }" x-show="show" x-cloak>
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" x-transition.opacity @click="show=false; $wire.closeView();"></div>
            
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-xl shadow-2xl border border-[var(--border)] bg-[var(--card-bg)] flex flex-col" x-transition.scale>
                    
                    <div class="flex items-center justify-between p-5 border-b border-[var(--border)] bg-[var(--bg)]">
                        <div>
                            <h3 class="text-xl font-bold text-[var(--text)]">Review Indicator #{{ $viewing->id }}</h3>
                            <div class="text-xs text-[var(--text-muted)] mt-1">Submitted by {{ optional($viewing->submitter)->name }} • {{ $viewing->created_at->format('M d, Y') }}</div>
                        </div>
                        <button class="text-[var(--text-muted)] hover:text-[var(--text)] transition" @click="show=false; $wire.closeView();">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        
                        <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-[var(--border)]">
                            <span class="text-xs uppercase font-bold text-[var(--text-muted)]">Current Status</span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                {{ strtoupper(str_replace('_', ' ', $viewing->status)) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Category</label>
                                    <div class="font-medium text-[var(--text)]">{{ $viewing->category }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Indicator Description</label>
                                    <div class="p-3 bg-[var(--bg)] rounded border border-[var(--border)] text-[var(--text)]">
                                        {{ $viewing->indicator }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Objective / Outcome</label>
                                    <div class="font-medium text-[var(--text)]">{{ $viewing->objective_result }}</div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Target Period</label>
                                        <div class="font-medium">{{ $viewing->target_period }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Target Value</label>
                                        <div class="font-mono font-bold text-blue-600">{{ $viewing->target_value }}</div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Baseline</label>
                                    <div class="font-mono">{{ $viewing->baseline }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Latest Actual</label>
                                    <div class="font-mono font-bold text-green-600">
                                        {{ collect($viewing->accomplishments_series)->last()['value'] ?? 'No data' }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Means of Verification</label>
                                    <div class="text-[var(--text)] italic">{{ $viewing->mov }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-4 pt-4 border-t border-[var(--border)]">
                            <div>
                                <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Assumptions & Risks</label>
                                <div class="text-sm text-[var(--text)]">{{ $viewing->assumptions_risk ?? 'None' }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[var(--text-muted)] uppercase mb-1">Submitter Remarks</label>
                                <div class="text-sm text-[var(--text)]">{{ $viewing->remarks ?? 'None' }}</div>
                            </div>
                        </div>

                        @if($rejectingId === $viewingId)
                            <div class="mt-6 p-5 rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 animate-in fade-in slide-in-from-bottom-2">
                                <h4 class="text-red-800 dark:text-red-200 font-bold mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Return to Sender
                                </h4>
                                
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-red-700 dark:text-red-300 uppercase mb-1">
                                            Fields requiring correction (Comma separated)
                                        </label>
                                        <input type="text" wire:model="reject_fields" placeholder="e.g. Baseline, Target Value, MOV" 
                                               class="w-full rounded border-red-300 bg-white focus:border-red-500 focus:ring-red-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-red-700 dark:text-red-300 uppercase mb-1">
                                            Reason / Instructions
                                        </label>
                                        <textarea rows="3" wire:model="reject_notes" placeholder="Please explain what needs to be fixed..." 
                                                  class="w-full rounded border-red-300 bg-white focus:border-red-500 focus:ring-red-500 text-sm"></textarea>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 mt-4">
                                    <button wire:click="$set('rejectingId', null)" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 transition">
                                        Cancel
                                    </button>
                                    <button wire:click="reject" class="px-4 py-2 text-sm font-bold text-white bg-red-600 rounded hover:bg-red-700 shadow-sm transition">
                                        Confirm Return
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if(!$rejectingId)
                        <div class="p-5 border-t border-[var(--border)] bg-[var(--bg)] flex justify-end gap-3">
                            <button wire:click="startReject({{ $viewingId }})" class="px-5 py-2.5 text-sm font-medium text-red-600 border border-red-200 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                Return with Notes
                            </button>
                            <button wire:click="approve({{ $viewingId }})" class="px-5 py-2.5 text-sm font-bold text-white bg-green-600 rounded hover:bg-green-700 shadow-md transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Approve & Forward
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</section>