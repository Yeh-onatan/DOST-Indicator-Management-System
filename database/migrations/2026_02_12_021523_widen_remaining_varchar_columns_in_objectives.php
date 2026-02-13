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
            $table->text('dost_agency')->nullable()->change();
            $table->text('program_name')->nullable()->change();
            $table->text('indicator_type')->nullable()->change();
            $table->text('prexc_code')->nullable()->change();
            $table->text('agency_code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->string('dost_agency', 255)->nullable()->change();
            $table->string('program_name', 255)->nullable()->change();
            $table->string('indicator_type', 255)->nullable()->change();
            $table->string('prexc_code', 255)->nullable()->change();
            $table->string('agency_code', 255)->nullable()->change();
        });
    }
};
