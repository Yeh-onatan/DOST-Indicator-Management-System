<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Indicator as Objective;
use Illuminate\Support\Facades\Auth;

class Approvals extends Component
{
    public $pending = [];
    public $rejectingId = null;
    public $reject_fields = '';
    public $reject_notes = '';
    public $hasViewedId = null;
    public $viewingId = null;
    public $viewing = null;

    public function mount(): void
    {
        $this->reload();
    }

    public function reload(): void
    {
        $user = Auth::user();
        $query = Objective::with(['submitter', 'region', 'office']);

        // Super Admin, Administrator, and EXECOM see all pending items
        if ($user->isSA() || $user->isAdministrator() || $user->isExecom()) {
            $query->whereIn('status', [
                Objective::STATUS_SUBMITTED_TO_RO,
                Objective::STATUS_SUBMITTED_TO_HO,
                Objective::STATUS_SUBMITTED_TO_OUSEC,
                Objective::STATUS_SUBMITTED_TO_ADMIN,
                Objective::STATUS_RETURNED_TO_RO,
                Objective::STATUS_RETURNED_TO_AGENCY,
                Objective::STATUS_RETURNED_TO_OUSEC,
            ])->where('is_locked', false);
        }
        // OUSEC users see indicators submitted to OUSEC for their review
        elseif ($user->isOUSEC()) {
            $query->where('status', Objective::STATUS_SUBMITTED_TO_OUSEC)
                  ->where('is_locked', false);

            if ($user->isOUSEROR()) {
                $query->where(function ($sub) {
                    $sub->whereNotNull('office_id')
                        ->whereNotNull('region_id');
                });
            } else {
                $allowedClusters = $user->getOUSECClusters();
                $query->whereHas('submitter.agency', function ($agencyQuery) use ($allowedClusters) {
                    $agencyQuery->whereIn('cluster', $allowedClusters);
                });
            }
        }
        // RO role - see regional submissions
        elseif ($user->isRO()) {
            $roOffices = \App\Models\Office::where('head_user_id', $user->id)
                ->where('type', 'RO')->pluck('id');
            if ($roOffices->isNotEmpty()) {
                $childPstoOfficeIds = \App\Models\Office::whereIn('parent_office_id', $roOffices)->pluck('id');
                $allOfficeIds = $roOffices->concat($childPstoOfficeIds)->unique();
                $query->where(function($q) use ($allOfficeIds) {
                    $q->whereIn('office_id', $allOfficeIds)
                      ->whereIn('status', [
                          Objective::STATUS_SUBMITTED_TO_RO,
                          Objective::STATUS_RETURNED_TO_RO,
                      ]);
                });
            } else {
                // If RO is not assigned as head of any office, they see their own submitted objectives that were returned
                $query->where('submitted_by_user_id', $user->id)
                      ->whereIn('status', [
                          Objective::STATUS_SUBMITTED_TO_RO,
                          Objective::STATUS_RETURNED_TO_RO,
                      ]);
            }
        }
        // Head of Office - see submissions to HO level
        elseif ($user->canActAsHeadOfOffice()) {
            if ($user->agency_id) {
                // Agency-assigned HO - see agency submissions
                $query->whereHas('submitter', function($s) use ($user) {
                    $s->where('agency_id', $user->agency_id);
                })->whereIn('status', [
                    Objective::STATUS_SUBMITTED_TO_HO,
                    Objective::STATUS_RETURNED_TO_AGENCY
                ]);
            } elseif ($user->region_id) {
                // Region-assigned HO - see regional submissions
                $query->where('region_id', $user->region_id)
                      ->whereIn('status', [
                          Objective::STATUS_SUBMITTED_TO_HO,
                          Objective::STATUS_RETURNED_TO_AGENCY,
                          Objective::STATUS_RETURNED_TO_OUSEC,
                      ]);
            }
        }
        // PSTO - see only their own returned items
        elseif ($user->isPSTO()) {
            $query->where('submitted_by_user_id', $user->id)
                  ->where('status', Objective::STATUS_RETURNED_TO_PSTO);
        }
        // Agency - see only their own returned items
        elseif ($user->isAgency()) {
            $query->where('submitted_by_user_id', $user->id)
                  ->where('status', Objective::STATUS_RETURNED_TO_AGENCY);
        }

        $this->pending = $query->orderByDesc('updated_at')->get();

        if ($this->viewingId) {
            $this->viewing = Objective::with('submitter')->find($this->viewingId);
            if (! $this->viewing) {
                $this->closeView();
            }
        }
    }

