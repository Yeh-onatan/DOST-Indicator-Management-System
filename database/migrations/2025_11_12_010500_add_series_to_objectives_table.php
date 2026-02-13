<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            if (! Schema::hasColumn('objectives', 'accomplishments_series')) {
                $table->json('accomplishments_series')->nullable()->after('accomplishments');
            }
            if (! Schema::hasColumn('objectives', 'annual_plan_targets_series')) {
                $table->json('annual_plan_targets_series')->nullable()->after('annual_plan_targets');
            }
        });
    }

    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            if (Schema::hasColumn('objectives', 'accomplishments_series')) {
                $table->dropColumn('accomplishments_series');
            }
            if (Schema::hasColumn('objectives', 'annual_plan_targets_series')) {
                $table->dropColumn('annual_plan_targets_series');
            }
        });
    }
};

