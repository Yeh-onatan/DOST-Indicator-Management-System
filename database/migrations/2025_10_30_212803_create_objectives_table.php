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
    Schema::create('objectives', function (Blueprint $table) {
        $table->id();

        // ownership
        $table->foreignId('submitted_by_user_id')->constrained('users')->cascadeOnDelete();

        // core fields
        $table->string('objective_result', 500);
        $table->string('indicator', 500);
        $table->text('description')->nullable();
        $table->string('dost_agency', 255)->nullable();

        // performance tracking
        $table->string('baseline', 100)->nullable();
        $table->string('accomplishments', 100)->nullable();
        $table->string('annual_plan_targets', 100)->nullable();
        $table->string('target_period', 100)->nullable();
        $table->unsignedInteger('target_value')->nullable();

        // documentation & context
        $table->string('mov', 255)->nullable();
        $table->string('responsible_agency', 255)->nullable();
        $table->string('reporting_agency', 255)->nullable();
        $table->text('assumptions_risk')->nullable();
        $table->text('pc_secretariat_remarks')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objectives');
    }
};
