<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DOSTAgency;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AgencyManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public bool $isEditing = false;

    // Form fields
    public ?int $editingId = null;
    public string $code = '';
    public string $agency_id = '';
    public string $name = '';
    public string $acronym = '';
    public string $description = '';
    public string $cluster = 'rdi';
    public bool $is_active = true;
    public ?int $head_user_id = null;

    public function mount()
    {
        if (!Auth::user()->isSA() && !Auth::user()->isAdministrator()) {
            abort(403, 'Unauthorized access');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id)
    {
        $agency = DOSTAgency::findOrFail($id);
        $this->editingId = $agency->id;
        $this->code = $agency->code;
        $this->agency_id = $agency->agency_id ?? '';
        $this->name = $agency->name;
        $this->acronym = $agency->acronym ?? '';
        $this->description = $agency->description ?? '';
        $this->cluster = $agency->cluster ?? 'rdi';
        $this->is_active = $agency->is_active;
        $this->head_user_id = $agency->head_user_id;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'code' => $this->isEditing ? 'required|string|max:50|unique:agencies,code,' . $this->editingId : 'required|string|max:50|unique:agencies,code',
            'agency_id' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'acronym' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'cluster' => 'required|string|in:council,rdi,ssi,collegial,main',
            'is_active' => 'boolean',
            'head_user_id' => 'nullable|integer|exists:users,id',
        ];

        $this->validate($rules);

        $data = [
            'code' => $this->code,
            'agency_id' => $this->agency_id ?: null,
            'name' => $this->name,
            'acronym' => $this->acronym,
            'description' => $this->description,
            'cluster' => $this->cluster,
            'is_active' => $this->is_active,
            'head_user_id' => $this->head_user_id,
        ];

        if ($this->isEditing) {
            $agency = DOSTAgency::findOrFail($this->editingId);

            // Capture before state for audit
            $before = $agency->only(['code', 'agency_id', 'name', 'acronym', 'description', 'cluster', 'is_active', 'head_user_id']);

            $agency->update($data);
            $agency->refresh();

            $after = $agency->only(['code', 'agency_id', 'name', 'acronym', 'description', 'cluster', 'is_active', 'head_user_id']);

            // Calculate diff for audit
            $diff = [];
            foreach ($after as $key => $value) {
                if ($before[$key] != $value) {
                    $diff[$key] = ['before' => $before[$key], 'after' => $value];
                }
            }

            // Generate human-readable description
            $descriptionParts = [];
            if (isset($diff['name'])) {
                $descriptionParts[] = "name from {$diff['name']['before']} to {$diff['name']['after']}";
            }
            if (isset($diff['code'])) {
                $descriptionParts[] = "code from {$diff['code']['before']} to {$diff['code']['after']}";
            }
            if (isset($diff['cluster'])) {
                $descriptionParts[] = "cluster from {$diff['cluster']['before']} to {$diff['cluster']['after']}";
            }
            $description = count($descriptionParts) > 0
                ? 'Updated agency ' . implode(', ', $descriptionParts)
                : 'Updated agency';

            // Log agency update via AuditService
            \App\Services\AuditService::log(
                'update',
                'Agency',
                $agency->id,
                ['diff' => $diff],
                $description
            );

            $this->dispatch('toast', message: 'Agency updated successfully', type: 'success');
        } else {
            $agency = DOSTAgency::create($data);

            // Log agency creation via AuditService
            \App\Services\AuditService::log(
                'create',
                'Agency',
                $agency->id,
                ['diff' => [
                    'name' => ['before' => null, 'after' => $agency->name],
                    'code' => ['before' => null, 'after' => $agency->code],
                    'acronym' => ['before' => null, 'after' => $agency->acronym],
                    'cluster' => ['before' => null, 'after' => $agency->cluster],
                ]],
                "Created agency {$agency->name} ({$agency->code}) in {$agency->cluster} cluster"
            );

            // Notify admins about new agency
            \App\Services\NotificationService::make()->notifyAgencyCreated($agency);

            $this->dispatch('toast', message: 'Agency created successfully', type: 'success');
        }

        $this->closeModal();
    }

    public function delete(int $id)
    {
        $agency = DOSTAgency::findOrFail($id);

        // Snapshot agency before deletion
        $snapshot = $agency->only(['id', 'code', 'agency_id', 'name', 'acronym', 'description', 'cluster', 'is_active']);

        $agency->delete();

        // Log agency deletion via AuditService
        \App\Services\AuditService::log(
            'delete',
            'Agency',
            $id,
            ['deleted' => $snapshot],
            "Deleted agency {$agency->name} ({$agency->code}) from {$agency->cluster} cluster"
        );

        $this->dispatch('toast', message: 'Agency deleted successfully', type: 'success');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->code = '';
        $this->agency_id = '';
        $this->name = '';
        $this->acronym = '';
        $this->description = '';
        $this->cluster = 'rdi';
        $this->is_active = true;
        $this->head_user_id = null;

        $this->resetValidation();
    }

    public function render()
    {
        $query = DOSTAgency::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                  ->orWhere('agency_id', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%')
                  ->orWhere('acronym', 'like', '%' . $this->search . '%');
            });
        }

        $agencies = $query->latest()->paginate(20);

        // Get users that can be HO (admin, super_admin)
        $hoUsers = \App\Models\User::whereIn('role', ['administrator', 'super_admin'])
            ->orderBy('name')
            ->get();

        return view('livewire.admin.agency-manager', compact('agencies', 'hoUsers'));
    }
}
