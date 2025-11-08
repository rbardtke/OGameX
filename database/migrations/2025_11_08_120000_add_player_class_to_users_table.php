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
        Schema::table('users', function (Blueprint $table) {
            // Player class: collector, general, discoverer
            // NULL means no class selected yet (for backwards compatibility)
            $table->enum('player_class', ['collector', 'general', 'discoverer'])->nullable()->after('username');

            // Timestamp of when the player last changed their class
            // Used to enforce the once-per-week restriction
            $table->timestamp('class_changed_at')->nullable()->after('player_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['player_class', 'class_changed_at']);
        });
    }
};
