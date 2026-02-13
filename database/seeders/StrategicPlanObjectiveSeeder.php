<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Chapter;
use App\Models\Indicator as Objective;
use App\Models\User;

class StrategicPlanObjectiveSeeder extends Seeder
{
    /**
     * Seed Strategic Plan dummy entries into the objectives table
     * so they appear in the Strategic Plan grid.
     */
    public function run(): void
    {
        $now = now();

        // Ensure a Strategic Plan chapter exists (used to scope the grid)
        $chapter = Chapter::firstOrCreate(
            ['category' => 'strategic_plan'],
            [
                'code'        => 'SP',
                'title'       => 'Strategic Plan',
                'outcome'     => 'Strategic Plan',
                'description' => 'Auto-created container for Strategic Plan entries.',
                'sort_order'  => 1,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        // Pick an admin user if available; fall back to user id 1
        $userId = User::whereIn('role', ['super_admin', 'administrator'])->value('id') ?? 1;

        $rows = [
            [
                'sp_id'              => 1,
                'target_period'      => 2025,
                'objective_result'   => 'Outcome 1: Human well-being promoted',
                'description'        => 'Strategy sample for Outcome 1',
                'indicator'          => 'Percentage of projects completed on time',
                'baseline'           => '50%',
                'target_value'       => 65,
                'mov'                => 'Internal reports',
                'responsible_agency' => 'Planning',
                'reporting_agency'   => 'Planning',
            ],
            [
                'sp_id'              => 2,
                'target_period'      => 2025,
                'objective_result'   => 'Outcome 2: Wealth creation fostered',
                'description'        => 'Strategy sample for Outcome 2',
                'indicator'          => 'Percentage of clients rating assistance satisfactory or better',
                'baseline'           => '60%',
                'target_value'       => 75,
                'mov'                => 'Survey results',
                'responsible_agency' => 'Operations',
                'reporting_agency'   => 'Operations',
            ],
        ];

        foreach ($rows as $row) {
            Objective::updateOrCreate(
                [
                    'chapter_id' => $chapter->id,
                    'sp_id'      => $row['sp_id'],
                ],
                [
                    'chapter_id'           => $chapter->id,
                    'sp_id'                => $row['sp_id'],
                    'target_period'        => $row['target_period'],
                    'objective_result'     => $row['objective_result'],
                    'description'          => $row['description'],
                    'indicator'            => $row['indicator'],
                    'baseline'             => $row['baseline'],
                    'target_value'         => $row['target_value'],
                    'mov'                  => $row['mov'],
                    'responsible_agency'   => $row['responsible_agency'],
                    'reporting_agency'     => $row['reporting_agency'],
                    'submitted_by_user_id' => $userId,
                    'admin_name'           => 'Seeder',
                    'status'               => 'DRAFT',
                    'updated_by'           => $userId,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]
            );
        }
    }
}

