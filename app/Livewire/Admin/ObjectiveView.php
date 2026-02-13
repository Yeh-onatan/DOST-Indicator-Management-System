<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Indicator as Objective;
use App\Models\RejectionNote;
use Illuminate\Support\Facades\Auth;

class ObjectiveView extends Component
{
    public $objective;
    public $rejecting = false;
    public $reject_fields = '';
    public $reject_notes = '';

    // For HO sending down to maker
    public $sendingDown = false;
    public $send_down_notes = '';
    public $originalRejectionNote = ''; // To show HO the original note

    public function mount(int $id): void
    {
        $obj = Objective::with(['myRejectionNote', 'submitter'])->find($id);
        abort_if(! $obj, 404);
        $this->objective = $obj;

        // Load original rejection note for HO to see
        if (auth()->user()->canActAsHeadOfOffice() && $obj->status === Objective::STATUS_RETURNED_TO_HO) {
            $this->loadOriginalRejectionNote();
        }
    }

    private function loadOriginalRejectionNote(): void
    {
        // Get the rejection note from SA/Admin
        $note = RejectionNote::where('objective_id', $this->objective->id)
            ->where('visible_to_user_id', auth()->id())
            ->latest()
            ->first();

        if ($note) {
            $this->originalRejectionNote = $note->note;
        }
    }

    public function startSendDown(): void
    {
        $this->sendingDown = true;
        $this->send_down_notes = $this->originalRejectionNote; // Pre-fill with original
    }

    public function cancelSendDown(): void
    {
        $this->sendingDown = false;
        $this->send_down_notes = '';
    }

    public function sendDownToMaker(): void
    {
        if (trim((string)$this->send_down_notes) === '') {
            $this->dispatch('toast', message: 'Please provide notes for the maker.', type: 'error');
            return;
        }

        if (!$this->objective->sendDownToMaker(Auth::user(), $this->send_down_notes)) {
            $this->dispatch('toast', message: 'Cannot send down.', type: 'error');
            return;
        }

        $this->dispatch('toast', message: 'Sent back to original maker.', type: 'success');
        $this->dispatch('closeModal');
    }

    public function approve(): void
    {
        $obj = $this->objective->fresh(['submitter']);
        if (! $obj) { abort(404); }

        $before = $obj->only(['status']);
        $obj->status = 'APPROVED';
        $obj->review_notes = null;
        $obj->corrections_required = null;
        $obj->save();

        \App\Models\AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => 'update',
            'entity_type' => 'Objective',
            'entity_id' => (string)$obj->id,
            'changes' => ['diff' => [ 'status' => ['before' => $before['status'] ?? null, 'after' => 'APPROVED'] ]],
        ]);

        if ($obj->submitter) {
            $obj->submitter->notify(new \App\Notifications\ObjectiveStatusChanged(
                status: 'APPROVED',
                objective: $obj,
                notes: null,
                corrections: null,
                actorId: auth()->id(),
            ));
        }

        $this->objective = $obj;
        session()->flash('success', 'Approved successfully.');
        $this->rejecting = false;
        $this->reject_fields = '';
        $this->reject_notes = '';
    }

    public function startReject(): void
    {
        $this->rejecting = true;
        $this->reject_fields = '';
        $this->reject_notes = '';
    }

    public function cancelReject(): void
    {
        $this->rejecting = false;
    }

    public function forward(): void
    {
        if (!$this->objective->forward(Auth::user())) {
            $this->dispatch('toast', message: 'Cannot forward this indicator.', type: 'error');
            return;
        }

        $this->dispatch('toast', message: 'Indicator forwarded successfully.', type: 'success');
        $this->dispatch('closeModal');
    }

    public function reject(): void
    {
        $obj = $this->objective->fresh(['submitter']);
        if (! $obj) { abort(404); }

        if (trim((string)$this->reject_notes) === '' && trim((string)$this->reject_fields) === '') {
            session()->flash('error', 'Please provide remarks and list at least one field to fix.');
            return;
        }

        $fields = collect(preg_split('/\s*,\s*/', (string)$this->reject_fields, -1, PREG_SPLIT_NO_EMPTY))
            ->unique()->values()->all();
        if (empty($fields)) {
            session()->flash('error', 'Please list at least one field to fix (e.g., indicator, baseline, mov).');
            return;
        }

        $before = $obj->only(['status','review_notes','corrections_required']);
        $obj->status = 'REJECTED';
        $obj->review_notes = $this->reject_notes ?: null;
        $obj->corrections_required = $fields ?: null;
        $obj->save();

        \App\Models\AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => 'update',
            'entity_type' => 'Objective',
            'entity_id' => (string)$obj->id,
            'changes' => ['diff' => [
                'status' => ['before' => $before['status'] ?? null, 'after' => 'REJECTED'],
                'review_notes' => ['before' => $before['review_notes'] ?? null, 'after' => $obj->review_notes],
                'corrections_required' => ['before' => $before['corrections_required'] ?? null, 'after' => $obj->corrections_required],
            ]],
        ]);

        if ($obj->submitter) {
            $obj->submitter->notify(new \App\Notifications\ObjectiveStatusChanged(
                status: 'REJECTED',
                objective: $obj,
                notes: $this->reject_notes ?: null,
                corrections: $fields ?: null,
                actorId: auth()->id(),
            ));
        }

        $this->objective = $obj;
        session()->flash('success', 'Rejected and sent back with required fixes.');
        $this->rejecting = false;
        $this->reject_fields = '';
        $this->reject_notes = '';
    }

    // --- Proof Management ---

    public function canViewProofs(): bool
    {
        // HO and above can view proofs
        return auth()->user()->canActAsHeadOfOffice()
            || auth()->user()->isAdministrator()
            || auth()->user()->isSA()
            || auth()->user()->isOUSEC();
    }

    public function canDeleteProofs(): bool
    {
        // Only SuperAdmin can delete proofs
        return auth()->user()->isSA();
    }

    public function deleteProof(int $proofId): void
    {
        if (! $this->canDeleteProofs()) {
            abort(403, 'You do not have permission to delete proofs.');
        }

        $proof = \App\Models\Proof::find($proofId);
        if (! $proof || $proof->objective_id !== $this->objective->id) {
            abort(404, 'Proof not found.');
        }

        // Delete file from storage
        $filePath = public_path('storage/' . $proof->file_path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $proof->delete();

        $this->objective = $this->objective->fresh(['proofs']);
        session()->flash('success', 'Proof deleted successfully.');
    }

    public function render()
    {
        // Load proofs when rendering
        $this->objective->load('proofs.uploader');

        return view('livewire.admin.objective-view')
            ->layout('components.layouts.app');
    }
}
