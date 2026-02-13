<div class="p-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-[var(--text)]">OUSEC Dashboard</h1>
            <p class="text-sm text-[var(--text-muted)] mt-1">{{ $this->getOUSECTypeLabel() }}</p>
        </div>
        @if(!$this->isOUSEROR())
            <div class="flex flex-wrap gap-2">
                @foreach($this->getAssignedClusters() as $cluster)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide {{ $this->getClusterBadgeClasses($cluster) }}">
                        {{ $cluster }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4 p-4 rounded-xl border border-[var(--border)] bg-[var(--card-bg)]">
        {{-- Status Filter --}}
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide mb-2">Status</label>
            <select wire:model.live="filterStatus" class="w-full px-4 py-2 rounded-lg border border-[var(--border)] bg-[var(--bg)] text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]">
                <option value="all">All Statuses</option>
                <option value="submitted_to_ousec">Submitted to OUSEC</option>
                <option value="returned_to_ousec">Returned to OUSEC</option>
            </select>
        </div>

        {{-- Cluster Filter (STS/RD only) --}}
        @if(!$this->isOUSEROR())
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide mb-2">Cluster</label>
                <select wire:model.live="filterCluster" class="w-full px-4 py-2 rounded-lg border border-[var(--border)] bg-[var(--bg)] text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]">
                    <option value="all">All Clusters</option>
                    @foreach(['ssi' => 'SSI', 'collegial' => 'Collegial', 'council' => 'Council', 'rdi' => 'RDI'] as $value => $label)
                        @if(in_array($value, $this->getAssignedClusters()))
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Refresh Button --}}
        <div class="flex items-end">
            <button wire:click="reload" class="px-6 py-2 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:bg-[var(--border)] text-[var(--text)] font-semibold transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- Pending Indicators --}}
    @if($pending->count() > 0)
        <div class="space-y-4">
            @foreach($pending as $indicator)
                <div class="rounded-xl border border-[var(--border)] bg-[var(--card-bg)] overflow-hidden
                    {{ $indicator->status === \App\Models\Indicator::STATUS_RETURNED_TO_OUSEC ? 'border-l-4 border-l-orange-500' : '' }}">
                    {{-- Main Row --}}
                    <div class="p-5">
                        <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                            {{-- Left: Indicator Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-3">
                                    {{-- Status Badge --}}
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide flex-shrink-0
                                        @if($indicator->status === \App\Models\Indicator::STATUS_SUBMITTED_TO_OUSEC) bg-blue-100 text-blue-700
                                        @elseif($indicator->status === \App\Models\Indicator::STATUS_RETURNED_TO_OUSEC) bg-orange-100 text-orange-700
                                        @else bg-gray-100 text-gray-700
                                        @endif">
                                        {{ str_replace('_', ' ', $indicator->status) }}
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        {{-- Title & Meta --}}
                                        <h3 class="text-lg font-bold text-[var(--text)] line-clamp-2">{{ $indicator->indicator }}</h3>

                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                            {{-- Agency Cluster Badge --}}
                                            @if($indicator->submitter && $indicator->submitter->agency && $indicator->submitter->agency->cluster)
                                                @php
                                                    $cluster = $indicator->submitter->agency->cluster;
                                                    $acronym = $indicator->submitter->agency->acronym;
                                                @endphp
                                                <span class="text-[var(--text-muted)]">
                                                    <span class="font-semibold">{{ $acronym }}</span>
                                                </span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase {{ $this->getClusterBadgeClasses($cluster) }}">
                                                    {{ $cluster }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Timestamps --}}
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-[var(--text-muted)]">
                                            <span>Submitted: {{ $indicator->submitted_to_ousec_at?->diffForHumans() ?? '--' }}</span>
                                            @if($indicator->returned_to_ousec_at)
                                                <span class="text-orange-600 dark:text-orange-400">Returned: {{ $indicator->returned_to_ousec_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Right: Actions --}}
                            <div class="flex items-center gap-2 lg:ml-4">
                                {{-- View Button --}}
                                <button wire:click="view({{ $indicator->id }})" class="px-4 py-2 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:bg-[var(--border)] text-[var(--text)] font-semibold transition flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View
                                </button>
                                {{-- Approve Button - Always Visible --}}
                                <button wire:click="approve({{ $indicator->id }})" wire:confirm="Approve this indicator and forward to Administrator?" class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold transition flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                    Approve
                                </button>
                                {{-- Reject Button - Always Visible --}}
                                <button wire:click="startReject({{ $indicator->id }})" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold transition flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 6L6 18M6 6l12 12"/>
                                    </svg>
                                    Return
                                </button>
                            </div>
                        </div>

                        {{-- Expanded View Details --}}
                        @if($viewingId === $indicator->id && $viewing)
                            <div class="mt-6 pt-6 border-t border-[var(--border)]">
                                <h4 class="text-sm font-bold text-[var(--text-muted)] uppercase tracking-wide mb-4">Indicator Details</h4>

                                <div class="grid md:grid-cols-2 gap-6">
                                    {{-- Left Column --}}
                                    <div class="space-y-4">
                                        <div>
                                            <label class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide">Strategic Plan</label>
                                            <p class="text-[var(--text)] mt-1">{{ $viewing->strategicPlan?->name ?? '--' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide">Pillar / Outcome</label>
                                            <p class="text-[var(--text)] mt-1">
                                                {{ $viewing->pillar?->name ?? '--' }}
                                                @if($viewing->outcome) <span class="text-[var(--text-muted)]"> / {{ $viewing->outcome->name }}</span> @endif
                                            </p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide">Category</label>
                                            <p class="text-[var(--text)] mt-1">{{ $viewing->category?->name ?? '--' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide">Description</label>
                                            <p class="text-[var(--text)] mt-1">{{ $viewing->description ?? 'No description provided' }}</p>
                                        </div>
                                    </div>

                                    {{-- Right Column --}}
                                    <div class="space-y-4">
                                        <div>
                                            <label class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide">Baseline Value</label>
                                            <p class="text-[var(--text)] mt-1">{{ $viewing->baseline_value ?? '--' }} {{ $viewing->unit_of_measure ?? '' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide">Target Value</label>
                                            <p class="text-[var(--text)] mt-1">{{ $viewing->target_value ?? '--' }} {{ $viewing->unit_of_measure ?? '' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide">Actual Value</label>
                                            <p class="text-[var(--text)] mt-1">{{ $viewing->actual_value ?? '--' }} {{ $viewing->unit_of_measure ?? '' }}</p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wide">Timeline</label>
                                            <p class="text-[var(--text)] mt-1">
                                                {{ $viewing->start_date?->format('M Y') ?? '--' }} - {{ $viewing->end_date?->format('M Y') ?? '--' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Rejection Notes (if any) --}}
                                @if($viewing->myRejectionNote)
                                    <div class="mt-6 p-4 rounded-lg bg-orange-50 border border-orange-200">
                                        <div class="flex items-center gap-2 text-orange-800 font-semibold mb-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            Previous Rejection Note
                                        </div>
                                        <p class="text-orange-900">{{ $viewing->myRejectionNote->notes }}</p>
                                        <p class="text-xs text-orange-700 mt-2">Returned on {{ $viewing->myRejectionNote->created_at->format('M j, Y g:i A') }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Reject Form --}}
                        @if($rejectingId === $indicator->id)
                            <div class="mt-6 pt-6 border-t border-[var(--border)]">
                                <h4 class="text-sm font-bold text-red-600 uppercase tracking-wide mb-4">Provide Rejection Remarks</h4>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-[var(--text)] mb-2">Reason for returning to Head Office</label>
                                        <textarea wire:model="reject_notes" rows="4" class="w-full px-4 py-3 rounded-lg border border-[var(--border)] bg-[var(--bg)] text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Please explain why this indicator is being returned..."></textarea>
                                        @error('reject_notes')
                                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button wire:click="reject" class="px-6 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold transition">
                                            Submit & Return to HO
                                        </button>
                                        <button wire:click="closeView" class="px-6 py-2 rounded-lg border border-[var(--border)] bg-[var(--bg)] hover:bg-[var(--border)] text-[var(--text)] font-semibold transition">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-20">
            <div class="mb-4 text-[var(--color-accent)]"><x-icon name="clipboard-list" class="w-16 h-16 mx-auto" /></div>
            <h3 class="text-xl font-bold text-[var(--text)] mb-2">No Pending Indicators</h3>
            <p class="text-[var(--text-muted)]">There are no indicators currently awaiting OUSEC review.</p>
        </div>
    @endif
</div>
