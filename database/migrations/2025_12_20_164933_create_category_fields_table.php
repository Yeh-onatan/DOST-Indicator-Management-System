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
        Schema::create('category_fields', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('category_id')
                ->constrained('indicator_categories')
                ->onDelete('cascade');
            
            $table->string('field_name');
            $table->string('field_label'); // <--- THIS WAS MISSING
            $table->string('field_type');
            $table->string('db_column')->nullable(); // <--- THIS WAS MISSING
            
            $table->json('options')->nullable(); // Changed from 'field_options' to 'options' to match your Seeder
            
            $table->boolean('is_required')->default(false);
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true); // <--- THIS WAS MISSING
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_fields');
    }
};