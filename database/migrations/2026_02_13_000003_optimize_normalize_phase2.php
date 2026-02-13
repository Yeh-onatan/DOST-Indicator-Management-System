<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2 Database Optimization & Normalization
 *
 * 1. Remove duplicate indexes on objectives (saves storage & write overhead)
 * 2. Add missing FK constraints for user reference columns
 * 3. Add agency_id FK to objectives (normalize text-based agency references)
 * 4. Convert TEXT → VARCHAR for columns that don't need unlimited length
 * 5. Normalize ALL status values to lowercase (comprehensive)
 * 6. Remove TEXT-prefix indexes and add proper VARCHAR indexes
 * 7. Remove redundant single-column indexes covered by composites
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // 1. Remove DUPLICATE indexes on objectives
        //    4 exact duplicates wasting storage & slowing writes
        // ──────────────────────────────────────────────
        Schema::table('objectives', function (Blueprint $table) {
            // idx_objectives_category duplicates objectives_category_index (both on `category`)
            if ($this->hasIndex('objectives', 'idx_objectives_category')) {
                $table->dropIndex('idx_objectives_category');
            }
            // idx_objectives_status duplicates objectives_status_index (both on `status`)
            if ($this->hasIndex('objectives', 'idx_objectives_status')) {
                $table->dropIndex('idx_objectives_status');
            }
            // idx_objectives_sp_id duplicates objectives_sp_id_index (both on `sp_id`)
            if ($this->hasIndex('objectives', 'idx_objectives_sp_id')) {
                $table->dropIndex('idx_objectives_sp_id');
            }
            // objectives_submitted_by_index duplicates objectives_submitted_by_user_id_foreign
            if ($this->hasIndex('objectives', 'objectives_submitted_by_index')) {
                $table->dropIndex('objectives_submitted_by_index');
            }
        });

        // Also remove single-column indexes now covered by composites:
        // objectives_category_index is covered by objectives_status_category_index
        // objectives_status_index is covered by objectives_status_category_index, objectives_region_status_index, objectives_office_status_index
        Schema::table('objectives', function (Blueprint $table) {
            if ($this->hasIndex('objectives', 'objectives_category_index')) {
                $table->dropIndex('objectives_category_index');
            }
            if ($this->hasIndex('objectives', 'objectives_status_index')) {
                $table->dropIndex('objectives_status_index');
            }
        });

        // ──────────────────────────────────────────────
        // 2. Add missing FK constraints for user reference columns
        //    created_by, updated_by, owner_id have indexes but no FK
        // ──────────────────────────────────────────────
        Schema::table('objectives', function (Blueprint $table) {
            if (!$this->hasForeignKey('objectives', 'objectives_created_by_foreign')) {
                $table->foreign('created_by', 'objectives_created_by_foreign')
                    ->references('id')->on('users')->nullOnDelete();
            }
            if (!$this->hasForeignKey('objectives', 'objectives_updated_by_foreign')) {
                $table->foreign('updated_by', 'objectives_updated_by_foreign')
                    ->references('id')->on('users')->nullOnDelete();
            }
            if (!$this->hasForeignKey('objectives', 'objectives_owner_id_foreign')) {
                $table->foreign('owner_id', 'objectives_owner_id_foreign')
                    ->references('id')->on('users')->nullOnDelete();
            }
        });

        // ──────────────────────────────────────────────
        // 3. Add agency_id FK to objectives
        //    Normalizes the text-based dost_agency/agency_code references
        // ──────────────────────────────────────────────
        if (!Schema::hasColumn('objectives', 'agency_id')) {
            Schema::table('objectives', function (Blueprint $table) {
                $table->unsignedBigInteger('agency_id')->nullable()->after('chapter_id');
                $table->foreign('agency_id', 'objectives_agency_id_foreign')
                    ->references('id')->on('agencies')->nullOnDelete();
                $table->index('agency_id', 'objectives_agency_id_index');
            });

            // Migrate existing text data to agency_id
            // Match by agency code (most reliable identifier)
            DB::statement("
                UPDATE objectives o
                INNER JOIN agencies a ON (
                    o.agency_code = a.code
                    OR o.agency_code = a.acronym
                    OR o.dost_agency = a.name
                    OR o.dost_agency = a.acronym
                )
                SET o.agency_id = a.id
                WHERE o.agency_id IS NULL
                AND (o.agency_code IS NOT NULL OR o.dost_agency IS NOT NULL)
            ");
        }

        // ──────────────────────────────────────────────
        // 4. Convert TEXT → VARCHAR for columns with bounded data
        //    TEXT columns are stored off-page in InnoDB, hurting cache
        // ──────────────────────────────────────────────

        // These columns store short categorical/code data, not long text
        $varcharConversions = [
            'indicator_type'     => 500,   // e.g. 'output', 'outcome' - but some may be longer names
            'dost_agency'        => 255,   // agency name
            'agency_code'        => 50,    // agency code like 'DOST', 'PCAARRD'
            'prexc_code'         => 100,   // PREXC code
            'target_period'      => 100,   // e.g. '2027', '2022-2028'
            'responsible_agency' => 255,   // agency name
            'reporting_agency'   => 255,   // agency name
            'baseline'           => 500,   // baseline data
            'program_name'       => 500,   // program name
        ];

        Schema::table('objectives', function (Blueprint $table) use ($varcharConversions) {
            foreach ($varcharConversions as $column => $length) {
                if (Schema::hasColumn('objectives', $column)) {
                    $table->string($column, $length)->nullable()->change();
                }
            }
        });

        // Remove TEXT-prefix indexes (inefficient) and add proper ones
        Schema::table('objectives', function (Blueprint $table) {
            // Drop old TEXT-prefix indexes
            if ($this->hasIndex('objectives', 'objectives_prexc_code_index')) {
                $table->dropIndex('objectives_prexc_code_index');
            }
            if ($this->hasIndex('objectives', 'idx_objectives_indicator')) {
                $table->dropIndex('idx_objectives_indicator');
            }
            if ($this->hasIndex('objectives', 'idx_objectives_target_period')) {
                $table->dropIndex('idx_objectives_target_period');
            }
            if ($this->hasIndex('objectives', 'idx_objectives_admin_name')) {
                $table->dropIndex('idx_objectives_admin_name');
            }
        });

        // Add proper indexes on VARCHAR columns
        Schema::table('objectives', function (Blueprint $table) {
            if (!$this->hasIndex('objectives', 'objectives_prexc_code_idx')) {
                $table->index('prexc_code', 'objectives_prexc_code_idx');
            }
            if (!$this->hasIndex('objectives', 'objectives_agency_code_idx')) {
                $table->index('agency_code', 'objectives_agency_code_idx');
            }
        });

        // ──────────────────────────────────────────────
        // 5. Comprehensive status normalization
        //    Ensure ALL uppercase variants are converted
        // ──────────────────────────────────────────────
        DB::statement("UPDATE objectives SET status = LOWER(status) WHERE BINARY status != LOWER(status)");

        // Also normalize any status values in indicator_history if they exist
        if (Schema::hasTable('indicator_history') && Schema::hasColumn('indicator_history', 'action')) {
            DB::statement("UPDATE indicator_history SET action = LOWER(action) WHERE BINARY action != LOWER(action)");
        }

        // ──────────────────────────────────────────────
        // 6. Add composite index for agency queries
        // ──────────────────────────────────────────────
        Schema::table('objectives', function (Blueprint $table) {
            if (!$this->hasIndex('objectives', 'objectives_agency_status_idx')) {
                $table->index(['agency_id', 'status'], 'objectives_agency_status_idx');
            }
        });
    }

    public function down(): void
    {
        // Remove new indexes
        Schema::table('objectives', function (Blueprint $table) {
            if ($this->hasIndex('objectives', 'objectives_agency_status_idx')) {
                $table->dropIndex('objectives_agency_status_idx');
            }
            if ($this->hasIndex('objectives', 'objectives_agency_code_idx')) {
                $table->dropIndex('objectives_agency_code_idx');
            }
            if ($this->hasIndex('objectives', 'objectives_prexc_code_idx')) {
                $table->dropIndex('objectives_prexc_code_idx');
            }
        });

        // Re-add TEXT-prefix indexes
        Schema::table('objectives', function (Blueprint $table) {
            $table->index([DB::raw('prexc_code(768)')], 'objectives_prexc_code_index');
            $table->index([DB::raw('indicator(768)')], 'idx_objectives_indicator');
            $table->index([DB::raw('target_period(768)')], 'idx_objectives_target_period');
            $table->index([DB::raw('admin_name(768)')], 'idx_objectives_admin_name');
        });

        // Revert VARCHAR back to TEXT
        $textReversions = [
            'indicator_type', 'dost_agency', 'agency_code', 'prexc_code',
            'target_period', 'responsible_agency', 'reporting_agency',
            'baseline', 'program_name',
        ];
        Schema::table('objectives', function (Blueprint $table) use ($textReversions) {
            foreach ($textReversions as $column) {
                if (Schema::hasColumn('objectives', $column)) {
                    $table->text($column)->nullable()->change();
                }
            }
        });

        // Remove FK constraints
        Schema::table('objectives', function (Blueprint $table) {
            if ($this->hasForeignKey('objectives', 'objectives_created_by_foreign')) {
                $table->dropForeign('objectives_created_by_foreign');
            }
            if ($this->hasForeignKey('objectives', 'objectives_updated_by_foreign')) {
                $table->dropForeign('objectives_updated_by_foreign');
            }
            if ($this->hasForeignKey('objectives', 'objectives_owner_id_foreign')) {
                $table->dropForeign('objectives_owner_id_foreign');
            }
        });

        // Remove agency_id column
        if (Schema::hasColumn('objectives', 'agency_id')) {
            Schema::table('objectives', function (Blueprint $table) {
                $table->dropForeign('objectives_agency_id_foreign');
                $table->dropIndex('objectives_agency_id_index');
                $table->dropColumn('agency_id');
            });
        }

        // Re-add duplicate indexes
        Schema::table('objectives', function (Blueprint $table) {
            $table->index('category', 'idx_objectives_category');
            $table->index('status', 'idx_objectives_status');
            $table->index('sp_id', 'idx_objectives_sp_id');
            $table->index('submitted_by_user_id', 'objectives_submitted_by_index');
            $table->index('category', 'objectives_category_index');
            $table->index('status', 'objectives_status_index');
        });
    }

    /**
     * Check if a table has a specific index.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Check if a table has a specific foreign key constraint.
     */
    private function hasForeignKey(string $table, string $fkName): bool
    {
        $dbName = DB::getDatabaseName();
        $fks = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$dbName, $table, $fkName]
        );
        return count($fks) > 0;
    }
};
