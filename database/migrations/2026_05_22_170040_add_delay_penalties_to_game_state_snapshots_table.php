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
            $table->unsignedTinyInteger('delay_penalties_team_a')->default(0)->after('delay_warnings_team_b');
            $table->unsignedTinyInteger('delay_penalties_team_b')->default(0)->after('delay_penalties_team_a');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_state_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'delay_penalties_team_a',
                'delay_penalties_team_b',
            ]);
        });
    }
};
