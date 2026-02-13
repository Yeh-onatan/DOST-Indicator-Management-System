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
    Schema::table('regions', function (Blueprint $table) {
        if (!Schema::hasColumn('regions', 'order_index')) {
            $table->integer('order_index')->default(0)->after('name');
        }
        if (!Schema::hasColumn('regions', 'director_id')) {
            $table->foreignId('director_id')->nullable()->constrained('users')->nullOnDelete()->after('order_index');
        }
    });
}

public function down(): void
{
    Schema::table('regions', function (Blueprint $table) {
        $table->dropColumn(['order_index', 'director_id']);
    });
}
};
