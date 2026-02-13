<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            if (!Schema::hasColumn('objectives', 'category')) {
                $table->string('category', 50)->nullable()->index()->after('chapter_id');
            }
            if (!Schema::hasColumn('objectives', 'agency_code')) {
                $table->string('agency_code', 50)->nullable()->after('dost_agency');
            }
            if (!Schema::hasColumn('objectives', 'program_name')) {
                $table->string('program_name', 255)->nullable()->after('description');
            }
            if (!Schema::hasColumn('objectives', 'indicator_type')) {
                $table->string('indicator_type', 50)->nullable()->after('program_name');
            }
            if (!Schema::hasColumn('objectives', 'prexc_code')) {
                $table->string('prexc_code', 100)->nullable()->after('indicator')->index();
            }
        });

        // Backfill category from chapters if available
        if (Schema::hasColumn('objectives', 'category') && Schema::hasColumn('objectives', 'chapter_id')) {
            $chapterCategories = DB::table('chapters')->select('id', 'category')->get();
            foreach ($chapterCategories as $chapter) {
                DB::table('objectives')
                    ->where('chapter_id', $chapter->id)
                    ->update(['category' => $chapter->category]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            if (Schema::hasColumn('objectives', 'prexc_code')) {
                $table->dropColumn('prexc_code');
            }
            if (Schema::hasColumn('objectives', 'indicator_type')) {
                $table->dropColumn('indicator_type');
            }
            if (Schema::hasColumn('objectives', 'program_name')) {
                $table->dropColumn('program_name');
            }
            if (Schema::hasColumn('objectives', 'agency_code')) {
                $table->dropColumn('agency_code');
            }
            if (Schema::hasColumn('objectives', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
