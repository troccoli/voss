<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_state_snapshots', function (Blueprint $table) {
            $table->string('team_a_side')->nullable()->after('substitutions_team_b');
        });

        $tossEvents = DB::table('game_events')
            ->select(['id', 'game_id', 'created_at', 'payload'])
            ->where('type', 'toss_completed')
            ->orderBy('game_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($tossEvents as $tossEvent) {
            $payload = json_decode((string) $tossEvent->payload, true);

            if (! is_array($payload)) {
                continue;
            }

            $teamASide = $payload['teamA'] ?? null;

            if (! is_string($teamASide)) {
                continue;
            }

            DB::table('game_state_snapshots')
                ->where('game_id', $tossEvent->game_id)
                ->where(function ($query) use ($tossEvent): void {
                    $query->where('created_at', '>', $tossEvent->created_at)
                        ->orWhere(function ($nestedQuery) use ($tossEvent): void {
                            $nestedQuery->where('created_at', $tossEvent->created_at)
                                ->where('game_event_id', '>=', $tossEvent->id);
                        });
                })
                ->update(['team_a_side' => $teamASide]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_state_snapshots', function (Blueprint $table) {
            $table->dropColumn('team_a_side');
        });
    }
};
