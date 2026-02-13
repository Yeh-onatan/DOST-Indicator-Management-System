<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Office;
use App\Models\PhilippineRegion;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class OfficeManager extends Component
{
    use WithPagination;

    public $name, $code, $region_id, $head_user_id, $parent_office_id, $address;
    public $type = 'PSTO';
    public $editingId = null;
    public bool $showModal = false;
    public $search = '';
    public bool $is_active = true;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:offices,code,' . $this->editingId,
            'type' => 'required|in:HO,CO,RO,PSTO',
            'region_id' => 'nullable|exists:regions,id',
            'head_user_id' => 'nullable|exists:users,id',
            'parent_office_id' => 'nullable|exists:offices,id',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function updatedSearch() { $this->resetPage(); }

    public function render()
    {
        $query = Office::with(['region', 'head', 'parent']);
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
        }

        return view('livewire.admin.office-manager', [
            'offices' => $query->orderBy('type')->paginate(10),
            'regions' => PhilippineRegion::select('id', 'name', 'code')->get(),
            'users' => User::select('id', 'name')->get(),
            'parentOptions' => Office::select('id', 'name', 'type')->whereIn('type', ['HO', 'CO', 'RO'])->get(),
        ]);
    }

    public function create()
    {
        $this->reset(['name', 'code', 'region_id', 'head_user_id', 'parent_office_id', 'editingId', 'address']);
        $this->type = 'PSTO';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $office = Office::findOrFail($id);
        $this->editingId = $id;
        $this->name = $office->name;
        $this->code = $office->code;
        $this->type = $office->type;
        $this->region_id = $office->region_id;
        $this->head_user_id = $office->head_user_id;
        $this->parent_office_id = $office->parent_office_id;
        $this->address = $office->address ?? '';
        $this->is_active = $office->is_active ?? true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            $office = Office::find($this->editingId);

            // Capture before state for audit
            $before = $office->only(['name', 'code', 'type', 'region_id', 'head_user_id', 'parent_office_id', 'address', 'is_active']);

            $office->update([
                'name' => $this->name,
                'code' => $this->code,
                'type' => $this->type,
                'region_id' => $this->region_id ?: null,
                'head_user_id' => $this->head_user_id ?: null,
                'parent_office_id' => $this->parent_office_id ?: null,
                'address' => $this->address,
                'is_active' => $this->is_active,
            ]);

            $office->refresh();
            $after = $office->only(['name', 'code', 'type', 'region_id', 'head_user_id', 'parent_office_id', 'address', 'is_active']);

            // Calculate diff for audit
            $diff = [];
            foreach ($after as $key => $value) {
                if ($before[$key] != $value) {
                    $diff[$key] = ['before' => $before[$key], 'after' => $value];
                }
            }

            // Log office update
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'update',
                'entity_type' => 'Office',
                'entity_id' => (string)$office->id,
                'changes' => ['diff' => $diff],
            ]);

            $this->dispatch('toast', message: 'Office updated successfully', type: 'success');
        } else {
            $office = Office::create([
                'region_id' => $this->region_id,
                'code' => $this->code,
                'name' => $this->name,
                'type' => $this->type,
                'address' => $this->address,
                'is_active' => $this->is_active,
            ]);

            // Log office creation
            AuditLog::create([
                'actor_user_id' => auth()->id(),
                'action' => 'create',
                'entity_type' => 'Office',
                'entity_id' => (string)$office->id,
                'changes' => ['diff' => [
                    'name' => ['before' => null, 'after' => $office->name],
                    'code' => ['before' => null, 'after' => $office->code],
                    'type' => ['before' => null, 'after' => $office->type],
                    'region_id' => ['before' => null, 'after' => $office->region_id],
                ]],
            ]);

            // Notify admins about new office
            \App\Services\NotificationService::make()->notifyOfficeCreated($office);

            $this->dispatch('toast', message: 'Office created successfully', type: 'success');
        }

        $this->closeModal();
    }

    public function closeModal() { $this->showModal = false; }
}