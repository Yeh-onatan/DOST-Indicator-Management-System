<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remove the 3H indicator category entirely.
 * - Soft-deletes any objectives with category '3_h'
 * - Removes the '3H' row from indicator_categories
 * - Removes any chapters linked to category '3_h'
 */
return new class extends Migration
{
    public function up(): void
    {
        // Soft-delete any objectives with category 3_h
        DB::table('objectives')
            ->where('category', '3_h')
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        // Also handle case-variations: 3_H, 3h
        DB::table('objectives')
            ->whereIn('category', ['3_H', '3h'])
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        // Delete chapters linked to 3_h category
        if (\Illuminate\Support\Facades\Schema::hasTable('chapters')) {
            DB::table('chapters')->whereIn('category', ['3_h', '3_H', '3h'])->delete();
        }

        // Delete indicator_templates linked to 3_h
        if (\Illuminate\Support\Facades\Schema::hasTable('indicator_templates')) {
            DB::table('indicator_templates')->whereIn('category', ['3_h', '3_H', '3h'])->delete();
        }

        // Delete the 3H category from indicator_categories
        DB::table('indicator_categories')->whereIn('slug', ['3_H', '3_h', '3h'])->delete();
    }

    public function down(): void
    {
        // Re-create the 3H category
        DB::table('indicator_categories')->insert([
            'name' => '3H',
            'slug' => '3_H',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Un-soft-delete objectives (restoring from trash)
        DB::table('objectives')
            ->whereIn('category', ['3_h', '3_H', '3h'])
            ->update(['deleted_at' => null]);
    }
};
