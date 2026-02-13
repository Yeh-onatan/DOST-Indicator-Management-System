<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            // Add indexes for frequently queried columns
            $table->index('chapter_id', 'idx_objectives_chapter_id');
            $table->index('indicator', 'idx_objectives_indicator');
            $table->index('target_period', 'idx_objectives_target_period');
            $table->index('admin_name', 'idx_objectives_admin_name');
            $table->index('category', 'idx_objectives_category');
            $table->index('status', 'idx_objectives_status');
            $table->index('sp_id', 'idx_objectives_sp_id');

            // Composite index for common query patterns
            $table->index(['chapter_id', 'category'], 'idx_objectives_chapter_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropIndex('idx_objectives_chapter_id');
            $table->dropIndex('idx_objectives_indicator');
            $table->dropIndex('idx_objectives_target_period');
            $table->dropIndex('idx_objectives_admin_name');
            $table->dropIndex('idx_objectives_category');
            $table->dropIndex('idx_objectives_status');
            $table->dropIndex('idx_objectives_sp_id');
            $table->dropIndex('idx_objectives_chapter_category');
        });
    }
};
