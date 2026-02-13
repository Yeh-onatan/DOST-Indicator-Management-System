<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            // Add the new specific column for Output Indicator
            if (!Schema::hasColumn('objectives', 'output_indicator')) {
                $table->text('output_indicator')->nullable()->after('indicator');
            }
        });
    }

    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropColumn('output_indicator');
        });
    }
};