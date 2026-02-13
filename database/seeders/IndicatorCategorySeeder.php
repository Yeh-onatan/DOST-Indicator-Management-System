<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IndicatorCategory;

class IndicatorCategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'slug' => 'strategic_plan',
                'name' => 'Strategic Plan',
                'description' => 'Strategic initiatives aligned with the unified plan.',
                'requires_chapter' => true,
                'display_order' => 1,
            ],
            [
                'slug' => 'pdp',
                'name' => 'PDP',
                'description' => 'Philippine Development Plan indicators.',
                'requires_chapter' => true,
                'display_order' => 2,
            ],
            [
                'slug' => 'prexc',
                'name' => 'PREXC',
                'description' => 'Program Expenditure Classification indicators.',
                'requires_chapter' => false,
                'display_order' => 3,
            ],
            [
                'slug' => 'agency_specifics',
                'name' => 'Agency Specifics',
                'description' => 'Agency-specific indicators and initiatives.',
                'requires_chapter' => false,
                'display_order' => 4,
            ],
        ];

        foreach ($defaults as $data) {
            IndicatorCategory::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'is_active' => true,
                    'is_mandatory' => true,
                    'created_by' => 1, // System user
                ])
            );
        }
    }
}
