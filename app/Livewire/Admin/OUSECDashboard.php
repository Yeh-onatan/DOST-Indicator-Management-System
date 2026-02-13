<?php

namespace App\Livewire\Admin;

use App\Constants\AgencyConstants;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Indicator as Objective;
use Illuminate\Support\Facades\Auth;

class OUSECDashboard extends Component
{
    public $pending = [];
    public $rejectingId = null;
    public $reject_notes = '';
    public $viewingId = null;
    public $viewing = null;

    // Filters
    public $filterStatus = 'all'; // all, submitted_to_ousec, returned_to_ousec
    public $filterCluster = 'all'; // all, regional, ssi, collegial, council, rdi

    public function mount(): void
    {
        $this->reload();
    }

    public function reload(): void
    {
        $user = Auth::user();

        if (!$user->isOUSEC()) {
            $this->pending = collect();
            return;
        }

        $query = Objective::with(['submitter', 'submitter.agency', 'region', 'office']);

        // OUSEC-RO: handles regional/PSTO indicators
        if ($user->isOUSEROR()) {
            $query->where(function($q) {
                $q->whereNotNull('office_id')
                  ->orWhereNotNull('region_id');
            })->whereIn('status', [
                Objective::STATUS_SUBMITTED_TO_OUSEC,
                Objective::STATUS_RETURNED_TO_OUSEC,
            ])->where('is_locked', false);
        }
        // OUSEC-STS and OUSEC-RD: handle agency indicators based on cluster
        else {
            $allowedClusters = $user->getOUSECClusters();
            $query->whereHas('submitter.agency', function($agencyQuery) use ($allowedClusters) {
                $agencyQuery->whereIn('cluster', $allowedClusters);
            })->whereIn('status', [
                Objective::STATUS_SUBMITTED_TO_OUSEC,
                Objective::STATUS_RETURNED_TO_OUSEC,
            ])->where('is_locked', false);
        }

        // Apply status filter
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        // Apply cluster filter (for STS/RD)
        if ($this->filterCluster !== 'all' && !$user->isOUSEROR()) {
            $query->whereHas('submitter.agency', function($q) use ($user) {
                $allowedClusters = $user->getOUSECClusters();
                $q->whereIn('cluster', $allowedClusters);
            });
        }

        $this->pending = $query->orderByDesc('updated_at')->get();

        if ($this->viewingId) {
            $this->viewing = Objective::with(['submitter', 'submitter.agency', 'myRejectionNote'])->find($this->viewingId);
            if (!$this->viewing) {
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
        $this->viewing = Objective::with(['submitter', 'submitter.agency', 'myRejectionNote'])->find($id);
    }

    public function closeView(): void
    {
        $this->viewingId = null;
        $this->viewing = null;
        $this->rejectingId = null;
        $this->reject_notes = '';
    }

    public function approve(int $id): void
    {
        try {
            $objective = Objective::findOrFail($id);
            $user = Auth::user();

            // Use the model's approve() method which handles OUSEC → Admin routing
            // This works for both submitted_to_ousec and returned_to_ousec states
            $objective->approve($user);

            $freshStatus = $objective->fresh()->status;
            $message = match($freshStatus) {
                Objective::STATUS_SUBMITTED_TO_ADMIN => 'Approved and forwarded to Administrator.',
                default => 'Action completed.',
            };

            // Audit Log
            \App\Models\AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'approve',
                'entity_type' => 'Objective',
                'entity_id' => (string)$objective->id,
                'changes' => ['status' => $freshStatus],
            ]);

            $this->reload();
            $this->closeView();
            $this->dispatch('toast', message: $message, type: 'success');
        } catch (\Throwable $e) {
            \Log::error('OUSECDashboard::approve failed', ['id' => $id, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to approve indicator. Please try again.', type: 'error');
        }
    }

    public function startReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->reject_notes = '';
        $this->view($id); // Auto-open view when rejecting
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

            // OUSEC rejects to HO
            $obj->reject($user, $this->reject_notes);

            $this->rejectingId = null;
            $this->reject_notes = '';
            $this->reload();
            $this->closeView();
            $this->dispatch('toast', message: 'Indicator returned to Head Office with feedback.', type: 'success');
        } catch (\Throwable $e) {
            \Log::error('OUSECDashboard::reject failed', ['id' => $this->rejectingId, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'Failed to reject indicator. Please try again.', type: 'error');
        }
    }

    public function getOUSECTypeLabel(): string
    {
        $user = Auth::user();
        return match(true) {
            $user->isOUSEROR() => 'OUSEC-RO (Regional Operations)',
            $user->isOUSECSTS() => 'OUSEC-STS (S&T Services)',
            $user->isOUSECRD() => 'OUSEC-RD (R&D)',
            default => 'OUSEC',
        };
    }

    public function getAssignedClusters(): array
    {
        $user = Auth::user();
        return $user->getOUSECClusters();
    }

    public function isOUSEROR(): bool
    {
        return Auth::user()->isOUSEROR();
    }

    /**
     * Get CSS classes for cluster badge styling
     */
    public function getClusterBadgeClasses(string $cluster): string
    {
        return match($cluster) {
            AgencyConstants::CLUSTER_SSI => 'bg-blue-100 text-blue-700',
            AgencyConstants::CLUSTER_COLLEGIAL => 'bg-purple-100 text-purple-700',
            AgencyConstants::CLUSTER_COUNCIL => 'bg-green-100 text-green-700',
            AgencyConstants::CLUSTER_RDI => 'bg-orange-100 text-orange-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function render()
    {
        return view('livewire.admin.o-u-s-e-c-dashboard')->layout('components.layouts.app');
    }
}
