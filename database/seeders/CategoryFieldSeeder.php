<?php

namespace Database\Seeders;

use App\Models\CategoryField;
use App\Models\IndicatorCategory;
use Illuminate\Database\Seeder;

class CategoryFieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            // --- STRATEGIC PLAN ---
            [
                'category_slug' => 'strategic_plan',
                'field_name' => 'pillar',
                'field_label' => 'Pillar',
                'field_type' => 'text',
                'db_column' => 'program_name',
                'is_required' => true,
                'display_order' => 1,
            ],
            [
                'category_slug' => 'strategic_plan',
                'field_name' => 'outcome',
                'field_label' => 'Outcome #',
                'field_type' => 'textarea',
                'db_column' => 'objective_result',
                'is_required' => true,
                'display_order' => 2,
            ],
            [
                'category_slug' => 'strategic_plan',
                'field_name' => 'strategy',
                'field_label' => 'Strategy',
                'field_type' => 'textarea',
                'db_column' => 'description',
                'is_required' => true,
                'display_order' => 3,
            ],
            [
                'category_slug' => 'strategic_plan',
                'field_name' => 'outcome_indicator',
                'field_label' => 'Outcome Indicator',
                'field_type' => 'textarea',
                'db_column' => 'indicator',
                'is_required' => false,
                'display_order' => 4,
            ],
            [
                'category_slug' => 'strategic_plan',
                'field_name' => 'output_indicator',
                'field_label' => 'Output Indicator',
                'field_type' => 'textarea',
                'db_column' => 'output_indicator', // Using the new column
                'is_required' => false,
                'display_order' => 5,
            ],

            // --- PREXC ---
            [
                'category_slug' => 'prexc',
                'field_name' => 'program_name',
                'field_label' => 'Program Name',
                'field_type' => 'text',
                'db_column' => 'program_name',
                'is_required' => true,
                'display_order' => 1,
            ],
            [
                'category_slug' => 'prexc',
                'field_name' => 'indicator_type',
                'field_label' => 'Indicator Type',
                'field_type' => 'select',
                'options' => ['outcome', 'output'],
                'db_column' => 'indicator_type',
                'is_required' => true,
                'display_order' => 2,
            ],
            
            // --- PDP ---
            [
                'category_slug' => 'pdp',
                'field_name' => 'chapter',
                'field_label' => 'Chapter',
                'field_type' => 'text',
                'db_column' => 'description',
                'is_required' => true,
                'display_order' => 1,
            ],
        ];

        // 1. Cleanup: Remove any field literally named "output" from previous attempts
        // This fixes the issue of "Output" appearing next to Outcome
        $stratCategory = IndicatorCategory::where('slug', 'strategic_plan')->first();
        if ($stratCategory) {
            CategoryField::where('category_id', $stratCategory->id)
                ->where('field_name', 'output') // The field we want to remove
                ->delete();
        }

        // 2. Create/Update Fields
        foreach ($fields as $fieldData) {
            $categorySlug = $fieldData['category_slug'];
            unset($fieldData['category_slug']);

            $category = IndicatorCategory::firstOrCreate(
                ['slug' => $categorySlug], 
                ['name' => ucfirst(str_replace('_', ' ', $categorySlug))]
            );

            CategoryField::updateOrCreate(
                [
                    'category_id' => $category->id,
                    'field_name' => $fieldData['field_name'],
                ],
                array_merge($fieldData, ['category_id' => $category->id, 'is_active' => true])
            );
        }
    }
}