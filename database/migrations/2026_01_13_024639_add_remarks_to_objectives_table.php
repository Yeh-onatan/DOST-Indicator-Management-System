<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            // Check if column exists to avoid errors
            if (!Schema::hasColumn('objectives', 'remarks')) {
                $table->text('remarks')->nullable()->after('baseline'); 
            }
        });
    }

    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};