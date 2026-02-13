<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SOC 2 & ISO 27001: Track security incidents for response and compliance
     */
    public function up(): void
    {
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_id', 50)->unique(); // Human-readable incident ID
            $table->string('severity')->index(); // critical, high, medium, low
            $table->string('type')->index(); // brute_force, sql_injection, xss, unauthorized_access, etc.
            $table->string('status')->default('open'); // open, investigating, resolved, closed
            $table->string('title');
            $table->text('description');
            $table->json('details')->nullable(); // Additional context, IPs, user agents, etc.
            $table->foreignId('affected_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('detected_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['severity', 'status']);
            $table->index(['type', 'detected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
    }
};
