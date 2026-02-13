<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Simple, easy-to-type password for all dev accounts
        $password = Hash::make('123'); 

        $users = [
            // 1. Super Admin
            [
                'name'     => 'Super Admin',
                'email'    => 'super@dost.gov.ph',
                'username' => 'super',  // Short & sweet
                'role'     => 'super_admin',
            ],
            // 2. Administrator
            [
                'name'     => 'Administrator',
                'email'    => 'admin@dost.gov.ph',
                'username' => 'admin',
                'role'     => 'administrator',
            ],
            // 3. Head of Office (renamed from Head Officer for clarity)
            [
                'name'     => 'Head of Office',
                'email'    => 'ho@dost.gov.ph',
                'username' => 'ho',
                'role'     => 'head_officer',
            ],
            // 4. Regional Office
            [
                'name'     => 'Regional Office II',
                'email'    => 'ro@dost.gov.ph',
                'username' => 'ro',
                'role'     => 'ro',
            ],
            // 5. PSTO (Provincial Office)
            [
                'name'     => 'PSTO Isabela',
                'email'    => 'psto@dost.gov.ph',
                'username' => 'psto',
                'role'     => 'psto',
            ],
            // 6. [NEW] Agency User
            [
                'name'     => 'TAPI User', // Example Agency
                'email'    => 'tapi@dost.gov.ph',
                'username' => 'agency',
                'role'     => 'agency',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['username' => $u['username']], // Check uniqueness by username
                [
                    'name'                         => $u['name'],
                    'email'                        => $u['email'],
                    'role'                         => $u['role'],
                    'password'                     => $password,
                    'email_notifications_enabled'  => true,
                    'is_locked'                    => false,
                ]
            );
        }
    }
}