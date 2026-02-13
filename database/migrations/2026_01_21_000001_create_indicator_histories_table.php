<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained('objectives')->cascadeOnDelete();
            $table->string('action'); // create, submit, approve, reject, return, update
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('rejection_note')->nullable(); // For rejection actions
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->timestamps();

            $table->index('objective_id');
            $table->index('action');
            $table->index('actor_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_histories');
    }
};
