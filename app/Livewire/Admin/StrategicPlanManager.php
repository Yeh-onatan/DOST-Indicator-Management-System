<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Pillar;
use App\Models\Outcome;
use App\Models\Strategy;
use App\Models\AuditLog;

class StrategicPlanManager extends Component
{
    use WithPagination;

    // Tab Management
    public string $activeTab = 'pillars';

    // Pillar Form Properties
    public $pillarValue;
    public $pillarIsActive = true;
    public $editingPillarId = null;

    // Outcome Form Properties
    public $outcomeValue;
    public $outcomeIsActive = true;
    public $editingOutcomeId = null;

    // Strategy Form Properties
    public $strategyValue;
    public $strategyIsActive = true;
    public $editingStrategyId = null;

    // Modal Visibility
    public bool $showPillarModal = false;
    public bool $showOutcomeModal = false;
    public bool $showStrategyModal = false;

    protected function rules()
    {
        $pillarUnique = 'required|integer|unique:pillars,value' . ($this->editingPillarId ? ',' . $this->editingPillarId : '');
        $outcomeUnique = 'required|integer|unique:outcomes,value' . ($this->editingOutcomeId ? ',' . $this->editingOutcomeId : '');
        $strategyUnique = 'required|integer|unique:strategies,value' . ($this->editingStrategyId ? ',' . $this->editingStrategyId : '');

        return [
            'pillarValue' => $pillarUnique,
            'pillarIsActive' => 'boolean',
            'outcomeValue' => $outcomeUnique,
            'outcomeIsActive' => 'boolean',
            'strategyValue' => $strategyUnique,
            'strategyIsActive' => 'boolean',
        ];
    }

    public function render()
    {
        return view('livewire.admin.strategic-plan-manager', [
            'pillars' => Pillar::orderBy('value')->paginate(10),
            'outcomes' => Outcome::orderBy('value')->paginate(10),
            'strategies' => Strategy::orderBy('value')->paginate(10),
        ]);
    }

    public function setTab($tab): void
    {
        $this->activeTab = $tab;
    }

    // --- Pillar Methods ---

    public function createPillar(): void
    {
        $this->resetPillarForm();
        $this->showPillarModal = true;
    }

    public function editPillar($id): void
    {
        $this->resetPillarForm();
        $pillar = Pillar::findOrFail($id);
        $this->editingPillarId = $id;
        $this->pillarValue = $pillar->value;
        $this->pillarIsActive = $pillar->is_active;
        $this->showPillarModal = true;
    }

