<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add reaper column to planets table
        Schema::table('planets', function (Blueprint $table) {
            $table->integer('reaper')->default(0)->after('deathstar');
        });

        // Add reaper column to fleet_missions table
        Schema::table('fleet_missions', function (Blueprint $table) {
            $table->integer('reaper')->default(0)->after('deathstar');
        });

        // Add crawler column to planets table (after solar_satellite)
        Schema::table('planets', function (Blueprint $table) {
            $table->integer('crawler')->default(0)->after('solar_satellite');
        });

        // Add crawler column to fleet_missions table (after espionage_probe)
        Schema::table('fleet_missions', function (Blueprint $table) {
            $table->integer('crawler')->default(0)->after('espionage_probe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planets', function (Blueprint $table) {
            $table->dropColumn('reaper');
            $table->dropColumn('crawler');
        });

        Schema::table('fleet_missions', function (Blueprint $table) {
            $table->dropColumn('reaper');
            $table->dropColumn('crawler');
        });
    }
};
