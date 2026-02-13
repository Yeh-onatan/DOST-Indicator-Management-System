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
            if (!Schema::hasColumn('users', 'region_id')) {
                $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            }
            if (!Schema::hasColumn('users', 'office_id')) {
                $table->foreignId('office_id')->nullable()->constrained('offices')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['office_id']);
            $table->dropColumn(['region_id', 'office_id']);
        });
    }
};
