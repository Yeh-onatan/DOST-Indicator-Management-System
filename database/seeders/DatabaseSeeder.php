<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // NOTE: Roles and Permissions are now handled by migration
            // 2026_02_07_100000_seed_roles_and_permissions.php

            // 1. Base Users & Authentication
            UsersSeeder::class,

            // 2. Organization Structure
            AgenciesSeeder::class,

            // [UPDATED] Separated Regions and Offices
            RegionsSeeder::class, // Must run first
            OfficesSeeder::class, // Runs second, links to Regions

            // 3. Indicator Configuration
            IndicatorCategorySeeder::class,
            CategoryFieldSeeder::class,
            PDPChapterSeeder::class,

            // 4. Content / Objectives
            StrategicPlanObjectiveSeeder::class,

            // 5. User Specific Settings
            UserSettingsSeeder::class,
        ]);
    }
}