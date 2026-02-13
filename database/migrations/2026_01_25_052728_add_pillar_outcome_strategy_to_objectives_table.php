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
            $table->foreignId('pillar_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outcome_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('strategy_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropForeign(['pillar_id']);
            $table->dropForeign(['outcome_id']);
            $table->dropForeign(['strategy_id']);
            $table->dropColumn(['pillar_id', 'outcome_id', 'strategy_id']);
        });
    }
};
