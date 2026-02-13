<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rejection_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained()->onDelete('cascade');
            $table->foreignId('rejected_by_user_id')->constrained('users');
            $table->foreignId('visible_to_user_id')->constrained('users');
            $table->text('note');
            $table->timestamps();

            $table->index('objective_id');
            $table->index('visible_to_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rejection_notes');
    }
};
