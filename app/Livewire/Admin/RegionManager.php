<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PhilippineRegion;
use App\Models\Office;
use App\Models\User;
use App\Models\AuditLog;

class RegionManager extends Component
{
    use WithPagination;

    // Form Properties
    public $name, $code, $order_index, $director_id;
    public $editingId = null;
    public $search = '';
    
    // Modal Visibility
    public bool $showModal = false;
    public bool $showAssignmentModal = false;
    
    // Assignment Properties
    public $selectedRegion = null;
    public array $selectedOffices = [];

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:regions,code,' . $this->editingId,
            'order_index' => 'nullable|integer',
            'director_id' => 'nullable|exists:users,id',
        ];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = PhilippineRegion::with(['director'])->withCount('offices');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.admin.region-manager', [
            'regions' => $query->orderBy('order_index')->paginate(17),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            // Only show offices that can be assigned (usually PSTOs)
            'availableOffices' => Office::select('id', 'name', 'code', 'region_id')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->order_index = (PhilippineRegion::max('order_index') ?? 0) + 1;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $region = PhilippineRegion::findOrFail($id);
        $this->editingId = $id;
        $this->name = $region->name;
        $this->code = $region->code;
        $this->order_index = $region->order_index;
        $this->director_id = $region->director_id;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();
        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'order_index' => $this->order_index,
            'director_id' => $this->director_id ?: null,
        ];

        if ($this->editingId) {
            $region = PhilippineRegion::find($this->editingId);

            // Capture before state for audit
            $before = $region->only(['name', 'code', 'order_index', 'director_id']);

            $region->update($data);
            $region->refresh();

            $after = $region->only(['name', 'code', 'order_index', 'director_id']);

            // Calculate diff for audit
            $diff = [];
            foreach ($after as $key => $value) {
                if ($before[$key] != $value) {
                    $diff[$key] = ['before' => $before[$key], 'after' => $value];
                }
            }

            // Log region update
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'update',
                'entity_type' => 'Region',
                'entity_id' => (string)$region->id,
                'changes' => ['diff' => $diff],
            ]);
        } else {
            $region = PhilippineRegion::create($data);

            // Log region creation
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'create',
                'entity_type' => 'Region',
                'entity_id' => (string)$region->id,
                'changes' => ['diff' => [
                    'name' => ['before' => null, 'after' => $region->name],
                    'code' => ['before' => null, 'after' => $region->code],
                ]],
            ]);
        }

        $this->closeModal();
        session()->flash('message', 'Region saved successfully.');
    }

    // --- Assignment Logic ---

    public function openAssignments($id)
    {
        $this->selectedRegion = PhilippineRegion::findOrFail($id);
        // Get IDs of offices currently linked to this region
        $this->selectedOffices = Office::where('region_id', $id)->pluck('id')->map(fn($id) => (string)$id)->toArray();
        $this->showAssignmentModal = true;
    }

    public function saveAssignments()
    {
        // Remove region link from offices that were previously in this region
        Office::where('region_id', $this->selectedRegion->id)->update(['region_id' => null]);

        // Link the selected offices to this region
        Office::whereIn('id', $this->selectedOffices)->update(['region_id' => $this->selectedRegion->id]);

        // Log bulk office reassignment
        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action' => 'update',
            'entity_type' => 'Region',
            'entity_id' => (string)$this->selectedRegion->id,
            'changes' => ['bulk_reassign' => [
                'offices_count' => count($this->selectedOffices),
                'region' => $this->selectedRegion->name,
            ]],
        ]);

        $this->showAssignmentModal = false;
        session()->flash('message', 'Offices assigned successfully.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset(['name', 'code', 'order_index', 'director_id', 'editingId']);
    }
}