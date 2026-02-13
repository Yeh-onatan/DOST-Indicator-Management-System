<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateUsersToRolesSeeder extends Seeder
{
    /**
     * Mapping of old role strings to new role names.
     */
    protected array $roleMapping = [
        'super_admin' => 'super_admin',
        'administrator' => 'administrator',
        'execom' => 'execom',
        'head_officer' => 'head_officer',
        'ro' => 'ro',
        'psto' => 'psto',
        'agency' => 'agency',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Migrating existing users to role_id...');

        $migratedCount = 0;
        $notFoundCount = 0;

        // Get all users who don't have a role_id set yet
        $users = User::whereNull('role_id')->get();

        foreach ($users as $user) {
            if (empty($user->role)) {
                $this->command->warn("User ID {$user->id} has no role set. Skipping.");
                $notFoundCount++;
                continue;
            }

            // Find the corresponding Role
            $roleName = $this->roleMapping[$user->role] ?? null;

            if (!$roleName) {
                $this->command->warn("No role mapping found for '{$user->role}' (User ID: {$user->id}). Skipping.");
                $notFoundCount++;
                continue;
            }

            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                $this->command->warn("Role '{$roleName}' not found in database. Skipping user ID {$user->id}.");
                $notFoundCount++;
                continue;
            }

            // Update the user with the new role_id
            $user->update(['role_id' => $role->id]);
            $migratedCount++;
        }

        $this->command->info("Migration complete:");
        $this->command->info("  - Migrated: {$migratedCount} users");
        $this->command->info("  - Skipped: {$notFoundCount} users");
    }
}
