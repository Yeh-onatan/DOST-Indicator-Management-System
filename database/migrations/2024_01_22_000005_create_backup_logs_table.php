<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ISO 27001 & SOC 2: Backup verification and monitoring
     */
    public function up(): void
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('backup_type'); // database, files, full
            $table->string('status'); // success, failed, warning
            $table->string('location')->nullable(); // s3, local, backup service name
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('details')->nullable(); // Tables backed up, file count, etc.
            $table->string('checksum')->nullable(); // For integrity verification
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();

            $table->index(['backup_type', 'started_at']);
            $table->index(['status', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
