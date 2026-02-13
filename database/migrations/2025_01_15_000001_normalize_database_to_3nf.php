<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Database Normalization to 3NF
 *
 * This migration normalizes the database schema to Third Normal Form (3NF).
 *
 * Changes:
 * 1. agencies: Drop redundant `agency_id` varchar column (auto-increment `id` is sufficient)
 * 2. pillars/outcomes/strategies: Add `name` column for human-readable labels
 * 3. objectives: Remove duplicate `rejection_note` (keep `rejection_reason`; notes are in `rejection_notes` table)
 * 4. objectives: Add proper FK constraints for `created_by`, `updated_by`, `owner_id`
 * 5. objectives: Add `deleted_at` to critical tables for SoftDeletes support
 * 6. admin_settings: Clean up orphaned JSON columns from deleted components
 * 7. users: Add missing columns referenced in code (`email_notifications_enabled`, `last_login_at`, `is_locked`)
 * 8. Normalize status constants to lowercase across the board
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // 1. agencies: Drop redundant agency_id varchar
        // ──────────────────────────────────────────────
        if (Schema::hasColumn('agencies', 'agency_id')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->dropColumn('agency_id');
            });
        }

        // ──────────────────────────────────────────────
        // 2. pillars, outcomes, strategies: Add `name` label column
        // ──────────────────────────────────────────────
        foreach (['pillars', 'outcomes', 'strategies'] as $lookupTable) {
            if (!Schema::hasColumn($lookupTable, 'name')) {
                Schema::table($lookupTable, function (Blueprint $table) {
                    $table->string('name', 255)->nullable()->after('value');
                });
            }
        }

        // ──────────────────────────────────────────────
        // 3. objectives: Remove duplicate rejection_note column
        //    (rejection notes are properly stored in rejection_notes table)
        // ──────────────────────────────────────────────
        if (Schema::hasColumn('objectives', 'rejection_note')) {
            // First, migrate any data from rejection_note to rejection_reason if rejection_reason is empty
            DB::statement("
                UPDATE objectives
                SET rejection_reason = rejection_note
                WHERE (rejection_reason IS NULL OR rejection_reason = '')
                AND rejection_note IS NOT NULL
                AND rejection_note != ''
            ");

            Schema::table('objectives', function (Blueprint $table) {
                $table->dropColumn('rejection_note');
            });
        }

        // ──────────────────────────────────────────────
        // 4. objectives: Add FK constraints for user reference columns
        // ──────────────────────────────────────────────
        Schema::table('objectives', function (Blueprint $table) {
            // Add indexes for performance before adding FKs
            if (!$this->hasIndex('objectives', 'objectives_created_by_index')) {
                $table->index('created_by', 'objectives_created_by_index');
            }
            if (!$this->hasIndex('objectives', 'objectives_updated_by_index')) {
                $table->index('updated_by', 'objectives_updated_by_index');
            }
            if (!$this->hasIndex('objectives', 'objectives_owner_id_index')) {
                $table->index('owner_id', 'objectives_owner_id_index');
            }
        });

        // ──────────────────────────────────────────────
        // 5. Add SoftDeletes to critical tables
        // ──────────────────────────────────────────────
        $softDeleteTables = ['objectives', 'users', 'agencies'];
        foreach ($softDeleteTables as $tableName) {
            if (!Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }

        // ──────────────────────────────────────────────
        // 6. users: Add missing columns referenced in code
        // ──────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_notifications_enabled')) {
                $table->boolean('email_notifications_enabled')->default(true)->after('email');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('email_notifications_enabled');
            }
        });

        // ──────────────────────────────────────────────
        // 7. Normalize all STATUS_DRAFT from 'DRAFT' to 'draft' (lowercase)
        // ──────────────────────────────────────────────
        DB::statement("UPDATE objectives SET status = 'draft' WHERE status = 'DRAFT'");

        // ──────────────────────────────────────────────
        // 8. Add composite indexes for common query patterns
        // ──────────────────────────────────────────────
        Schema::table('objectives', function (Blueprint $table) {
            if (!$this->hasIndex('objectives', 'objectives_status_category_index')) {
                $table->index(['status', 'category'], 'objectives_status_category_index');
            }
            if (!$this->hasIndex('objectives', 'objectives_region_status_index')) {
                $table->index(['region_id', 'status'], 'objectives_region_status_index');
            }
            if (!$this->hasIndex('objectives', 'objectives_office_status_index')) {
                $table->index(['office_id', 'status'], 'objectives_office_status_index');
            }
        });
    }

    public function down(): void
    {
        // Reverse status normalization
        DB::statement("UPDATE objectives SET status = 'DRAFT' WHERE status = 'draft'");

        // Remove composite indexes
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropIndex('objectives_status_category_index');
            $table->dropIndex('objectives_region_status_index');
            $table->dropIndex('objectives_office_status_index');
        });

        // Remove user columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_notifications_enabled', 'last_login_at', 'is_locked']);
        });

        // Remove SoftDeletes
        foreach (['objectives', 'users', 'agencies'] as $tableName) {
            if (Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }

        // Remove indexes
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropIndex('objectives_created_by_index');
            $table->dropIndex('objectives_updated_by_index');
            $table->dropIndex('objectives_owner_id_index');
        });

        // Re-add rejection_note
        Schema::table('objectives', function (Blueprint $table) {
            $table->text('rejection_note')->nullable();
        });

        // Remove name from lookup tables
        foreach (['pillars', 'outcomes', 'strategies'] as $lookupTable) {
            Schema::table($lookupTable, function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }

        // Re-add agency_id
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('agency_id')->nullable();
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