    #[On('objective-created')]
    #[On('objective-updated')]
    #[On('objective-deleted')]
    public function refreshFromEvents(): void
    {
        $this->reload();
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        $this->viewing = Objective::with('submitter')->find($id);
        $this->hasViewedId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
        $this->viewing = null;
        $this->rejectingId = null;
        $this->reject_fields = '';
        $this->reject_notes = '';
    }

    public function approve(int $id): void
    {
        if ($this->hasViewedId !== $id) {
            $this->dispatch('toast', message: 'Please click View to open details before approving.', type: 'error');
            return;
        }

        try {
            $objective = Objective::findOrFail($id);
            $user = Auth::user();

            // Use the model's approve method which handles all role-based routing including OUSEC
            $objective->approve($user);

            // Generate appropriate message based on new status
            $message = match($objective->status) {
                Objective::STATUS_SUBMITTED_TO_HO => 'Approved and forwarded to Head Office.',
                Objective::STATUS_SUBMITTED_TO_OUSEC => 'Approved and forwarded to OUSEC.',
                Objective::STATUS_SUBMITTED_TO_ADMIN => 'Approved and forwarded to Admin.',
                Objective::STATUS_SUBMITTED_TO_SUPERADMIN => 'Approved and forwarded to SuperAdmin.',
                Objective::STATUS_APPROVED => 'Final approval granted. Indicator is now locked.',
                default => 'Action completed.',
            };

            // Audit Log
            \App\Services\AuditService::log(
                action: 'approve',
                modelType: 'Objective',
                modelId: $objective->id,
                changes: ['status' => $objective->status],
                actorId: auth()->id()
            );

            // Notification is sent by the approve() method, no need to send here

            $this->reload();
            $this->closeView();
            $this->dispatch('toast', message: $message, type: 'success');
        } catch (\Throwable $e) {
            \Log::error('Approvals::approve failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to approve indicator. Please try again.', type: 'error');
        }
    }

    public function startReject(int $id): void
    {
        if ($this->hasViewedId !== $id) {
            $this->dispatch('toast', message: 'Please click View to open details before rejecting.', type: 'error');
            return;
        }
        $this->rejectingId = $id;
        $this->reject_fields = '';
        $this->reject_notes = '';
    }

    public function reject(): void
    {
        if (!$this->rejectingId) return;

        try {
            $obj = Objective::with('submitter')->find($this->rejectingId);
            if (!$obj) return;

            if (trim((string)$this->reject_notes) === '') {
                $this->dispatch('toast', message: 'Please provide rejection remarks.', type: 'error');
                return;
            }

            $user = Auth::user();

            // Use model's reject logic - now creates private notes
            $obj->reject($user, $this->reject_notes);

            // Log audit
            \App\Services\AuditService::log(
                action: 'reject',
                modelType: 'Objective',
                modelId: $obj->id,
                changes: ['status' => $obj->status],
                actorId: auth()->id()
            );

            $this->rejectingId = null;
            $this->reject_fields = '';
            $this->reject_notes = '';
            $this->reload();
            $this->closeView();
            $this->dispatch('toast', message: 'Indicator returned with feedback.', type: 'success');
        } catch (\Throwable $e) {
            \Log::error('Approvals::reject failed', ['id' => $this->rejectingId, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to reject indicator. Please try again.', type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.admin.approvals')->layout('components.layouts.app');
    }
}
