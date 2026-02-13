<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add soft deletes to critical data models for compliance and data recovery.
     * SOC 2, ISO 27001: Audit logs and evidence must be retained and not permanently deleted.
     *
     * Tables: proofs, audit_logs, indicator_history, rejection_notes, password_history, notifications
     */
    public function up(): void
    {
        // Add deleted_at to proofs (compliance evidence)
        if (Schema::hasTable('proofs') && !Schema::hasColumn('proofs', 'deleted_at')) {
            Schema::table('proofs', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at to audit_logs (audit trail - compliance critical)
        if (Schema::hasTable('audit_logs') && !Schema::hasColumn('audit_logs', 'deleted_at')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at to indicator_history (historical changes)
        if (Schema::hasTable('indicator_history') && !Schema::hasColumn('indicator_history', 'deleted_at')) {
            Schema::table('indicator_history', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at to rejection_notes (feedback history)
        if (Schema::hasTable('rejection_notes') && !Schema::hasColumn('rejection_notes', 'deleted_at')) {
            Schema::table('rejection_notes', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at to password_history (PCI DSS requirement)
        if (Schema::hasTable('password_history') && !Schema::hasColumn('password_history', 'deleted_at')) {
            Schema::table('password_history', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add deleted_at to notifications (user history)
        if (Schema::hasTable('notifications') && !Schema::hasColumn('notifications', 'deleted_at')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proofs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('indicator_history', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('rejection_notes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('password_history', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
