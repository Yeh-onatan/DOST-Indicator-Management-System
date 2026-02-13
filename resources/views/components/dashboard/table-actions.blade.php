@props([
    'objective' => null,
    'user' => null,
    'viewMode' => false,
    'editingQuickFormId' => null,
])

<td class="px-4 py-3 text-center">
    <div class="inline-flex items-center justify-center gap-2">

        {{-- Common View Button --}}
        <button wire:click="openView({{ $objective->id }})"
            class="p-1.5 rounded-full text-blue-600 hover:bg-blue-100 hover:text-blue-800 transition-all"
            title="View Details">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
        </button>

        {{-- View History Button (Clock Icon) - SA, Admin, Execom only --}}
        @if($user->isSA() || $user->isAdministrator() || $user->isExecom())
            <button
                wire:click="$dispatch('open-entity-history', { entityType: 'Objective', entityId: '{{ $objective->id }}', entityName: '{{ $objective->indicator ?? 'Indicator #' . $objective->id }}' })"
                class="p-1.5 rounded-full text-gray-600 hover:bg-gray-100 hover:text-gray-800 transition-all"
                title="View Audit History">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </button>
        @endif

        {{-- ================= PSTO ACTIONS ================= --}}
        @if($user->isPSTO())
            @if($objective->status === \App\Models\Indicator::STATUS_APPROVED)
                {{-- Update Progress (Refresh Icon) --}}
                <button wire:click="openUpdateProgress({{ $objective->id }})"
                    class="p-1.5 rounded-full text-green-600 hover:bg-green-100 hover:text-green-800 transition-all"
                    title="Update Progress">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </button>
            @endif

            @if(in_array($objective->status, ['DRAFT', 'rejected', 'returned_to_psto']))
                {{-- Edit (Pencil Icon) --}}
                <button wire:click="openEdit({{ $objective->id }})"
                    class="p-1.5 rounded-full text-amber-600 hover:bg-amber-100 hover:text-amber-800 transition-all"
                    title="Edit">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </button>

                {{-- Submit to RO (Paper Airplane) --}}
                <button
                    wire:click="submitToRO({{ $objective->id }})"
                    wire:confirm="Submit to Regional Office?"
                    class="p-1.5 rounded-full text-[#00AEEF] hover:bg-blue-50 hover:text-blue-600 transition-all"
                    title="Submit to R.O.">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                </button>
            @endif
        @endif

        {{-- ================= RO ACTIONS ================= --}}
        @if($user->isRO())
            @if($objective->status === \App\Models\Indicator::STATUS_SUBMITTED_TO_RO || $objective->status === \App\Models\Indicator::STATUS_RETURNED_TO_RO)
                <button wire:click="openEdit({{ $objective->id }})"
                    class="p-1.5 rounded-full text-amber-600 hover:bg-amber-100 hover:text-amber-800 transition-all"
                    title="Edit">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </button>

                <button wire:click="submitToHO({{ $objective->id }})"
                    wire:confirm="Submit to Head of Office?"
                    class="p-1.5 rounded-full text-[#00AEEF] hover:bg-blue-50 hover:text-blue-600 transition-all"
                    title="Submit to H.O.">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                </button>

                <button wire:click="openRejectionModal({{ $objective->id }})"
                    class="p-1.5 rounded-full text-red-600 hover:bg-red-100 hover:text-red-800 transition-all"
                    title="Return to PSTO">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                </button>
            @endif
        @endif

        {{-- ================= AGENCY ACTIONS ================= --}}
        @if($user->isAgency())
            {{-- Update Progress (when approved) --}}
            @if($objective->status === \App\Models\Indicator::STATUS_APPROVED)
                <button wire:click="openUpdateProgress({{ $objective->id }})"
                    class="p-1.5 rounded-full text-green-600 hover:bg-green-100 hover:text-green-800 transition-all"
                    title="Update Progress">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </button>
            @endif

            {{-- Edit, Submit (when draft/rejected/returned) --}}
            @if(in_array($objective->status, ['DRAFT', 'rejected', 'returned_to_agency']))
                {{-- Edit (Pencil Icon) --}}
                <button wire:click="openEdit({{ $objective->id }})"
                    class="p-1.5 rounded-full text-amber-600 hover:bg-amber-100 hover:text-amber-800 transition-all"
                    title="Edit">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </button>

                {{-- Submit (Paper Airplane) - Dynamic text based on HO assignment --}}
                @php
                    $agencyHasHO = $objective->submitter && $objective->submitter->agency && $objective->submitter->agency->head_user_id;
                    $submitLabel = $agencyHasHO ? 'Submit to H.O.' : 'Submit to Admin';
                    $submitConfirm = $agencyHasHO ? 'Submit to Head of Office?' : 'Submit to Administrator?';
                @endphp
                <button
                    wire:click="submitToHO({{ $objective->id }})"
                    wire:confirm="{{ $submitConfirm }}"
                    class="p-1.5 rounded-full text-[#00AEEF] hover:bg-blue-50 hover:text-blue-600 transition-all"
                    title="{{ $submitLabel }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                </button>
            @endif
        @endif

        {{-- ================= EXECOM ACTIONS ================= --}}
        @if($user->isExecom())
            {{-- View button only - no edit, submit, delete, approve, reject --}}
            {{-- (View button is already shown for all users at line 243) --}}
        @endif

        {{-- ================= OUSEC ACTIONS ================= --}}
        @if($user->isOUSEC())
            @if(in_array($objective->status, ['submitted_to_ousec', 'returned_to_ousec']))
                {{-- Approve (Forward to Admin) --}}
                @php
                    // Show the specific OUSEC role name
                    $ousecRoleName = 'OUSEC';
                    if ($user->role === 'ousec_sts') $ousecRoleName = 'OUSEC-STS';
                    elseif ($user->role === 'ousec_rd') $ousecRoleName = 'OUSEC-RD';
                    elseif ($user->role === 'ousec_ro') $ousecRoleName = 'OUSEC-RO';
                @endphp
                <button wire:click="ousecApprove({{ $objective->id }})"
                    wire:confirm="Approve and forward to Administrator?"
                    class="p-1.5 rounded-full text-green-600 hover:bg-green-100 hover:text-green-800 transition-all"
                    title="{{ $ousecRoleName }} Approve & Forward to Admin">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>

                {{-- Reject (Return to HO) --}}
                <button wire:click="openRejectionModal({{ $objective->id }})"
                    class="p-1.5 rounded-full text-red-600 hover:bg-red-100 hover:text-red-800 transition-all"
                    title="Return to Head Office">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                </button>
            @endif
        @endif

        {{-- ================= HO/ACTION ACTIONS ================= --}}
        @if($user->canActAsHeadOfOffice())
            @if($objective->status === \App\Models\Indicator::STATUS_SUBMITTED_TO_HO)
                {{-- Approve (Forward to OUSEC or Admin) --}}
                @php
                    // Determine the forward destination for dynamic title
                    $forwardDest = 'Administrator'; // default

                    if ($objective->submitter && $objective->submitter->agency) {
                        if ($objective->submitter->agency->code === 'DOST-CO') {
                            $forwardDest = 'Administrator';
                        } else {
                            // Show specific OUSEC type (OUSEC-STS, OUSEC-RD)
                            $ousecType = \App\Constants\AgencyConstants::getOUSECTypeForCluster($objective->submitter->agency->cluster);
                            $forwardDest = $ousecType ? str_replace('ousec_', 'OUSEC-', strtoupper($ousecType)) : 'OUSEC';
                        }
                    } elseif ($objective->submitter && ($objective->submitter->office || $objective->submitter->region_id)) {
                        $forwardDest = 'OUSEC-RO';
                    }
                @endphp
                <button wire:click="approve({{ $objective->id }})"
                    wire:confirm="Forward to {{ $forwardDest }}?"
                    class="p-1.5 rounded-full text-green-600 hover:bg-green-100 hover:text-green-800 transition-all"
                    title="Forward to {{ $forwardDest }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>

                {{-- Reject (X) --}}
                <button wire:click="openRejectionModal({{ $objective->id }})"
                    class="p-1.5 rounded-full text-red-600 hover:bg-red-100 hover:text-red-800 transition-all"
                    title="Reject">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>
            @endif
        @endif

        {{-- ================= ADMIN ACTIONS ================= --}}
        @if($user->isAdministrator())
            @if($objective->status === \App\Models\Indicator::STATUS_SUBMITTED_TO_ADMIN)
                {{-- Approve (Forward to SuperAdmin) --}}
                <button wire:click="approve({{ $objective->id }})"
                    wire:confirm="Forward to SuperAdmin?"
                    class="p-1.5 rounded-full text-green-600 hover:bg-green-100 hover:text-green-800 transition-all"
                    title="Forward to SuperAdmin">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>

                {{-- Reject (X) --}}
                <button wire:click="openRejectionModal({{ $objective->id }})"
                    class="p-1.5 rounded-full text-red-600 hover:bg-red-100 hover:text-red-800 transition-all"
                    title="Reject">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>
            @endif
        @endif

        {{-- ================= SUPERADMIN ACTIONS ================= --}}
        @if($user->isSuperAdmin())
            @if($objective->status === \App\Models\Indicator::STATUS_SUBMITTED_TO_SUPERADMIN)
                {{-- Final Approve (Check) --}}
                <button wire:click="approve({{ $objective->id }})"
                    wire:confirm="Give final approval? This will lock the indicator."
                    class="p-1.5 rounded-full text-green-600 hover:bg-green-100 hover:text-green-800 transition-all"
                    title="Final Approval">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>

                {{-- Reject (X) --}}
                <button wire:click="openRejectionModal({{ $objective->id }})"
                    class="p-1.5 rounded-full text-red-600 hover:bg-red-100 hover:text-red-800 transition-all"
                    title="Reject">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>
            @endif
        @endif

        {{-- ================= SA/ADMIN POWERS (Force Delete, Admin Edit, Reopen) ================= --}}
        @if($user->isSA() || $user->isAdministrator())
            {{-- Separator for admin actions --}}
            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            {{-- Admin Edit (Edit with Shield) --}}
            <button wire:click="openAdminConfirm({{ $objective->id }}, 'adminEdit')"
                class="p-1.5 rounded-full text-purple-600 hover:bg-purple-100 hover:text-purple-800 transition-all"
                title="Admin Edit (Bypass Workflow)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" /></svg>
            </button>

            {{-- Force Delete (Trash with Warning) --}}
            @if($objective->status !== \App\Models\Indicator::STATUS_APPROVED)
                <button wire:click="openAdminConfirm({{ $objective->id }}, 'adminDelete')"
                    class="p-1.5 rounded-full text-orange-600 hover:bg-orange-100 hover:text-orange-800 transition-all"
                    title="Force Delete (Admin)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 0v2m0-2h2m-2 0H10" /></svg>
                </button>
            @endif

            {{-- Reopen (Refresh/Undo) --}}
            @if(in_array($objective->status, ['approved', 'rejected']))
                <button wire:click="openAdminConfirm({{ $objective->id }}, 'reopen')"
                    class="p-1.5 rounded-full text-blue-600 hover:bg-blue-100 hover:text-blue-800 transition-all"
                    title="Reopen to Draft">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </button>
            @endif
        @endif

        {{-- ==================== SUPERADMIN POWERS ==================== --}}
        @if($user->isSuperAdmin())
            {{-- SuperAdmin Separator --}}
            <div class="w-px h-5 bg-red-900 mx-1"></div>

            {{-- Force Delete Approved (Double Danger) --}}
            @if($objective->status === \App\Models\Indicator::STATUS_APPROVED)
                <button wire:click="forceDeleteApproved({{ $objective->id }})"
                    wire:confirm="⚠️ DANGER: This will PERMANENTLY delete an APPROVED indicator. This action CANNOT be undone. Continue?"
                    class="p-1.5 rounded-full text-red-700 hover:bg-red-100 hover:text-red-900 transition-all"
                    title="Force Delete Approved (SuperAdmin Only)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </button>
            @endif

            {{-- Force Status Dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false" class="p-1.5 rounded-full text-indigo-600 hover:bg-indigo-100 hover:text-indigo-800 transition-all"
                        title="Force Status (SuperAdmin)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                </button>
                {{-- Dropdown Menu --}}
                <div x-show="open" @click.outside="open = false" class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <div class="py-1">
                        <button @click="open = false; $wire.openForceStatusModal({{ $objective->id }}, 'DRAFT')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Force to Draft</button>
                        <button @click="open = false; $wire.openForceStatusModal({{ $objective->id }}, 'submitted_to_ro')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Force to RO Review</button>
                        <button @click="open = false; $wire.openForceStatusModal({{ $objective->id }}, 'submitted_to_ho')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Force to HO Review</button>
                        <button @click="open = false; $wire.openForceStatusModal({{ $objective->id }}, 'approved')" class="block w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-100">Force Approve</button>
                        <button @click="open = false; $wire.openForceStatusModal({{ $objective->id }}, 'rejected')" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-100">Force Reject</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</td>
