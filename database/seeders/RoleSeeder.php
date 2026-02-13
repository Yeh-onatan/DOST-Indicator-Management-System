<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Default roles matching the existing system.
     * Workflow stages are now hardcoded by ID for simplicity.
     */
    protected array $roles = [
        [
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'description' => 'Full system access with all permissions',
            'is_system' => true,
            'starting_workflow_stage_id' => null, // No workflow needed for super admin
        ],
        [
            'name' => 'administrator',
            'display_name' => 'Administrator',
            'description' => 'Administrative access to manage users and system settings',
            'is_system' => true,
            'starting_workflow_stage_id' => null, // No workflow needed for administrator
        ],
        [
            'name' => 'execom',
            'display_name' => 'EXECOM',
            'description' => 'Executive Committee member with approval privileges',
            'is_system' => true,
            'starting_workflow_stage_id' => null, // No workflow needed for execom
        ],
        [
            'name' => 'head_officer',
            'display_name' => 'Head Officer',
            'description' => 'Head of Office with HO-level approval authority',
            'is_system' => true,
            'starting_workflow_stage_id' => null, // HO is an approver, not a submitter
        ],
        [
            'name' => 'ro',
            'display_name' => 'Regional Officer',
            'description' => 'Regional Office role with RO-level approval authority',
            'is_system' => true,
            'starting_workflow_stage_id' => null, // RO is primarily an approver role
        ],
        [
            'name' => 'psto',
            'display_name' => 'PSTO',
            'description' => 'Provincial Office role with PSTO-level authority',
            'is_system' => true,
            'starting_workflow_stage_id' => null, // Workflow routing now handled by role-based logic
        ],
        [
            'name' => 'agency',
            'display_name' => 'Agency',
            'description' => 'Agency user role for submitting objectives',
            'is_system' => true,
            'starting_workflow_stage_id' => null, // Workflow routing now handled by role-based logic
        ],
        // --- OUSEC Roles ---
        [
            'name' => 'ousec_ro',
            'display_name' => 'OUSEC-RO',
            'description' => 'Office of the Undersecretary for Regional Operations - handles regional/PSTO indicators',
            'is_system' => true,
            'starting_workflow_stage_id' => null,
        ],
        [
            'name' => 'ousec_sts',
            'display_name' => 'OUSEC-STS',
            'description' => 'Office of the Undersecretary for S&T Services - handles SSI and Collegial agencies',
            'is_system' => true,
            'starting_workflow_stage_id' => null,
        ],
        [
            'name' => 'ousec_rd',
            'display_name' => 'OUSEC-RD',
            'description' => 'Office of the Undersecretary for R&D - handles Councils and RDIs',
            'is_system' => true,
            'starting_workflow_stage_id' => null,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                    'is_system' => $roleData['is_system'],
                    'starting_workflow_stage_id' => $roleData['starting_workflow_stage_id'],
                ]
            );

            $this->command->info("Created/Updated role: {$roleData['display_name']}");
        }

        $this->command->info('Roles configured successfully.');
    }
}
