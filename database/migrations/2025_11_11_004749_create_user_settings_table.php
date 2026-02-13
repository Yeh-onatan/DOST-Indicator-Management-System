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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();

            // Link settings to a specific user
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Personal Preferences
            $table->string('theme')->default('system'); // system|light|dark
            $table->string('density')->default('comfortable'); // comfortable|compact
            $table->string('default_landing_page')->default('dashboard'); // route name
            $table->unsignedSmallInteger('table_page_size')->default(25); // 10|25|50

            // Data & Display Defaults
            $table->string('default_agency')->nullable();
            $table->unsignedSmallInteger('default_year')->nullable();
            $table->tinyInteger('default_quarter')->nullable(); // 1-4
            $table->string('number_format')->default('1,234.56'); // or 1.234,56
            $table->string('date_format')->default('YYYY-MM-DD'); // or DD/MM/YYYY
            $table->string('progress_display')->default('percent'); // percent|value

            // Notifications and Export Presets (flexible, future-proof)
            $table->json('notifications')->nullable();
            $table->json('export_presets')->nullable();

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
