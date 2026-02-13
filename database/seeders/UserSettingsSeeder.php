<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;

class UserSettingsSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->chunkById(200, function ($users) {
            foreach ($users as $user) {
                if (UserSetting::where('user_id', $user->id)->exists()) {
                    continue;
                }
                UserSetting::create([
                    'user_id'              => $user->id,
                    'theme'                => 'dark',
                    'density'              => 'comfortable',
                    'default_landing_page' => 'dashboard',
                    'table_page_size'      => 25,
                    'default_agency'       => null,
                    'default_year'         => null,
                    'default_quarter'      => null,
                    'number_format'        => '1,234.56',
                    'date_format'          => 'YYYY-MM-DD',
                    'progress_display'     => 'percent',
                    'notifications'        => [
                        'reminders' => [
                            'upcoming_deadline'     => true,
                            'overdue_submission'    => true,
                            'returned_for_revision' => true,
                        ],
                        'frequency' => 'daily',
                        'channel'   => ['email'],
                    ],
                    'export_presets' => [
                        'default_export_type' => 'PDF',
                        'include_charts'      => true,
                    ],
                ]);
            }
        });
    }
}
