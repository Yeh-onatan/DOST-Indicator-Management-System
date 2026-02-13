<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->string('org_name')->nullable();
            $table->string('org_logo_path')->nullable();
            $table->string('theme_accent')->nullable();
            $table->string('timezone')->nullable();
            $table->string('locale')->nullable();
            $table->unsignedSmallInteger('archive_years')->nullable();
            $table->json('regions_roles')->nullable();
            $table->json('compliance')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn([
                'org_name','org_logo_path','theme_accent','timezone','locale','archive_years','regions_roles','compliance'
            ]);
        });
    }
};

