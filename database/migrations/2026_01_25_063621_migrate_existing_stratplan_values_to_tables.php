<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Migrate Pillars (from program_name column)
        $pillarValues = DB::table('objectives')
            ->whereNotNull('program_name')
            ->where('program_name', '!=', '')
            ->distinct()
            ->pluck('program_name');

        foreach ($pillarValues as $value) {
            // Check if it's numeric or has a numeric pattern
            if (is_numeric($value) || preg_match('/^\d+$/', $value)) {
                $existingId = DB::table('pillars')->where('value', (int)$value)->value('id');
                if (!$existingId) {
                    DB::table('pillars')->insert(['value' => (int)$value, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }

        // 2. Migrate Outcomes (from objective_result column)
        $outcomeValues = DB::table('objectives')
            ->whereNotNull('objective_result')
            ->where('objective_result', '!=', '')
            ->distinct()
            ->pluck('objective_result');

        foreach ($outcomeValues as $value) {
            // Check if it's numeric or has a numeric pattern
            if (is_numeric($value) || preg_match('/^\d+$/', $value)) {
                $existingId = DB::table('outcomes')->where('value', (int)$value)->value('id');
                if (!$existingId) {
                    DB::table('outcomes')->insert(['value' => (int)$value, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }

        // 3. Migrate Strategies (from description column - looking for patterns)
        $strategyValues = DB::table('objectives')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->distinct()
            ->pluck('description');

        foreach ($strategyValues as $value) {
            // Check if it's a single numeric value
            if (preg_match('/^\d+$/', trim($value))) {
                $existingId = DB::table('strategies')->where('value', (int)$value)->value('id');
                if (!$existingId) {
                    DB::table('strategies')->insert(['value' => (int)$value, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }

        // 4. Now link objectives to the new pillar_id, outcome_id, strategy_id
        $objectives = DB::table('objectives')
            ->whereNotNull('program_name')
            ->where('program_name', '!=', '')
            ->get();

        foreach ($objectives as $objective) {
            // Find matching pillar by value
            if (is_numeric($objective->program_name) || preg_match('/^\d+$/', $objective->program_name)) {
                $pillar = DB::table('pillars')->where('value', (int)$objective->program_name)->first();
                if ($pillar) {
                    DB::table('objectives')->where('id', $objective->id)->update(['pillar_id' => $pillar->id]);
                }
            }
        }

        $objectives = DB::table('objectives')
            ->whereNotNull('objective_result')
            ->where('objective_result', '!=', '')
            ->get();

        foreach ($objectives as $objective) {
            // Find matching outcome by value
            if (is_numeric($objective->objective_result) || preg_match('/^\d+$/', $objective->objective_result)) {
                $outcome = DB::table('outcomes')->where('value', (int)$objective->objective_result)->first();
                if ($outcome) {
                    DB::table('objectives')->where('id', $objective->id)->update(['outcome_id' => $outcome->id]);
                }
            }
        }

        $objectives = DB::table('objectives')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->get();

        foreach ($objectives as $objective) {
            // Find matching strategy by value
            $desc = trim($objective->description);
            if (preg_match('/^\d+$/', $desc)) {
                $strategy = DB::table('strategies')->where('value', (int)$desc)->first();
                if ($strategy) {
                    DB::table('objectives')->where('id', $objective->id)->update(['strategy_id' => $strategy->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key references
        DB::table('objectives')->update(['pillar_id' => null, 'outcome_id' => null, 'strategy_id' => null]);

        // Optionally: Delete created pillars, outcomes, strategies
        // Uncomment if you want to remove the migrated values
        // DB::table('pillars')->truncate();
        // DB::table('outcomes')->truncate();
        // DB::table('strategies')->truncate();
    }
};
