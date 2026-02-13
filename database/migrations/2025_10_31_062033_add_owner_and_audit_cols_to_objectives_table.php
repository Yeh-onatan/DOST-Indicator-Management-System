<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            // who “owns”/is responsible for the objective (optional FK to users)
            if (!Schema::hasColumn('objectives', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('dost_agency');
                // If you want an FK and you have users table:
                // $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            }

            // simple priority tag
            if (!Schema::hasColumn('objectives', 'priority')) {
                $table->string('priority', 20)->default('Medium')->after('owner_id');
            }

            // audit trail (who created/updated)
            if (!Schema::hasColumn('objectives', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('pc_secretariat_remarks');
                // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('objectives', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                // $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            if (Schema::hasColumn('objectives', 'updated_by')) $table->dropColumn('updated_by');
            if (Schema::hasColumn('objectives', 'created_by')) $table->dropColumn('created_by');
            if (Schema::hasColumn('objectives', 'priority'))   $table->dropColumn('priority');

            // Drop FK first if you added it:
            // if (Schema::hasColumn('objectives', 'owner_id')) {
            //     $table->dropForeign(['owner_id']);
            //     $table->dropColumn('owner_id');
            // }
            if (Schema::hasColumn('objectives', 'owner_id')) $table->dropColumn('owner_id');
        });
    }
};
