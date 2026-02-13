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
        // Update offices table to add hierarchical structure
        Schema::table('offices', function (Blueprint $table) {
            // 1. Add parent_office_id if it doesn't exist
            if (!Schema::hasColumn('offices', 'parent_office_id')) {
                $table->foreignId('parent_office_id')
                      ->nullable()
                      ->after('name')
                      ->constrained('offices')
                      ->onDelete('cascade');
            }

            // 2. REMOVED THE DESTRUCTIVE 'TYPE' BLOCK
            // We do NOT want to drop the column and make it an ENUM.
            // We want to keep the flexible string column created in the previous migration.
            
            // If for some reason 'type' is missing, ensure it exists as a string
            if (!Schema::hasColumn('offices', 'type')) {
                $table->string('type')->default('psto')->after('parent_office_id');
            }

            // 3. Add index for performance (check if index exists first to be safe, though optional)
            // It's usually safe to add index, but wrapping in try-catch or checking exists is safer in dev
            try {
                $table->index(['type', 'parent_office_id']);
            } catch (\Exception $e) {
                // Index might already exist, continue.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            // Safely drop foreign key and column
            if (Schema::hasColumn('offices', 'parent_office_id')) {
                $table->dropForeign(['parent_office_id']);
                $table->dropColumn('parent_office_id');
            }
            
            // Remove the index
            try {
                $table->dropIndex(['type', 'parent_office_id']);
            } catch (\Exception $e) {}
        });
    }
};