    public function savePillar(): void
    {
        $uniqueRule = 'required|integer|unique:pillars,value';
        if ($this->editingPillarId) {
            $uniqueRule .= ',' . $this->editingPillarId;
        }

        $this->validate([
            'pillarValue' => $uniqueRule,
            'pillarIsActive' => 'boolean',
        ]);

        $data = [
            'value' => $this->pillarValue,
            'is_active' => $this->pillarIsActive,
        ];

        if ($this->editingPillarId) {
            $pillar = Pillar::find($this->editingPillarId);
            $pillar->update($data);

            // Log pillar update
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'update',
                'entity_type' => 'Pillar',
                'entity_id' => (string)$pillar->id,
                'changes' => ['diff' => [
                    'value' => ['before' => null, 'after' => $pillar->value],
                    'is_active' => ['before' => null, 'after' => $pillar->is_active],
                ]],
            ]);
        } else {
            $pillar = Pillar::create($data);

            // Log pillar creation
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'create',
                'entity_type' => 'Pillar',
                'entity_id' => (string)$pillar->id,
                'changes' => ['diff' => [
                    'value' => ['before' => null, 'after' => $pillar->value],
                ]],
            ]);
        }

        $this->closePillarModal();
        session()->flash('message', 'Pillar saved successfully.');
    }

    public function deletePillar($id): void
    {
        $pillar = Pillar::findOrFail($id);

        // Snapshot pillar before deletion
        $snapshot = $pillar->only(['id', 'value', 'is_active']);

        $pillar->delete();

        // Log pillar deletion
        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => 'delete',
            'entity_type' => 'Pillar',
            'entity_id' => (string)$id,
            'changes' => ['deleted' => $snapshot],
        ]);

        session()->flash('message', 'Pillar deleted successfully.');
    }

    public function closePillarModal(): void
    {
        $this->showPillarModal = false;
        $this->resetPillarForm();
    }

    private function resetPillarForm(): void
    {
        $this->reset(['pillarValue', 'pillarIsActive', 'editingPillarId']);
        $this->pillarIsActive = true;
    }

    // --- Outcome Methods ---

    public function createOutcome(): void
    {
        $this->resetOutcomeForm();
        $this->showOutcomeModal = true;
    }

    public function editOutcome($id): void
    {
        $this->resetOutcomeForm();
        $outcome = Outcome::findOrFail($id);
        $this->editingOutcomeId = $id;
        $this->outcomeValue = $outcome->value;
        $this->outcomeIsActive = $outcome->is_active;
        $this->showOutcomeModal = true;
    }

    public function saveOutcome(): void
    {
        $uniqueRule = 'required|integer|unique:outcomes,value';
        if ($this->editingOutcomeId) {
            $uniqueRule .= ',' . $this->editingOutcomeId;
        }

        $this->validate([
            'outcomeValue' => $uniqueRule,
            'outcomeIsActive' => 'boolean',
        ]);

        $data = [
            'value' => $this->outcomeValue,
            'is_active' => $this->outcomeIsActive,
        ];

        if ($this->editingOutcomeId) {
            $outcome = Outcome::find($this->editingOutcomeId);
            $outcome->update($data);

            // Log outcome update
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'update',
                'entity_type' => 'Outcome',
                'entity_id' => (string)$outcome->id,
                'changes' => ['diff' => [
                    'value' => ['before' => null, 'after' => $outcome->value],
                    'is_active' => ['before' => null, 'after' => $outcome->is_active],
                ]],
            ]);
        } else {
            $outcome = Outcome::create($data);

            // Log outcome creation
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'create',
                'entity_type' => 'Outcome',
                'entity_id' => (string)$outcome->id,
                'changes' => ['diff' => [
                    'value' => ['before' => null, 'after' => $outcome->value],
                ]],
            ]);
        }

        $this->closeOutcomeModal();
        session()->flash('message', 'Outcome saved successfully.');
    }

    public function deleteOutcome($id): void
    {
        $outcome = Outcome::findOrFail($id);

        // Snapshot outcome before deletion
        $snapshot = $outcome->only(['id', 'value', 'is_active']);

        $outcome->delete();

        // Log outcome deletion
        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => 'delete',
            'entity_type' => 'Outcome',
            'entity_id' => (string)$id,
            'changes' => ['deleted' => $snapshot],
        ]);

        session()->flash('message', 'Outcome deleted successfully.');
    }

    public function closeOutcomeModal(): void
    {
        $this->showOutcomeModal = false;
        $this->resetOutcomeForm();
    }

    private function resetOutcomeForm(): void
    {
        $this->reset(['outcomeValue', 'outcomeIsActive', 'editingOutcomeId']);
        $this->outcomeIsActive = true;
    }

    // --- Strategy Methods ---

    public function createStrategy(): void
    {
        $this->resetStrategyForm();
        $this->showStrategyModal = true;
    }

    public function editStrategy($id): void
    {
        $this->resetStrategyForm();
        $strategy = Strategy::findOrFail($id);
        $this->editingStrategyId = $id;
        $this->strategyValue = $strategy->value;
        $this->strategyIsActive = $strategy->is_active;
        $this->showStrategyModal = true;
    }

    public function saveStrategy(): void
    {
        $uniqueRule = 'required|integer|unique:strategies,value';
        if ($this->editingStrategyId) {
            $uniqueRule .= ',' . $this->editingStrategyId;
        }

        $this->validate([
            'strategyValue' => $uniqueRule,
            'strategyIsActive' => 'boolean',
        ]);

        $data = [
            'value' => $this->strategyValue,
            'is_active' => $this->strategyIsActive,
        ];

        if ($this->editingStrategyId) {
            $strategy = Strategy::find($this->editingStrategyId);
            $strategy->update($data);

            // Log strategy update
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'update',
                'entity_type' => 'Strategy',
                'entity_id' => (string)$strategy->id,
                'changes' => ['diff' => [
                    'value' => ['before' => null, 'after' => $strategy->value],
                    'is_active' => ['before' => null, 'after' => $strategy->is_active],
                ]],
            ]);
        } else {
            $strategy = Strategy::create($data);

            // Log strategy creation
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'create',
                'entity_type' => 'Strategy',
                'entity_id' => (string)$strategy->id,
                'changes' => ['diff' => [
                    'value' => ['before' => null, 'after' => $strategy->value],
                ]],
            ]);
        }

        $this->closeStrategyModal();
        session()->flash('message', 'Strategy saved successfully.');
    }

    public function deleteStrategy($id): void
    {
        $strategy = Strategy::findOrFail($id);

        // Snapshot strategy before deletion
        $snapshot = $strategy->only(['id', 'value', 'is_active']);

        $strategy->delete();

        // Log strategy deletion
        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => 'delete',
            'entity_type' => 'Strategy',
            'entity_id' => (string)$id,
            'changes' => ['deleted' => $snapshot],
        ]);

        session()->flash('message', 'Strategy deleted successfully.');
    }

    public function closeStrategyModal(): void
    {
        $this->showStrategyModal = false;
        $this->resetStrategyForm();
    }

    private function resetStrategyForm(): void
    {
        $this->reset(['strategyValue', 'strategyIsActive', 'editingStrategyId']);
        $this->strategyIsActive = true;
    }
}
