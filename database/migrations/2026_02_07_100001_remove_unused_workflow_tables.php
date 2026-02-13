<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove unused workflow tables and columns
 *
 * CRITICAL: The workflow tables (workflows, workflow_stages) were created but never used.
 * The actual approval logic uses hardcoded status transitions in Objective.php instead.
 * This removes the dead code to reduce confusion and technical debt.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign key constraints first
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['starting_workflow_stage_id']);
            $table->dropIndex(['starting_workflow_stage_id']);
            $table->dropColumn('starting_workflow_stage_id');
        });

        Schema::table('objectives', function (Blueprint $table) {
            $table->dropForeign(['current_workflow_stage_id']);
            $table->dropColumn('current_workflow_stage_id');
        });

        // Drop the workflow tables
        Schema::dropIfExists('workflow_stages');
        Schema::dropIfExists('workflows');
    }

    public function down(): void
    {
        // Recreate tables (for rollback)
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('order_index')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('order_index')->nullable();
            $table->foreignId('required_permission_id')->nullable()->constrained('permissions')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('next_stage_id')->nullable()->constrained('workflow_stages')->nullOnDelete();
            $table->foreignId('return_stage_id')->nullable()->constrained('workflow_stages')->nullOnDelete();
            $table->timestamps();

            $table->unique(['slug', 'workflow_id']);
        });

        // Add back columns
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('starting_workflow_stage_id')->nullable()->after('description')->constrained('workflow_stages')->onDelete('set null');
            $table->index('starting_workflow_stage_id');
        });

        Schema::table('objectives', function (Blueprint $table) {
            $table->foreignId('current_workflow_stage_id')->nullable()->after('is_locked')->constrained('workflow_stages');
        });
    }
};
