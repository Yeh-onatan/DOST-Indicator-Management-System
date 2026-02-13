<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicator_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('indicator_templates', 'chapter_id')) {
                $table->foreignId('chapter_id')->nullable()->after('category')->constrained('chapters')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('indicator_templates', function (Blueprint $table) {
            if (Schema::hasColumn('indicator_templates', 'chapter_id')) {
                $table->dropConstrainedForeignId('chapter_id');
            }
        });
    }
};

