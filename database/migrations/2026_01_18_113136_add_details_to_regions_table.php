<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('regions', function (Blueprint $table) {
        $table->integer('order_index')->default(0)->after('id'); // For custom sorting (e.g. NCR=1, Reg1=2)
        $table->foreignId('director_id')->nullable()->constrained('users')->nullOnDelete()->after('name'); // The Regional Director
    });
}

public function down()
{
    Schema::table('regions', function (Blueprint $table) {
        $table->dropColumn(['order_index', 'director_id']);
    });
}
};
