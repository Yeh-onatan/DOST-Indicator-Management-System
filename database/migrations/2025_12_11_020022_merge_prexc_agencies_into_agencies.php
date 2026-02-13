<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add agency_id column to agencies table if it doesn't exist
        if (!Schema::hasColumn('agencies', 'agency_id')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->string('agency_id')->nullable()->after('code');
                $table->index('agency_id');
            });
        }

        // Step 2: Merge PrexcAgency data into Agency table if table exists
        if (Schema::hasTable('prexc_agencies')) {
            $prexcAgencies = DB::table('prexc_agencies')->get();

            foreach ($prexcAgencies as $prexcAgency) {
                // Try to find matching agency by code or name
                $existingAgency = DB::table('agencies')
                    ->where('code', $prexcAgency->agency_code)
                    ->orWhere('name', 'like', '%' . $prexcAgency->agency_name . '%')
                    ->first();

                if ($existingAgency) {
                    // Update existing agency with agency_id
                    DB::table('agencies')
                        ->where('id', $existingAgency->id)
                        ->update(['agency_id' => $prexcAgency->agency_id]);
                } else {
                    // Insert new agency
                    DB::table('agencies')->insert([
                        'code' => $prexcAgency->agency_code ?? Str::slug($prexcAgency->agency_name),
                        'agency_id' => $prexcAgency->agency_id,
                        'name' => $prexcAgency->agency_name,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Step 3: Drop foreign key constraint and then the prexc_agencies table
            Schema::table('prexc_indicators', function (Blueprint $table) {
                $table->dropForeign('prexc_indicators_prexc_agency_id_foreign');
            });

            Schema::dropIfExists('prexc_agencies');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate prexc_agencies table
        Schema::create('prexc_agencies', function (Blueprint $table) {
            $table->id();
            $table->string('agency_id')->unique();
            $table->string('agency_code')->nullable();
            $table->string('agency_name');
            $table->timestamps();
        });

        // Restore data from agencies table
        $agencies = DB::table('agencies')->whereNotNull('agency_id')->get();
        foreach ($agencies as $agency) {
            DB::table('prexc_agencies')->insert([
                'agency_id' => $agency->agency_id,
                'agency_code' => $agency->code,
                'agency_name' => $agency->name,
                'created_at' => $agency->created_at,
                'updated_at' => $agency->updated_at,
            ]);
        }

        // Remove agency_id column from agencies table
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn('agency_id');
        });
    }
};
