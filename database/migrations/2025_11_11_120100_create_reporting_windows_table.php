<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_windows', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->boolean('lock_after_close')->default(false);
            $table->timestamps();
            $table->unique(['year','quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_windows');
    }
};

