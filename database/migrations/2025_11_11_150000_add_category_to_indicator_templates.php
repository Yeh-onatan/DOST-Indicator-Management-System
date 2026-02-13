<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicator_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('indicator_templates', 'category')) {
                $table->string('category', 40)->default('agency_specifics')->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('indicator_templates', function (Blueprint $table) {
            if (Schema::hasColumn('indicator_templates', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};

