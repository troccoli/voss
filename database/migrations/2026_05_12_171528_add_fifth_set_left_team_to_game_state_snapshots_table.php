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
        Schema::table('game_state_snapshots', function (Blueprint $table) {
            $table->string('fifth_set_left_team')->nullable()->after('team_a_side');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_state_snapshots', function (Blueprint $table) {
            $table->dropColumn('fifth_set_left_team');
        });
    }
};
