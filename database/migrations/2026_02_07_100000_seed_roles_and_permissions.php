<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed default roles and permissions
 *
 * CRITICAL: This data is required for the application to function.
 * Moved from seeders to migration because the app breaks without these.
 */
return new class extends Migration
{
    /**
     * All permissions organized by category
     */
    protected array $permissions = [
        ['name' => 'view_users', 'description' => 'View user list', 'category' => 'users', 'sort_order' => 1],
        ['name' => 'create_users', 'description' => 'Create new users', 'category' => 'users', 'sort_order' => 2],
        ['name' => 'edit_users', 'description' => 'Edit user accounts', 'category' => 'users', 'sort_order' => 3],
        ['name' => 'delete_users', 'description' => 'Delete user accounts', 'category' => 'users', 'sort_order' => 4],
        ['name' => 'manage_roles', 'description' => 'Manage role permissions', 'category' => 'users', 'sort_order' => 5],

        ['name' => 'view_objectives', 'description' => 'View strategic objectives', 'category' => 'objectives', 'sort_order' => 10],
        ['name' => 'create_objectives', 'description' => 'Create objectives', 'category' => 'objectives', 'sort_order' => 11],
        ['name' => 'edit_objectives', 'description' => 'Edit objectives', 'category' => 'objectives', 'sort_order' => 12],
        ['name' => 'delete_objectives', 'description' => 'Delete objectives', 'category' => 'objectives', 'sort_order' => 13],
        ['name' => 'submit_objectives', 'description' => 'Submit objectives for approval', 'category' => 'objectives', 'sort_order' => 14],

        ['name' => 'view_psto_approvals', 'description' => 'View PSTO level approvals', 'category' => 'approvals', 'sort_order' => 20],
        ['name' => 'view_ro_approvals', 'description' => 'View RO level approvals', 'category' => 'approvals', 'sort_order' => 21],
        ['name' => 'view_ho_approvals', 'description' => 'View HO level approvals', 'category' => 'approvals', 'sort_order' => 22],
        ['name' => 'view_agency_approvals', 'description' => 'View Agency level approvals', 'category' => 'approvals', 'sort_order' => 23],
        ['name' => 'view_admin_approvals', 'description' => 'View Admin level approvals', 'category' => 'approvals', 'sort_order' => 24],
        ['name' => 'view_ousec_approvals', 'description' => 'View OUSEC level approvals', 'category' => 'approvals', 'sort_order' => 25],
        ['name' => 'approve_psto', 'description' => 'Approve at PSTO level', 'category' => 'approvals', 'sort_order' => 26],
        ['name' => 'approve_ro', 'description' => 'Approve at RO level', 'category' => 'approvals', 'sort_order' => 27],
        ['name' => 'approve_ho', 'description' => 'Approve at HO level', 'category' => 'approvals', 'sort_order' => 28],
        ['name' => 'approve_agency', 'description' => 'Approve at Agency level', 'category' => 'approvals', 'sort_order' => 29],
        ['name' => 'approve_ousec', 'description' => 'Approve at OUSEC level', 'category' => 'approvals', 'sort_order' => 30],
        ['name' => 'approve_admin', 'description' => 'Final admin approval', 'category' => 'approvals', 'sort_order' => 31],
        ['name' => 'reject_objectives', 'description' => 'Reject/return objectives', 'category' => 'approvals', 'sort_order' => 32],

        ['name' => 'view_agencies', 'description' => 'View agency list', 'category' => 'agencies', 'sort_order' => 40],
        ['name' => 'create_agencies', 'description' => 'Create new agencies', 'category' => 'agencies', 'sort_order' => 41],
        ['name' => 'edit_agencies', 'description' => 'Edit agency details', 'category' => 'agencies', 'sort_order' => 42],
        ['name' => 'delete_agencies', 'description' => 'Delete agencies', 'category' => 'agencies', 'sort_order' => 43],
        ['name' => 'assign_agency_head', 'description' => 'Assign head of agency', 'category' => 'agencies', 'sort_order' => 44],

        ['name' => 'view_offices', 'description' => 'View office list', 'category' => 'offices', 'sort_order' => 50],
        ['name' => 'create_offices', 'description' => 'Create new offices', 'category' => 'offices', 'sort_order' => 51],
        ['name' => 'edit_offices', 'description' => 'Edit office details', 'category' => 'offices', 'sort_order' => 52],
        ['name' => 'delete_offices', 'description' => 'Delete offices', 'category' => 'offices', 'sort_order' => 53],
        ['name' => 'assign_office_head', 'description' => 'Assign head of office', 'category' => 'offices', 'sort_order' => 54],

        ['name' => 'view_reports', 'description' => 'View system reports', 'category' => 'reports', 'sort_order' => 60],
        ['name' => 'export_reports', 'description' => 'Export report data', 'category' => 'reports', 'sort_order' => 61],

        ['name' => 'view_settings', 'description' => 'View system settings', 'category' => 'settings', 'sort_order' => 70],
        ['name' => 'edit_settings', 'description' => 'Edit system settings', 'category' => 'settings', 'sort_order' => 71],
        ['name' => 'view_audit_logs', 'description' => 'View audit logs', 'category' => 'settings', 'sort_order' => 72],
        ['name' => 'impersonate_users', 'description' => 'Impersonate other users', 'category' => 'settings', 'sort_order' => 73],
        ['name' => 'manage_workflow', 'description' => 'Configure approval workflow', 'category' => 'settings', 'sort_order' => 74],

        ['name' => 'send_notifications', 'description' => 'Send system notifications', 'category' => 'notifications', 'sort_order' => 80],
        ['name' => 'manage_notifications', 'description' => 'Manage notifications', 'category' => 'notifications', 'sort_order' => 81],
    ];

    /**
     * All roles
     */
    protected array $roles = [
        ['name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'Full system access with all permissions'],
        ['name' => 'administrator', 'display_name' => 'Administrator', 'description' => 'Administrative access to manage users and system settings'],
        ['name' => 'execom', 'display_name' => 'EXECOM', 'description' => 'Executive Committee member with approval privileges'],
        ['name' => 'head_officer', 'display_name' => 'Head Officer', 'description' => 'Head of Office with HO-level approval authority'],
        ['name' => 'ro', 'display_name' => 'Regional Officer', 'description' => 'Regional Office role with RO-level approval authority'],
        ['name' => 'psto', 'display_name' => 'PSTO', 'description' => 'Provincial Office role with PSTO-level authority'],
        ['name' => 'agency', 'display_name' => 'Agency', 'description' => 'Agency user role for submitting objectives'],
        ['name' => 'ousec_ro', 'display_name' => 'OUSEC-RO', 'description' => 'Office of the Undersecretary for Regional Operations - handles regional/PSTO indicators'],
        ['name' => 'ousec_sts', 'display_name' => 'OUSEC-STS', 'description' => 'Office of the Undersecretary for S&T Services - handles SSI and Collegial agencies'],
        ['name' => 'ousec_rd', 'display_name' => 'OUSEC-RD', 'description' => 'Office of the Undersecretary for R&D - handles Councils and RDIs'],
    ];

    /**
     * Permission assignments per role
     */
    protected array $rolePermissions = [
        'super_admin' => 'all', // Gets ALL permissions
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

    public function up(): void
    {
        // Insert permissions (if not exists)
        $permissionIds = [];
        $now = now();

        foreach ($this->permissions as $permission) {
            $existing = DB::table('permissions')->where('name', $permission['name'])->first();

            if ($existing) {
                $permissionIds[$permission['name']] = $existing->id;
            } else {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'category' => $permission['category'],
                    'is_active' => true,
                    'sort_order' => $permission['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $permissionIds[$permission['name']] = $id;
            }
        }

        // Insert roles (if not exists)
        $roleIds = [];

        foreach ($this->roles as $role) {
            $existing = DB::table('roles')->where('name', $role['name'])->first();

            if ($existing) {
                $roleIds[$role['name']] = $existing->id;
            } else {
                $id = DB::table('roles')->insertGetId([
                    'name' => $role['name'],
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $roleIds[$role['name']] = $id;
            }
        }

        // Assign permissions to roles (if not exists)
        $allPermissionIds = array_values($permissionIds);

        foreach ($this->rolePermissions as $roleName => $permissions) {
            if (!isset($roleIds[$roleName])) {
                continue;
            }

            $roleId = $roleIds[$roleName];

            if ($permissions === 'all') {
                // Super admin gets all permissions
                foreach ($allPermissionIds as $permissionId) {
                    $exists = DB::table('permission_role')
                        ->where('permission_id', $permissionId)
                        ->where('role_id', $roleId)
                        ->first();

                    if (!$exists) {
                        DB::table('permission_role')->insert([
                            'permission_id' => $permissionId,
                            'role_id' => $roleId,
                        ]);
                    }
                }
            } else {
                // Get specific permission IDs by category or name
                foreach ($permissions as $permissionName) {
                    // Check if it's a category or specific permission
                    if (isset($permissionIds[$permissionName])) {
                        // Direct permission name
                        $exists = DB::table('permission_role')
                            ->where('permission_id', $permissionIds[$permissionName])
                            ->where('role_id', $roleId)
                            ->first();

                        if (!$exists) {
                            DB::table('permission_role')->insert([
                                'permission_id' => $permissionIds[$permissionName],
                                'role_id' => $roleId,
                            ]);
                        }
                    } else {
                        // Category - add all permissions in this category
                        foreach ($this->permissions as $perm) {
                            if ($perm['category'] === $permissionName) {
                                $exists = DB::table('permission_role')
                                    ->where('permission_id', $permissionIds[$perm['name']])
                                    ->where('role_id', $roleId)
                                    ->first();

                                if (!$exists) {
                                    DB::table('permission_role')->insert([
                                        'permission_id' => $permissionIds[$perm['name']],
                                        'role_id' => $roleId,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Clean up in reverse order
        DB::table('permission_role')->whereIn('role_id', DB::table('roles')->whereIn('name', array_column($this->roles, 'name'))->pluck('id'))->delete();
        DB::table('roles')->whereIn('name', array_column($this->roles, 'name'))->delete();
        DB::table('permissions')->whereIn('name', array_column($this->permissions, 'name'))->delete();
    }
};
