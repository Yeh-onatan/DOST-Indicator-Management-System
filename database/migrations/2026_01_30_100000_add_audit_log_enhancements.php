<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add audit log enhancements for better tracking
 *
 * Adds fields for:
 * - Related entity tracking (e.g., agency name when HO is assigned)
 * - Human-readable descriptions
 * - Batch operation grouping
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Related entity for displaying contextual information
            // Example: When assigning an HO, store which agency was affected
            $table->string('related_entity_type')->nullable()->after('entity_id');
            $table->string('related_entity_id')->nullable()->after('related_entity_type');

            // Human-readable description of what changed
            // Useful for quick overview without parsing changes JSON
            $table->text('description')->nullable()->after('changes');

            // Batch ID for grouping bulk operations
            // All entries in a bulk operation share the same batch_id
            $table->string('batch_id')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['related_entity_type', 'related_entity_id', 'description', 'batch_id']);
        });
    }
};
