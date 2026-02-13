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
        Schema::table('users', function (Blueprint $table) {
            // Add only if it doesn't exist yet
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')
                    ->after('email')   // place it after email
                    ->unique();        // must be unique for login
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                // drop unique index first (by column array is fine)
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }
        });
    }
};
