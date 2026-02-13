<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table = 'indicator_mandatory_assignments';
        $isSqlite = DB::getDriverName() === 'sqlite';

        // Step 1: Safely drop foreign keys by COLUMN NAME (SQLite compatible)
        $this->dropForeignKeyIfExists($table, ['objective_id']);
        $this->dropForeignKeyIfExists($table, ['region_id']);
        $this->dropForeignKeyIfExists($table, ['office_id']);

        // 2. DROP OLD UNIQUE CONSTRAINT
        if ($this->indexExists($table, 'ind_mand_assign_unique')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropUnique('ind_mand_assign_unique');
            });
        }

        // 3. ADD AGENCY_ID
        if (!Schema::hasColumn($table, 'agency_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('agency_id')
                    ->nullable()
                    ->after('office_id')
                    ->constrained('agencies')
                    ->onDelete('cascade');
            });
        }

        // Step 4: Recreate the foreign keys
        Schema::table($table, function (Blueprint $table) {
            if (!$this->foreignKeyExists('indicator_mandatory_assignments', 'indicator_mandatory_assignments_objective_id_foreign')) {
                $table->foreign('objective_id')->references('id')->on('objectives')->onDelete('cascade');
            }
            if (!$this->foreignKeyExists('indicator_mandatory_assignments', 'indicator_mandatory_assignments_region_id_foreign')) {
                $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            }
            if (!$this->foreignKeyExists('indicator_mandatory_assignments', 'indicator_mandatory_assignments_office_id_foreign')) {
                $table->foreign('office_id')->references('id')->on('offices')->onDelete('cascade');
            }
        });

        // 5. CREATE NEW UNIQUE INDEX
        if (!$this->indexExists($table, 'ind_mand_assign_unique_v2')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unique(
                    ['objective_id', 'assignment_type', 'region_id', 'office_id', 'agency_id'], 
                    'ind_mand_assign_unique_v2'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = 'indicator_mandatory_assignments';

        if ($this->indexExists($table, 'ind_mand_assign_unique_v2')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropUnique('ind_mand_assign_unique_v2');
            });
        }

        if (Schema::hasColumn($table, 'agency_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['agency_id']);
                $table->dropColumn('agency_id');
            });
        }

        if (!$this->indexExists($table, 'ind_mand_assign_unique')) {
            Schema::table($table, function (Blueprint $table) {
                $table->unique(
                    ['objective_id', 'assignment_type', 'region_id', 'office_id'], 
                    'ind_mand_assign_unique'
                );
            });
        }
    }

    // --- DRIVER SAFE HELPER FUNCTIONS ---

    /**
     * Safely drops a foreign key by column name (SQLite compatible).
     */
    protected function dropForeignKeyIfExists(string $table, array $columns): void
    {
        // Get the foreign key name from the column
        $foreignKeys = Schema::getForeignKeys($table);
        
        foreach ($foreignKeys as $fk) {
            if ($fk['columns'] === $columns) {
                Schema::table($table, function (Blueprint $table) use ($columns) {
                    $table->dropForeign($columns); // ✅ Pass column array, not name
                });
                break;
            }
        }
    }

    /**
     * Driver-agnostic check for Foreign Keys.
     * Works on MySQL and SQLite (In-Memory Testing).
     */
    protected function foreignKeyExists(string $table, string $fkName): bool
    {
        $foreignKeys = Schema::getForeignKeys($table);
        
        foreach ($foreignKeys as $fk) {
            if ($fk['name'] === $fkName) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Driver-agnostic check for Indexes.
     * Works on MySQL and SQLite (In-Memory Testing).
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
    }
};