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
            $table->text('mov')->nullable()->change();
            $table->text('indicator')->nullable()->change();
            $table->text('objective_result')->nullable()->change();
            $table->text('baseline')->nullable()->change();
            $table->text('target_period')->nullable()->change();
            $table->text('responsible_agency')->nullable()->change();
            $table->text('reporting_agency')->nullable()->change();
            $table->text('accomplishments')->nullable()->change(); // Just in case
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->string('mov', 255)->nullable()->change();
            $table->string('indicator', 500)->nullable()->change();
            $table->string('objective_result', 500)->nullable()->change();
            $table->string('baseline', 100)->nullable()->change();
            $table->string('target_period', 100)->nullable()->change();
            $table->string('responsible_agency', 255)->nullable()->change();
            $table->string('reporting_agency', 255)->nullable()->change();
            $table->string('accomplishments', 100)->nullable()->change();
        });
    }
};
