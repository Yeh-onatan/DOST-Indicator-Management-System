<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drops the legacy PREXC tables that have been replaced by the unified objectives table.
     * The system now uses objectives.category to distinguish between PREXC, PDP, and Strategic Plan indicators.
     */
    public function up(): void
    {
        // Drop prexc_histories first (has FK to prexc_indicators)
        Schema::dropIfExists('prexc_histories');

        // Drop prexc_indicators
        Schema::dropIfExists('prexc_indicators');
    }

    /**
     * Reverse the migrations.
     *
     * Recreates the legacy tables for rollback purposes.
     * Note: This will NOT restore any data that was in these tables.
     */
    public function down(): void
    {
        // Recreate prexc_indicators table
        Schema::create('prexc_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('prexc_code')->unique()->nullable();
            // Note: prexc_agency_id FK was removed by earlier migration (2025_12_11_020022)
            $table->unsignedBigInteger('prexc_agency_id')->nullable();
            $table->string('admin_name')->nullable();
            $table->string('program_name')->nullable();
            $table->enum('indicator_type', ['outcome', 'output']);
            $table->string('indicator_code')->nullable();
            $table->string('indicator_description');
            $table->text('definition')->nullable();
            $table->integer('year')->nullable();
            $table->string('baseline')->nullable();
            $table->string('target')->nullable();
            $table->string('actual')->nullable();
            $table->string('mov')->nullable();
            $table->string('responsible_unit')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('DRAFT');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Recreate prexc_histories table
        Schema::create('prexc_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prexc_indicator_id')->constrained('prexc_indicators')->cascadeOnDelete();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->timestamps();
        });
    }
};
