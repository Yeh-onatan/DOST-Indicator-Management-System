<section class="w-full">
    <div class="p-6 bg-white dark:bg-neutral-900 shadow-xl sm:rounded-lg max-w-5xl mx-auto">

        <div class="flex items-start justify-between mb-6 border-b border-[var(--border)] pb-4">
            <div>
                <h1 class="text-3xl font-extrabold text-[var(--text)]">Objective #{{ $objective->id }}</h1>
                <p class="text-[var(--text-muted)]">Posted by {{ optional($objective->submitter)->name }} @if(optional($objective->submitter)->email)<span class="text-xs">({{ optional($objective->submitter)->email }})</span>@endif</p>
                <div class="mt-2">
                    @php($label = (!$objective->status || $objective->status === 'DRAFT') ? 'FOR APPROVAL' : strtoupper($objective->status))
                    <span class="px-3 py-1 text-xs font-bold rounded-full 
                        {{ ($objective->status === 'APPROVED') ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100' : 
                           (($objective->status === 'REJECTED') ? 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-100' : 
                           'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-100') }}">
                        {{ $label }}
                    </span>
                </div>
                @if (session()->has('success'))
                    <div class="mt-3 p-2 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ session('success') }}</div>
                @endif
                @if (session()->has('error'))
                    <div class="mt-3 p-2 rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100">{{ session('error') }}</div>
                @endif
            </div>
            <div></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6 mb-8">
            <div>
                <p class="text-xs uppercase font-semibold text-[var(--text)-muted]"><span class="opacity-70">Agency</span></p>
                <p class="text-base font-medium text-[var(--text)]">{{ $objective->dost_agency }}</p>
            </div>
            <div>
                <p class="text-xs uppercase font-semibold text-[var(--text)-muted]"><span class="opacity-70">Target Period</span></p>
                <p class="text-base font-medium text-[var(--text)]">{{ $objective->target_period }}</p>
            </div>
            <div>
                <p class="text-xs uppercase font-semibold text-[var(--text)-muted]"><span class="opacity-70">Target Value</span></p>
                <p class="text-base font-medium text-[var(--text)]">{{ $objective->target_value }}</p>
            </div>
            <div>
                <p class="text-xs uppercase font-semibold text-[var(--text)-muted]"><span class="opacity-70">Reporting Agency</span></p>
                <p class="text-base font-medium text-[var(--text)]">{{ $objective->reporting_agency }}</p>
            </div>
        </div>

        <div class="space-y-6">
            @auth
                @if(auth()->user()->canActAsHeadOfOffice() && $objective->status === \App\Models\Indicator::STATUS_RETURNED_TO_HO)
                    {{-- HO "Send Down" Section --}}
                    @if($sendingDown)
                        <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)] mb-4">
                            <h3 class="font-semibold mb-2">Send Back to Original Maker</h3>
                            @if($originalRejectionNote)
                                <div class="mb-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-1">Original Rejection Note:</p>
                                    <p class="text-sm text-[var(--text)]">{{ $originalRejectionNote }}</p>
                                </div>
                            @endif
                            <div>
                                <label class="block text-sm mb-1">Your notes to the maker:</label>
                                <textarea rows="3" wire:model.defer="send_down_notes"
                                    class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]"
                                    placeholder="Add your context or forward the original note as-is..."></textarea>
                            </div>
                            <div class="mt-2 flex gap-2">
                                <button type="button" wire:click="sendDownToMaker"
                                    class="px-4 py-2 rounded bg-blue-600 text-white">
                                    Send to {{ $objective->submitter && $objective->submitter->isAgency() ? 'Agency' : 'PSTO' }}
                                </button>
                                <button type="button" wire:click="cancelSendDown"
                                    class="px-4 py-2 rounded border">Cancel</button>
                            </div>
                        </div>
                    @else
                        <button wire:click="startSendDown"
                            class="px-4 py-2 rounded border border-blue-600 text-blue-600 hover:bg-blue-50">
                            Send Down to Maker
                        </button>
                    @endif
                @endif
            @endauth

            @if ($rejecting)
                <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)]">
                    <h3 class="font-semibold mb-2">Reject Objective #{{ $objective->id }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm mb-1">Fields to fix (comma-separated)</label>
                            <input type="text" wire:model.defer="reject_fields" placeholder="e.g., indicator, baseline, mov" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]"/>
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Notes to Proponent</label>
                            <textarea rows="2" wire:model.defer="reject_notes" class="w-full rounded border-[var(--border)] bg-[var(--card-bg)]"></textarea>
                        </div>
                        <div class="md:col-span-2 flex gap-2">
                            <button type="button" wire:click="reject" class="px-4 py-2 rounded bg-red-600 text-white">Send back</button>
                            <button type="button" wire:click="cancelReject" class="px-4 py-2 rounded border">Cancel</button>
                        </div>
                    </div>
                </div>
            @endif
            <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)]">
                <p class="text-sm uppercase font-bold text-[var(--text)] mb-2">Objective / Results</p>
                <p class="whitespace-pre-wrap text-[var(--text)] text-base leading-relaxed">{{ $objective->objective_result }}</p>
            </div>

            <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)]">
                <p class="text-sm uppercase font-bold text-[var(--text)] mb-2">Indicator</p>
                <p class="whitespace-pre-wrap text-[var(--text)] text-base leading-relaxed">{{ $objective->indicator }}</p>
            </div>

            <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)]">
                <p class="text-sm uppercase font-bold text-[var(--text)] mb-2">Operational Definition</p>
                <p class="whitespace-pre-wrap text-[var(--text)] text-base leading-relaxed">{{ $objective->description }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)]">
                    <p class="text-sm uppercase font-bold text-[var(--text)] mb-2">Baseline</p>
                    <p class="whitespace-pre-wrap text-[var(--text)] text-base leading-relaxed">{{ $objective->baseline }}</p>
                </div>
                <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)]">
                    <p class="text-sm uppercase font-bold text-[var(--text)] mb-2">Accomplishments</p>
                    <p class="whitespace-pre-wrap text-[var(--text)] text-base leading-relaxed">{{ $objective->accomplishments }}</p>
                </div>
                <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)]">
                    <p class="text-sm uppercase font-bold text-[var(--text)] mb-2">Annual Plan Targets</p>
                    <p class="whitespace-pre-wrap text-[var(--text)] text-base leading-relaxed">{{ $objective->annual_plan_targets }}</p>
                </div>
                <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)]">
                    <p class="text-sm uppercase font-bold text-[var(--text)] mb-2">Means of Verification (MOV)</p>
                    <p class="whitespace-pre-wrap text-[var(--text)] text-base leading-relaxed">{{ $objective->mov }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)]">
                    <p class="text-sm uppercase font-bold text-[var(--text)] mb-2">Responsible Agency</p>
                    <p class="whitespace-pre-wrap text-[var(--text)] text-base leading-relaxed">{{ $objective->responsible_agency }}</p>
                </div>
                <div class="p-4 rounded-lg border border-[var(--border)] bg-[var(--card-bg)]">
                    <p class="text-sm uppercase font-bold text-[var(--text)] mb-2">Assumptions / Risk</p>
                    <p class="whitespace-pre-wrap text-[var(--text)] text-base leading-relaxed">{{ $objective->assumptions_risk }}</p>
                </div>
            </div>

            @if ($objective->myRejectionNote)
                <div class="p-4 rounded-lg border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 mb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-red-800 dark:text-red-200 mb-1">
                                Feedback from {{ $objective->myRejectionNote->rejectedBy->name }}
                            </p>
                            <p class="text-[var(--text)] whitespace-pre-wrap">{{ $objective->myRejectionNote->note }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Proofs Section (for approved indicators with updates) --}}
        @if($this->canViewProofs() && $objective->proofs && $objective->proofs->count() > 0)
            <div class="mt-6 p-5 rounded-lg bg-gray-50 border border-gray-200">
                <div class="flex items-center gap-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.707.293H19a2 2 0 012-2z"/>
                    </svg>
                    <h3 class="text-base font-semibold text-gray-800">Proof Documents ({{ $objective->proofs->count() }})</h3>
                </div>

                <div class="space-y-3">
                    @foreach($objective->proofs as $proof)
                        <div class="flex items-center justify-between p-3 rounded bg-white border border-gray-300">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 p-2 rounded bg-blue-100 text-blue-600">
                                    @if(str_ends_with(strtolower($proof->file_name), '.pdf') || str_ends_with(strtolower($proof->file_name), '.doc') || str_ends_with(strtolower($proof->file_name), '.docx'))
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                            <path d="M14 2v6h6M16 13H8"/>
                                            <path d="M16 17H8"/>
                                            <path d="M10 9H8"/>
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <path d="M21 15l-5-5L5 15"/>
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $proof->file_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $proof->human_file_size }} • Uploaded by {{ $proof->uploader->name ?? 'Unknown' }} • {{ $proof->created_at->format('M j, Y g:i A') }}
                                        @if($proof->year)
                                            • Year: <span class="font-semibold text-blue-600">{{ $proof->year }}</span>
                                        @endif
                                        @if($proof->mfo_reference)
                                            • MFO: <span class="font-mono text-green-700">{{ $proof->mfo_reference }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ $proof->url }}" target="_blank" class="px-3 py-1.5 text-sm rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                                    View/Download
                                </a>
                                @if($this->canDeleteProofs())
                                    <button wire:click="deleteProof({{ $proof->id }})" wire:confirm="Are you sure you want to delete this proof?" class="px-3 py-1.5 text-sm rounded border border-red-300 text-red-600 hover:bg-red-50">
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="sticky bottom-0 bg-[var(--card-bg)] border-t border-[var(--border)] p-3 mt-6 flex justify-between items-center">
            <div class="text-sm text-[var(--text-muted)]">
                Status: <span class="font-semibold">{{ strtoupper(str_replace('_', ' ', $objective->status)) }}</span>
            </div>
            <div class="flex justify-end gap-2">
                @if(str_starts_with($objective->status, 'returned_to_'))
                    {{-- For returned indicators - show Edit and Forward buttons --}}
                    @if(auth()->user()->canActAsHeadOfOffice() && $objective->status === \App\Models\Indicator::STATUS_RETURNED_TO_HO)
                        {{-- HO has special "Send Down" button above --}}
                    @else
                        <button wire:click="edit" class="px-4 py-2 rounded border border-blue-600 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20">
                            Edit
                        </button>
                        <button wire:click="forward" class="px-4 py-2 rounded border border-green-600 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20">
                            Send To (Resubmit)
                        </button>
                    @endif
                @elseif(!in_array($objective->status, ['APPROVED']))
                    {{-- Standard approve/reject for pending indicators --}}
                    <button wire:click="startReject" class="px-4 py-2 rounded border border-red-600 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                        Reject
                    </button>
                    <button wire:click="approve" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">
                        Approve
                    </button>
                @endif
            </div>
        </div>

    </div>
</section>
