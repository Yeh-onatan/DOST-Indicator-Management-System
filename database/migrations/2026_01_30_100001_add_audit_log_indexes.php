<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add indexes to audit_logs for performance
 *
 * Improves query performance for common filtering patterns
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Actor filtering
            $table->index('actor_user_id');

            // Entity filtering (combined index for better performance)
            $table->index(['entity_type', 'entity_id']);

            // Action type filtering
            $table->index('action');

            // Date range filtering
            $table->index('created_at');

            // Batch operation grouping
            $table->index('batch_id');

            // Related entity filtering
            $table->index(['related_entity_type', 'related_entity_id']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['actor_user_id']);
            $table->dropIndex(['entity_type', 'entity_id']);
            $table->dropIndex('action');
            $table->dropIndex('created_at');
            $table->dropIndex('batch_id');
            $table->dropIndex(['related_entity_type', 'related_entity_id']);
        });
    }
};
