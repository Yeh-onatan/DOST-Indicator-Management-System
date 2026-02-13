<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Define all permissions organized by category.
     */
    protected array $permissions = [
        'users' => [
            ['name' => 'view_users', 'description' => 'View user list', 'sort_order' => 1],
            ['name' => 'create_users', 'description' => 'Create new users', 'sort_order' => 2],
            ['name' => 'edit_users', 'description' => 'Edit user accounts', 'sort_order' => 3],
            ['name' => 'delete_users', 'description' => 'Delete user accounts', 'sort_order' => 4],
            ['name' => 'manage_roles', 'description' => 'Manage role permissions', 'sort_order' => 5],
        ],
        'objectives' => [
            ['name' => 'view_objectives', 'description' => 'View strategic objectives', 'sort_order' => 10],
            ['name' => 'create_objectives', 'description' => 'Create objectives', 'sort_order' => 11],
            ['name' => 'edit_objectives', 'description' => 'Edit objectives', 'sort_order' => 12],
            ['name' => 'delete_objectives', 'description' => 'Delete objectives', 'sort_order' => 13],
            ['name' => 'submit_objectives', 'description' => 'Submit objectives for approval', 'sort_order' => 14],
        ],
        'approvals' => [
            ['name' => 'view_psto_approvals', 'description' => 'View PSTO level approvals', 'sort_order' => 20],
            ['name' => 'view_ro_approvals', 'description' => 'View RO level approvals', 'sort_order' => 21],
            ['name' => 'view_ho_approvals', 'description' => 'View HO level approvals', 'sort_order' => 22],
            ['name' => 'view_agency_approvals', 'description' => 'View Agency level approvals', 'sort_order' => 23],
            ['name' => 'view_admin_approvals', 'description' => 'View Admin level approvals', 'sort_order' => 24],
            ['name' => 'view_ousec_approvals', 'description' => 'View OUSEC level approvals', 'sort_order' => 25],
            ['name' => 'approve_psto', 'description' => 'Approve at PSTO level', 'sort_order' => 26],
            ['name' => 'approve_ro', 'description' => 'Approve at RO level', 'sort_order' => 27],
            ['name' => 'approve_ho', 'description' => 'Approve at HO level', 'sort_order' => 28],
            ['name' => 'approve_agency', 'description' => 'Approve at Agency level', 'sort_order' => 29],
            ['name' => 'approve_ousec', 'description' => 'Approve at OUSEC level', 'sort_order' => 30],
            ['name' => 'approve_admin', 'description' => 'Final admin approval', 'sort_order' => 31],
            ['name' => 'reject_objectives', 'description' => 'Reject/return objectives', 'sort_order' => 32],
        ],
        'agencies' => [
            ['name' => 'view_agencies', 'description' => 'View agency list', 'sort_order' => 40],
            ['name' => 'create_agencies', 'description' => 'Create new agencies', 'sort_order' => 41],
            ['name' => 'edit_agencies', 'description' => 'Edit agency details', 'sort_order' => 42],
            ['name' => 'delete_agencies', 'description' => 'Delete agencies', 'sort_order' => 43],
            ['name' => 'assign_agency_head', 'description' => 'Assign head of agency', 'sort_order' => 44],
        ],
        'offices' => [
            ['name' => 'view_offices', 'description' => 'View office list', 'sort_order' => 50],
            ['name' => 'create_offices', 'description' => 'Create new offices', 'sort_order' => 51],
            ['name' => 'edit_offices', 'description' => 'Edit office details', 'sort_order' => 52],
            ['name' => 'delete_offices', 'description' => 'Delete offices', 'sort_order' => 53],
            ['name' => 'assign_office_head', 'description' => 'Assign head of office', 'sort_order' => 54],
        ],
        'reports' => [
            ['name' => 'view_reports', 'description' => 'View system reports', 'sort_order' => 60],
            ['name' => 'export_reports', 'description' => 'Export report data', 'sort_order' => 61],
        ],
        'settings' => [
            ['name' => 'view_settings', 'description' => 'View system settings', 'sort_order' => 70],
            ['name' => 'edit_settings', 'description' => 'Edit system settings', 'sort_order' => 71],
            ['name' => 'view_audit_logs', 'description' => 'View audit logs', 'sort_order' => 72],
            ['name' => 'impersonate_users', 'description' => 'Impersonate other users', 'sort_order' => 73],
            ['name' => 'manage_workflow', 'description' => 'Configure approval workflow', 'sort_order' => 74],
        ],
        'notifications' => [
            ['name' => 'send_notifications', 'description' => 'Send system notifications', 'sort_order' => 80],
            ['name' => 'manage_notifications', 'description' => 'Manage notifications', 'sort_order' => 81],
        ],
    ];

    /**
     * Define default role permission assignments.
     */
    protected array $rolePermissions = [
        'super_admin' => 'all', // Super admin gets ALL permissions
        'administrator' => [
            'users', 'objectives', 'approvals', 'agencies', 'offices',
            'reports', 'settings', 'notifications'
            // All except impersonate
        ],
        'execom' => [
            'view_users', 'view_objectives', 'view_agencies', 'view_offices',
            'view_reports', 'view_settings',
            'view_psto_approvals', 'view_ro_approvals', 'view_ho_approvals',
            'view_ousec_approvals', 'view_admin_approvals',
            'approve_ro', 'approve_ho', 'reject_objectives',
        ],
        'head_officer' => [
            'view_objectives', 'create_objectives', 'edit_objectives', 'submit_objectives',
            'view_ho_approvals', 'approve_ho', 'reject_objectives',
            'view_reports',
        ],
        'ro' => [
            'view_objectives', 'create_objectives', 'edit_objectives', 'submit_objectives',
            'view_ro_approvals', 'approve_ro', 'reject_objectives',
            'view_reports',
        ],
        'psto' => [
            'view_objectives', 'create_objectives', 'edit_objectives', 'submit_objectives',
            'view_psto_approvals', 'approve_psto', 'reject_objectives',
            'view_reports',
        ],
        'agency' => [
            'view_objectives', 'create_objectives', 'edit_objectives', 'submit_objectives',
            'view_reports',
        ],
        // --- OUSEC Role Permissions ---
        'ousec_ro' => [
            'view_objectives', 'edit_objectives', 'submit_objectives',
            'view_ousec_approvals', 'approve_ousec', 'reject_objectives',
            'view_reports', 'view_agencies', 'view_offices',
        ],
        'ousec_sts' => [
            'view_objectives', 'edit_objectives', 'submit_objectives',
            'view_ousec_approvals', 'approve_ousec', 'reject_objectives',
            'view_reports', 'view_agencies',
        ],
        'ousec_rd' => [
            'view_objectives', 'edit_objectives', 'submit_objectives',
            'view_ousec_approvals', 'approve_ousec', 'reject_objectives',
            'view_reports', 'view_agencies',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create all permissions
        $createdPermissions = [];
        $sortOrder = 1;

        foreach ($this->permissions as $category => $categoryPermissions) {
            foreach ($categoryPermissions as $permissionData) {
                $permission = Permission::firstOrCreate(
                    ['name' => $permissionData['name']],
                    [
                        'description' => $permissionData['description'],
                        'category' => $category,
                        'is_active' => true,
                        'sort_order' => $permissionData['sort_order'] ?? $sortOrder++,
                    ]
                );
                $createdPermissions[$permission->name] = $permission->id;
            }
        }

        $this->command->info('Permissions created successfully.');

        // Assign permissions to roles
        $allPermissionIds = array_values($createdPermissions);

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                $this->command->warn("Role '{$roleName}' not found. Skipping permission assignment.");
                continue;
            }

            if ($permissions === 'all') {
                // Super admin gets all permissions
                $role->syncPermissions($allPermissionIds);
                $this->command->info("Assigned ALL permissions to role '{$roleName}'.");
            } else {
                // Get specific permission IDs
                $permissionIds = [];
                foreach ($permissions as $permissionName) {
                    if (isset($createdPermissions[$permissionName])) {
                        $permissionIds[] = $createdPermissions[$permissionName];
                    }
                }

                if (!empty($permissionIds)) {
                    $role->syncPermissions($permissionIds);
                    $this->command->info("Assigned " . count($permissionIds) . " permissions to role '{$roleName}'.");
                }
            }
        }

        $this->command->info('Permission assignments completed successfully.');
    }
}
