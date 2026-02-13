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
        Schema::table('users', function (Blueprint $table) {
            // OUSEC type column: null, 'ousec_ro', 'ousec_sts', 'ousec_rd'
            $table->string('ousec_type')->nullable()->after('role')->comment('OUSEC specialization type');

            // Assigned clusters for OUSEC-STS and OUSEC-RD (JSON array)
            $table->json('assigned_clusters')->nullable()->after('ousec_type')->comment('Agency clusters assigned to OUSEC user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ousec_type', 'assigned_clusters']);
        });
    }
};
