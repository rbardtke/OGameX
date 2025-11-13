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
        // Add reaper and crawler columns to planets table
        Schema::table('planets', function (Blueprint $table) {
            $table->integer('reaper')->default(0)->after('deathstar');
            $table->integer('crawler')->default(0)->after('solar_satellite');
        });

        // Add reaper and crawler columns to fleet_missions table
        Schema::table('fleet_missions', function (Blueprint $table) {
            $table->integer('reaper')->default(0)->after('deathstar');
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
