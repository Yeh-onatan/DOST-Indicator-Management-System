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
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('starting_workflow_stage_id')->nullable()->after('description')->constrained('workflow_stages')->onDelete('set null');
            $table->index('starting_workflow_stage_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['starting_workflow_stage_id']);
            $table->dropIndex(['starting_workflow_stage_id']);
            $table->dropColumn('starting_workflow_stage_id');
        });
    }
};
