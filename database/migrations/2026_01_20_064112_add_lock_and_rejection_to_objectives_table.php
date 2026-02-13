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
    if (!Schema::hasColumn('objectives', 'is_locked')) {
        Schema::table('objectives', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('status'); // To lock the form
        });
    }

    if (!Schema::hasColumn('objectives', 'rejection_note')) {
        Schema::table('objectives', function (Blueprint $table) {
            $table->text('rejection_note')->nullable()->after('is_locked'); // Mandatory feedback
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('objectives', 'is_locked')) {
            Schema::table('objectives', function (Blueprint $table) {
                $table->dropColumn('is_locked');
            });
        }

        if (Schema::hasColumn('objectives', 'rejection_note')) {
            Schema::table('objectives', function (Blueprint $table) {
                $table->dropColumn('rejection_note');
            });
        }
    }
};
