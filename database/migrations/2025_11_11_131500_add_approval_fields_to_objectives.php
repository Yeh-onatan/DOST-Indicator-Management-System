<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            if (!Schema::hasColumn('objectives','status')) {
                $table->string('status')->nullable()->after('updated_by');
            }
            if (!Schema::hasColumn('objectives','review_notes')) {
                $table->text('review_notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('objectives','corrections_required')) {
                $table->json('corrections_required')->nullable()->after('review_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            if (Schema::hasColumn('objectives','corrections_required')) {
                $table->dropColumn('corrections_required');
            }
            if (Schema::hasColumn('objectives','review_notes')) {
                $table->dropColumn('review_notes');
            }
            if (Schema::hasColumn('objectives','status')) {
                $table->dropColumn('status');
            }
        });
    }
};

