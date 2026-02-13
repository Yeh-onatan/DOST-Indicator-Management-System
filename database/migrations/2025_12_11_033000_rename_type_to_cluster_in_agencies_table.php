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
        Schema::table('agencies', function (Blueprint $table) {
            if (Schema::hasColumn('agencies', 'type') && ! Schema::hasColumn('agencies', 'cluster')) {
                $table->renameColumn('type', 'cluster');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (Schema::hasColumn('agencies', 'cluster') && ! Schema::hasColumn('agencies', 'type')) {
                $table->renameColumn('cluster', 'type');
            }
        });
    }
};
