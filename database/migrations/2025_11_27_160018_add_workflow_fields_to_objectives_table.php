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
        Schema::table('objectives', function (Blueprint $table) {
            // Add mandatory flag
            $table->boolean('is_mandatory')->default(false)->after('status');

            // Add region and office tracking
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null')->after('submitted_by_user_id');
            $table->foreignId('office_id')->nullable()->constrained('offices')->onDelete('set null')->after('region_id');

            // Add workflow tracking
            $table->timestamp('submitted_to_ro_at')->nullable()->after('updated_by');
            $table->timestamp('submitted_to_ho_at')->nullable()->after('submitted_to_ro_at');
            $table->timestamp('approved_at')->nullable()->after('submitted_to_ho_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('rejected_at');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null')->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['office_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'is_mandatory',
                'region_id',
                'office_id',
                'submitted_to_ro_at',
                'submitted_to_ho_at',
                'approved_at',
                'rejected_at',
                'approved_by',
                'rejected_by',
                'rejection_reason'
            ]);
        });
    }
};
