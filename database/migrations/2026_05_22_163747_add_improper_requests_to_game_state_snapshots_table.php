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
            $table->unsignedTinyInteger('improper_requests_team_a')->default(0)->after('substitutions_team_b');
            $table->unsignedTinyInteger('improper_requests_team_b')->default(0)->after('improper_requests_team_a');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_state_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'improper_requests_team_a',
                'improper_requests_team_b',
            ]);
        });
    }
};
