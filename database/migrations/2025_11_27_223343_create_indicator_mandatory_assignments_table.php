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
        if (!Schema::hasTable('indicator_mandatory_assignments')) {
            Schema::create('indicator_mandatory_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('objective_id')->constrained('objectives')->onDelete('cascade');
                $table->string('assignment_type'); // 'region', 'office', 'all'
                $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade');
                $table->foreignId('office_id')->nullable()->constrained('offices')->onDelete('cascade');
                $table->foreignId('agency_id')->nullable()->constrained('agencies')->onDelete('cascade');
                $table->timestamps();

                // Unique constraint to prevent duplicate assignments - NOW INCLUDING agency_id
                $table->unique(['objective_id', 'assignment_type', 'region_id', 'office_id', 'agency_id'], 'ind_mand_assign_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('indicator_mandatory_assignments')) {
            Schema::dropIfExists('indicator_mandatory_assignments');
        }
    }
};
