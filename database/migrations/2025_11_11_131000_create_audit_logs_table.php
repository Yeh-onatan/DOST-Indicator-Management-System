<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit Logs table
 *
 * Purpose
 * - Persist an immutable record of who changed what and when across the app.
 * - The `changes` column stores a structured payload so we can render
 *   field-by-field diffs in the UI (Before → After) and snapshots on delete.
 *
 * Conventions
 * - action: one of "create", "update", "delete" (you can extend if needed).
 * - entity_type: the model name (e.g., "Objective", "ReportingWindow").
 * - entity_id: string to avoid type coupling (IDs, UUIDs, composite keys, etc.).
 * - changes: JSON with either { diff: { field: {before,after}, ... } } or
 *            { deleted: <full model array> } for deletes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // User who performed the action (nullable for system tasks)
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            // The semantic action performed (create/update/delete)
            $table->string('action');

            // What model/table was affected (e.g., Objective)
            $table->string('entity_type');

            // Which specific record (string for flexibility: numeric IDs, UUIDs, etc.)
            $table->string('entity_id')->nullable();

            // Structured payload describing the change
            // - On update/create: { diff: { field: {before, after}, ... } }
            // - On delete: { deleted: <full model array> }
            $table->json('changes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
