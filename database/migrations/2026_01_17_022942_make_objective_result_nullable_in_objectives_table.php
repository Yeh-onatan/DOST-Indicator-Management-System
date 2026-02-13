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
    Schema::table('objectives', function (Blueprint $table) {
        $table->text('objective_result')->nullable()->change();
    });
}

public function down()
{
    // Reverting requires knowing the original state, usually not nullable
    // schema::table('objectives', function (Blueprint $table) {
    //    $table->text('objective_result')->nullable(false)->change();
    // });
}
};
