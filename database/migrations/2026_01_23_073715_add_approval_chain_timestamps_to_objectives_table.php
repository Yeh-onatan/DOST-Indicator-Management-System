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
            $table->timestamp('submitted_to_admin_at')->nullable()->after('submitted_to_ho_at');
            $table->timestamp('submitted_to_superadmin_at')->nullable()->after('submitted_to_admin_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropColumn(['submitted_to_admin_at', 'submitted_to_superadmin_at']);
        });
    }
};
