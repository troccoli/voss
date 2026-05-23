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
            $table->unsignedTinyInteger('misconduct_warnings_team_a')->default(0)->after('delay_penalties_team_b');
            $table->unsignedTinyInteger('misconduct_warnings_team_b')->default(0)->after('misconduct_warnings_team_a');
            $table->unsignedTinyInteger('misconduct_penalties_team_a')->default(0)->after('misconduct_warnings_team_b');
            $table->unsignedTinyInteger('misconduct_penalties_team_b')->default(0)->after('misconduct_penalties_team_a');
            $table->unsignedTinyInteger('misconduct_expulsions_team_a')->default(0)->after('misconduct_penalties_team_b');
            $table->unsignedTinyInteger('misconduct_expulsions_team_b')->default(0)->after('misconduct_expulsions_team_a');
            $table->unsignedTinyInteger('misconduct_disqualifications_team_a')->default(0)->after('misconduct_expulsions_team_b');
            $table->unsignedTinyInteger('misconduct_disqualifications_team_b')->default(0)->after('misconduct_disqualifications_team_a');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_state_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'misconduct_warnings_team_a',
                'misconduct_warnings_team_b',
                'misconduct_penalties_team_a',
                'misconduct_penalties_team_b',
                'misconduct_expulsions_team_a',
                'misconduct_expulsions_team_b',
                'misconduct_disqualifications_team_a',
                'misconduct_disqualifications_team_b',
            ]);
        });
    }
};
