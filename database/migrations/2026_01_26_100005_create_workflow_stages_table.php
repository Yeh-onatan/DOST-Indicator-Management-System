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
        Schema::create('workflow_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('order_index')->default(1);
            $table->foreignId('required_permission_id')->nullable()->constrained('permissions');
            $table->foreignId('next_stage_id')->nullable()->constrained('workflow_stages');
            $table->foreignId('return_stage_id')->nullable()->constrained('workflow_stages');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('order_index');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_stages');
    }
};
