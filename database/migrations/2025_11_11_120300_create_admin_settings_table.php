<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->json('notifications_sla')->nullable();
            $table->json('data_quality_rules')->nullable();
            $table->string('pdf_logo_path')->nullable();
            $table->text('pdf_header')->nullable();
            $table->text('pdf_footer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};

