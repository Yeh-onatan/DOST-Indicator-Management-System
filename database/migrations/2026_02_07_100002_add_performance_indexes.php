<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes for frequently queried columns
 *
 * Based on query analysis, these columns are frequently used in WHERE clauses
 * and benefit from indexing for improved query performance.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Objectives table - frequently filtered by status
        Schema::table('objectives', function (Blueprint $table) {
            // Only create indexes if they don't exist
            $indexes = collect(DB::select("SHOW INDEX FROM objectives"))->pluck('Key_name')->toArray();

            if (!in_array('objectives_status_index', $indexes)) {
                $table->index('status', 'objectives_status_index');
            }
            if (!in_array('objectives_submitted_by_index', $indexes)) {
                $table->index('submitted_by_user_id', 'objectives_submitted_by_index');
            }
            if (!in_array('objectives_is_locked_index', $indexes)) {
                $table->index('is_locked', 'objectives_is_locked_index');
            }
            if (!in_array('objectives_region_status_index', $indexes)) {
                $table->index(['region_id', 'status'], 'objectives_region_status_index');
            }
            if (!in_array('objectives_office_status_index', $indexes)) {
                $table->index(['office_id', 'status'], 'objectives_office_status_index');
            }
        });

        // Users table - frequently filtered by role
        Schema::table('users', function (Blueprint $table) {
            $indexes = collect(DB::select("SHOW INDEX FROM users"))->pluck('Key_name')->toArray();

            if (!in_array('users_role_index', $indexes)) {
                $table->index('role', 'users_role_index');
            }
            if (!in_array('users_agency_id_index', $indexes)) {
                $table->index('agency_id', 'users_agency_id_index');
            }
            if (!in_array('users_office_id_index', $indexes)) {
                $table->index('office_id', 'users_office_id_index');
            }
            if (!in_array('users_region_id_index', $indexes)) {
                $table->index('region_id', 'users_region_id_index');
            }
        });

        // Audit logs table - frequently queried for history
        Schema::table('audit_logs', function (Blueprint $table) {
            $indexes = collect(DB::select("SHOW INDEX FROM audit_logs"))->pluck('Key_name')->toArray();

            if (!in_array('audit_logs_entity_index', $indexes)) {
                $table->index(['entity_type', 'entity_id'], 'audit_logs_entity_index');
            }
            if (!in_array('audit_logs_action_index', $indexes)) {
                $table->index('action', 'audit_logs_action_index');
            }
            if (!in_array('audit_logs_created_at_index', $indexes)) {
                $table->index('created_at', 'audit_logs_created_at_index');
            }
        });

        // Indicator histories table - frequently queried for objective history
        Schema::table('indicator_histories', function (Blueprint $table) {
            $indexes = collect(DB::select("SHOW INDEX FROM indicator_histories"))->pluck('Key_name')->toArray();

            if (!in_array('indicator_histories_objective_date_index', $indexes)) {
                $table->index(['objective_id', 'created_at'], 'indicator_histories_objective_date_index');
            }
        });
    }

    public function down(): void
    {
        // Objectives table
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropIndex('objectives_status_index');
            $table->dropIndex('objectives_submitted_by_index');
            $table->dropIndex('objectives_is_locked_index');
            $table->dropIndex('objectives_region_status_index');
            $table->dropIndex('objectives_office_status_index');
        });

        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
            $table->dropIndex('users_agency_id_index');
            $table->dropIndex('users_office_id_index');
            $table->dropIndex('users_region_id_index');
        });

        // Audit logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_entity_index');
            $table->dropIndex('audit_logs_action_index');
            $table->dropIndex('audit_logs_created_at_index');
        });

        // Indicator histories table
        Schema::table('indicator_histories', function (Blueprint $table) {
            $table->dropIndex('indicator_histories_objective_date_index');
        });
    }
};
