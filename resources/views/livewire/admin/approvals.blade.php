<section class="w-full">
    {{-- Header --}}
    <div class="relative mb-6 w-full max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900">Approvals</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage the approval chain for submitted indicators</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $pending instanceof \Illuminate\Support\Collection ? $pending->count() : count($pending) }} Pending
                </span>
                <button wire:click="reload" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition shadow-sm">
                    <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="reload" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    Refresh
                </button>
            </div>
        </div>
        <div class="mt-3 border-b border-gray-200"></div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-4 max-w-7xl mx-auto px-4">
            <div class="p-3 rounded-lg bg-green-50 text-green-700 border border-green-200 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 max-w-7xl mx-auto px-4">
            <div class="p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- Search --}}
    <div x-data="{ q: localStorage.getItem('approvals_q') ?? '', init(){ this.$watch('q', v => localStorage.setItem('approvals_q', v)) } }" class="mb-4 max-w-7xl mx-auto px-4">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input type="text" x-model="q" placeholder="Search by indicator, objective, agency, submitter..."
                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 bg-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition" />
        </div>
    </div>

    {{-- Table --}}
    <div class="max-w-7xl mx-auto px-4" wire:poll.15s="reload">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden relative">
            {{-- Loading Overlay --}}
            <div wire:loading class="absolute inset-0 bg-white/60 backdrop-blur-[1px] grid place-items-center z-10">
                <svg class="animate-spin h-6 w-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Indicator / Objective</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Agency / Office</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Submitted By</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Current Stage</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pending as $row)
                        @php
                            $u = $row->submitter;
                            $__blob = strtolower(trim(($row->objective_result.' '.$row->indicator.' '.$row->dost_agency.' '.($u?->name ?? '').' '.($u?->email ?? ''))));

                            $statusColors = [
                                'draft' => 'bg-gray-100 text-gray-700',
                                'submitted_to_ro' => 'bg-blue-100 text-blue-700',
                                'submitted_to_ho' => 'bg-indigo-100 text-indigo-700',
                                'submitted_to_ousec' => 'bg-purple-100 text-purple-700',
                                'submitted_to_admin' => 'bg-orange-100 text-orange-700',
                                'submitted_to_superadmin' => 'bg-amber-100 text-amber-700',
                                'approved' => 'bg-green-100 text-green-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                'returned_to_psto' => 'bg-red-50 text-red-600',
                                'returned_to_agency' => 'bg-red-50 text-red-600',
                                'returned_to_ro' => 'bg-red-50 text-red-600',
                                'returned_to_ho' => 'bg-orange-50 text-orange-600',
                                'returned_to_ousec' => 'bg-orange-50 text-orange-600',
                                'returned_to_admin' => 'bg-orange-50 text-orange-600',
                                'reopened' => 'bg-cyan-100 text-cyan-700',
                            ];
                            $color = $statusColors[$row->status] ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                            wire:key="row-{{ $row->id }}"
                            x-data
                            x-show="q === '' || {{ json_encode($__blob) }}.includes(q.toLowerCase())"
                            wire:click="view({{ $row->id }})">
                            <td class="px-4 py-3 font-mono text-xs text-gray-400">{{ $row->id }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900 truncate max-w-[30ch]" title="{{ $row->indicator }}">{{ $row->indicator }}</div>
                                <div class="text-xs text-gray-400 truncate max-w-[30ch] mt-0.5">{{ $row->objective_result }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-700">{{ $row->responsible_agency ?? $row->dost_agency }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $row->office->name ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-gray-700">{{ $u?->name ?? '—' }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $row->updated_at?->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide {{ $color }}">
                                    {{ str_replace('_', ' ', $row->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center" @click.stop>
                                <button wire:click="view({{ $row->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    Review
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <p class="text-gray-500 font-medium">No pending items for your approval level.</p>
                                    <p class="text-xs text-gray-400">All caught up! New submissions will appear here automatically.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- REVIEW MODAL --}}
    @if($viewingId && $viewing)
        <div class="fixed inset-0 z-[9999]" x-data="{ show: true }" x-show="show" x-cloak>
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" x-transition.opacity @click="show=false; $wire.closeView();"></div>

            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="relative w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-xl shadow-2xl border border-gray-200 bg-white flex flex-col" x-transition.scale>

                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between p-5 border-b border-gray-200 bg-gray-50">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Review Indicator #{{ $viewing->id }}</h3>
                            <div class="text-xs text-gray-500 mt-1">
                                Submitted by <span class="font-semibold">{{ optional($viewing->submitter)->name }}</span> &bull; {{ $viewing->created_at->format('M d, Y') }}
                            </div>
                        </div>
                        <button class="p-1.5 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-200 transition" @click="show=false; $wire.closeView();">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">

                        {{-- Status Badge --}}
                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-xs uppercase font-bold text-gray-500 tracking-wide">Current Status</span>
                            @php
                                $viewStatusColors = [
                                    'draft' => 'bg-gray-100 text-gray-700',
                                    'submitted_to_ro' => 'bg-blue-100 text-blue-700',
                                    'submitted_to_ho' => 'bg-indigo-100 text-indigo-700',
                                    'submitted_to_ousec' => 'bg-purple-100 text-purple-700',
                                    'submitted_to_admin' => 'bg-orange-100 text-orange-700',
                                    'submitted_to_superadmin' => 'bg-amber-100 text-amber-700',
                                    'approved' => 'bg-green-100 text-green-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    'returned_to_psto' => 'bg-red-50 text-red-600',
                                    'returned_to_agency' => 'bg-red-50 text-red-600',
                                    'returned_to_ro' => 'bg-red-50 text-red-600',
                                    'returned_to_ho' => 'bg-orange-50 text-orange-600',
                                    'returned_to_ousec' => 'bg-orange-50 text-orange-600',
                                    'returned_to_admin' => 'bg-orange-50 text-orange-600',
                                    'reopened' => 'bg-cyan-100 text-cyan-700',
                                ];
                                $viewColor = $viewStatusColors[$viewing->status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase {{ $viewColor }}">
                                {{ str_replace('_', ' ', $viewing->status) }}
                            </span>
                        </div>

                        {{-- Details Grid --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Category</label>
                                    <div class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $viewing->category)) }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Indicator Description</label>
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-800 leading-relaxed">
                                        {{ $viewing->indicator }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Objective / Outcome</label>
                                    <div class="font-medium text-gray-900">{{ $viewing->objective_result }}</div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Target Period</label>
                                        <div class="font-medium text-gray-900">{{ $viewing->target_period }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Target Value</label>
                                        <div class="font-mono font-bold text-blue-600 text-lg">{{ $viewing->target_value }}</div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Baseline</label>
                                    <div class="font-mono text-gray-800">{{ $viewing->baseline }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Latest Actual</label>
                                    <div class="font-mono font-bold text-green-600 text-lg">
                                        {{ collect($viewing->accomplishments_series)->last()['value'] ?? 'No data' }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Means of Verification</label>
                                    <div class="text-gray-600 italic">{{ $viewing->mov }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Additional Info --}}
                        <div class="grid grid-cols-1 gap-4 pt-4 border-t border-gray-200">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Assumptions & Risks</label>
                                <div class="text-sm text-gray-700">{{ $viewing->assumptions_risk ?? 'None' }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Submitter Remarks</label>
                                <div class="text-sm text-gray-700">{{ $viewing->remarks ?? 'None' }}</div>
                            </div>
                        </div>

                        {{-- Rejection Form --}}
                        @if($rejectingId === $viewingId)
                            <div class="mt-6 p-5 rounded-xl border border-red-200 bg-red-50 animate-in fade-in slide-in-from-bottom-2">
                                <h4 class="text-red-800 font-bold mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    Return to Sender
                                </h4>

                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-red-700 uppercase mb-1">
                                            Fields requiring correction (Comma separated)
                                        </label>
                                        <input type="text" wire:model="reject_fields" placeholder="e.g. Baseline, Target Value, MOV"
                                               class="w-full rounded-lg border-red-300 bg-white focus:border-red-500 focus:ring-red-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-red-700 uppercase mb-1">
                                            Reason / Instructions
                                        </label>
                                        <textarea rows="3" wire:model="reject_notes" placeholder="Please explain what needs to be fixed..."
                                                  class="w-full rounded-lg border-red-300 bg-white focus:border-red-500 focus:ring-red-500 text-sm"></textarea>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 mt-4">
                                    <button wire:click="$set('rejectingId', null)" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                        Cancel
                                    </button>
                                    <button wire:click="reject" class="px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm transition">
                                        Confirm Return
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer Actions --}}
                    @if(!$rejectingId)
                        <div class="p-5 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                            <button wire:click="startReject({{ $viewingId }})"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                                Return with Notes
                            </button>
                            <button wire:click="approve({{ $viewingId }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-green-600 rounded-lg hover:bg-green-700 shadow-md transition disabled:opacity-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                <span wire:loading.remove wire:target="approve">Approve & Forward</span>
                                <span wire:loading wire:target="approve">Processing...</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</section>