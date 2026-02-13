<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX: Migration 2026_02_13_000002 targeted wrong table names:
 *   - "indicator_history"  (wrong) → actual table is "indicator_histories"
 *   - "password_history"   (wrong) → actual table is "password_histories"
 *
 * The Schema::hasTable() check silently failed, so deleted_at was never added.
 * This migration adds the missing deleted_at columns to the CORRECT table names.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fix indicator_histories — model uses SoftDeletes but column was never added
        if (Schema::hasTable('indicator_histories') && !Schema::hasColumn('indicator_histories', 'deleted_at')) {
            Schema::table('indicator_histories', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Fix password_histories — model uses SoftDeletes but column was never added
        if (Schema::hasTable('password_histories') && !Schema::hasColumn('password_histories', 'deleted_at')) {
            Schema::table('password_histories', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('indicator_histories') && Schema::hasColumn('indicator_histories', 'deleted_at')) {
            Schema::table('indicator_histories', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('password_histories') && Schema::hasColumn('password_histories', 'deleted_at')) {
            Schema::table('password_histories', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
