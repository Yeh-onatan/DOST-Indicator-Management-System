<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Office;
use App\Models\DOSTAgency;
use App\Models\PhilippineRegion;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserManager extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $roleFilter = null;
    public ?string $agencyFilter = null;
    public ?int $officeFilter = null;
    public bool $showModal = false;
    public bool $isEditing = false;
    public string $sortBy = 'role_hierarchy'; // role_hierarchy, name, email, created_at
    public string $sortDirection = 'asc'; // asc, desc

    // Form fields
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'psto';
    public string $hoAssignmentType = 'office';
    public ?int $region_id = null;
    public ?int $office_id = null;
    public ?int $agency_id = null;

    public $filteredOffices = [];

    // --- RBAC Properties ---
    public string $activeTab = 'users';
    public ?Role $selectedRole = null;
    public array $selectedPermissions = [];

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($this->isEditing ? ',' . $this->editingId : ''),
            'username' => 'required|string|max:255|unique:users,username' . ($this->isEditing ? ',' . $this->editingId : ''),
            'password' => ($this->isEditing ? 'nullable' : 'required') . '|string|confirmed',
            'role' => 'required|in:super_admin,administrator,execom,head_officer,ro,psto,agency,ousec_ro,ousec_sts,ousec_rd',
            'region_id' => 'nullable|exists:regions,id',
            'office_id' => 'nullable|exists:offices,id',
            'agency_id' => 'nullable|exists:agencies,id',
        ];
    }

    public function mount()
    {
        if (!Auth::user()->isSA() && !Auth::user()->isAdministrator()) {
            abort(403, 'Unauthorized access');
        }
    }

    public function updatedRegionId($value)
    {
        if ($value) {
            $this->filteredOffices = Office::where('region_id', $value)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $this->filteredOffices = Office::whereNull('region_id')
                ->where('is_active', true)
                ->get();
        }
        $this->office_id = null;
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedRoleFilter() { $this->resetPage(); }
    public function updatedOfficeFilter() { $this->resetPage(); }
    public function updatedAgencyFilter() { $this->resetPage(); }
    public function updatedSortBy() { $this->resetPage(); }
    public function updatedSortDirection() { $this->resetPage(); }

    /**
     * Get the role order for sorting (hierarchical order)
     */
    public function getRoleOrder(): array
    {
        return [
            'super_admin' => 1,
            'administrator' => 2,
            'execom' => 3,
            'head_officer' => 4,
            'ousec_ro' => 5,
            'ousec_sts' => 6,
            'ousec_rd' => 7,
            'ro' => 8,
            'agency' => 9,
            'psto' => 10,
        ];
    }

    public function openCreate()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id)
    {
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username ?? '';
        $this->role = $user->role;
        $this->region_id = $user->region_id;
        $this->updatedRegionId($this->region_id);
        $this->office_id = $user->office_id;
        $this->agency_id = $user->agency_id;
        $this->password = '';
        $this->password_confirmation = '';

        // Set hoAssignmentType based on whether HO has agency_id
        if ($this->role === 'head_officer') {
            if ($this->agency_id) {
                $this->hoAssignmentType = 'agency';
            } else {
                // Has region_id or neither -> region assignment
                $this->hoAssignmentType = 'office';
            }
        } else {
            $this->hoAssignmentType = 'office';
        }

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'role' => $this->role,
        ];

        // Ensure cross-contamination doesn't happen
        if ($this->role === 'agency') {
            $data['agency_id'] = $this->agency_id;
            $data['region_id'] = null;
            $data['office_id'] = null;
        } elseif ($this->role === 'ro') {
            $data['region_id'] = $this->region_id;
            $data['office_id'] = null;
            $data['agency_id'] = null;
        } elseif ($this->role === 'psto') {
            $data['region_id'] = $this->region_id;
            $data['office_id'] = $this->office_id;
            $data['agency_id'] = null;
        } elseif ($this->role === 'head_officer') {
            if ($this->hoAssignmentType === 'agency' && $this->agency_id) {
                // HO assigned to agency
                $data['agency_id'] = $this->agency_id;
                $data['region_id'] = null;
                $data['office_id'] = null;
            } else {
                // HO assigned to region - only set region_id, not office_id
                $data['agency_id'] = null;
                $data['region_id'] = $this->region_id;
                $data['office_id'] = null;
            }
        } elseif ($this->role === 'ousec_ro') {
            // OUSEC-RO: assigned to region (handles regional/PSTO indicators)
            $data['region_id'] = $this->region_id;
            $data['office_id'] = null;
            $data['agency_id'] = null;
        } elseif (in_array($this->role, ['ousec_sts', 'ousec_rd'])) {
            // OUSEC-STS and OUSEC-RD: no location/agency assignment needed
            // They are assigned clusters in the database
            $data['region_id'] = null;
            $data['office_id'] = null;
            $data['agency_id'] = null;
        } else {
            // National roles (SA, Admin, EXECOM) don't have location/agency constraints
            $data['region_id'] = null;
            $data['office_id'] = null;
            $data['agency_id'] = null;
        }

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->isEditing) {
            $user = User::findOrFail($this->editingId);

            // Capture before state for audit
            $before = $user->only(['name', 'email', 'username', 'role', 'region_id', 'office_id', 'agency_id']);

            // Handle HO assignment changes
            if ($this->role === 'head_officer') {
                // If HO is being assigned to an agency
                if ($this->hoAssignmentType === 'agency' && $this->agency_id) {
                    // Log new HO assignment
                    \App\Services\AuditService::logAssignmentCreate(
                        'User',
                        $user->id,
                        'head_of_office',
                        DOSTAgency::find($this->agency_id)
                    );

                    DOSTAgency::where('id', $this->agency_id)->update(['head_user_id' => $user->id]);
                    // Clear previous agency assignment if different
                    if ($user->agency_id && $user->agency_id != $this->agency_id) {
                        // Log removal of previous HO assignment
                        \App\Services\AuditService::logAssignmentDelete(
                            'User',
                            $user->id,
                            'head_of_office',
                            DOSTAgency::find($user->agency_id)
                        );
                        DOSTAgency::where('id', $user->agency_id)->update(['head_user_id' => null]);
                    }
                }
                // If HO was previously assigned to an agency but is being changed to office
                elseif ($user->agency_id && $this->hoAssignmentType === 'office') {
                    // Log removal of previous HO assignment
                    \App\Services\AuditService::logAssignmentDelete(
                        'User',
                        $user->id,
                        'head_of_office',
                        DOSTAgency::find($user->agency_id)
                    );
                    DOSTAgency::where('id', $user->agency_id)->update(['head_user_id' => null]);
                }
            }

            $user->update($data);
            $user->refresh();

            // Calculate diff for audit
            $after = $user->only(['name', 'email', 'username', 'role', 'region_id', 'office_id', 'agency_id']);
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
            if (isset($diff['email'])) {
                $descriptionParts[] = "email from {$diff['email']['before']} to {$diff['email']['after']}";
            }
            if (isset($diff['role'])) {
                $descriptionParts[] = "role from {$diff['role']['before']} to {$diff['role']['after']}";
            }
            $description = count($descriptionParts) > 0
                ? 'Updated user ' . implode(', ', $descriptionParts)
                : 'Updated user';

            // Log user update via AuditService
            \App\Services\AuditService::log(
                'update',
                'User',
                $user->id,
                ['diff' => $diff],
                $description
            );

            // Log role change specifically if role changed
            if (isset($diff['role'])) {
                \App\Services\AuditService::logRoleChange(
                    $user,
                    $diff['role']['before'],
                    $diff['role']['after']
                );
            }

            $this->dispatch('toast', message: 'User updated successfully', type: 'success');
        } else {
            $data['email_verified_at'] = now();
            $user = User::create($data);

            // Log user creation via AuditService
            \App\Services\AuditService::log(
                'create',
                'User',
                $user->id,
                ['diff' => [
                    'name' => ['before' => null, 'after' => $user->name],
                    'email' => ['before' => null, 'after' => $user->email],
                    'username' => ['before' => null, 'after' => $user->username],
                    'role' => ['before' => null, 'after' => $user->role],
                ]],
                "Created user {$user->name} ({$user->username}) with role {$user->role}"
            );

            // If HO is assigned to an agency, update the agency's head_user_id
            if ($this->role === 'head_officer' && $this->hoAssignmentType === 'agency' && $this->agency_id) {
                // Log HO assignment
                \App\Services\AuditService::logAssignmentCreate(
                    'User',
                    $user->id,
                    'head_of_office',
                    DOSTAgency::find($this->agency_id)
                );
                DOSTAgency::where('id', $this->agency_id)->update(['head_user_id' => $user->id]);
            }

            $this->dispatch('toast', message: 'User created successfully', type: 'success');
        }

        $this->closeModal();
    }

    public function delete(int $id)
    {
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            $this->dispatch('toast', message: 'Cannot delete your own account', type: 'error');
            return;
        }

        // Snapshot user before deletion
        $snapshot = $user->only(['id', 'name', 'email', 'username', 'role', 'region_id', 'office_id', 'agency_id']);

        $user->delete();

        // Log user deletion via AuditService
        \App\Services\AuditService::log(
            'delete',
            'User',
            $id,
            ['deleted' => $snapshot],
            "Deleted user {$user->name} ({$user->username}) with role {$user->role}"
        );

        $this->dispatch('toast', message: 'User deleted successfully', type: 'success');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->username = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->role = 'psto';
        $this->hoAssignmentType = 'office';
        $this->region_id = null;
        $this->office_id = null;
        $this->agency_id = null;
        $this->filteredOffices = [];
        $this->resetValidation();
    }

    // --- RBAC Methods ---

    public function setActiveTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    /**
     * Load role permissions for editing.
     */
    public function selectRole(int $roleId)
    {
        $this->selectedRole = Role::with('permissions')->find($roleId);
        $this->selectedPermissions = $this->selectedRole->permissions->pluck('id')->toArray();
    }

    public function cancelRoleEdit()
    {
        $this->selectedRole = null;
        $this->selectedPermissions = [];
    }

    public function saveRolePermissions()
    {
        if (!$this->selectedRole) return;

        $this->selectedRole->syncPermissions($this->selectedPermissions);

        $this->dispatch('toast', message: 'Permissions updated successfully', type: 'success');
        $this->cancelRoleEdit();
    }

    public function render()
    {
        $query = User::with(['region', 'office', 'agency']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('username', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->roleFilter) {
            $query->where('role', $this->roleFilter);
        }

        if ($this->officeFilter) {
            $query->where('office_id', $this->officeFilter);
        }

        if ($this->agencyFilter === 'none') {
            $query->whereNull('agency_id');
        } elseif ($this->agencyFilter) {
            $query->where('agency_id', $this->agencyFilter);
        }

        // Apply sorting
        if ($this->sortBy === 'role_hierarchy') {
            $roleOrder = $this->getRoleOrder();
            $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
            $query->orderByRaw("FIELD(role, '" . implode("','", array_keys($roleOrder)) . "') $direction");
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return view('livewire.admin.user-manager', [
            'users' => $query->paginate(50),
            'regions' => PhilippineRegion::where('is_active', true)->orderBy('order_index')->get(),
            'offices' => Office::where('is_active', true)->orderBy('name')->get(),
            'agencies' => DOSTAgency::where('is_active', true)->orderBy('acronym')->get(),
            // RBAC data
            'roles' => Role::with('permissions')->orderBy('name')->get(),
            'permissionCategories' => Permission::orderBy('category')->orderBy('sort_order')->get()->groupBy('category'),
        ]);
    }
}
