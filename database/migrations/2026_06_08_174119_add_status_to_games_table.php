<?php

declare(strict_types=1);

use App\Enums\GameEventType;
use App\Enums\MatchPhase;
use App\Enums\OfficialRole;
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
        Schema::table('games', function (Blueprint $table): void {
            $table->string('status')->default(MatchPhase::Setup->value)->after('rosters_submitted');
        });

        $requiredOfficialRoleCount = count(OfficialRole::cases());

        collect(DB::table('games')->select('id')->orderBy('id')->get())
            ->each(function (object $game) use ($requiredOfficialRoleCount): void {
                $gameId = (int) $game->id;
                $details = DB::table('games')
                    ->join('teams as home_teams', 'home_teams.id', '=', 'games.home_team_id')
                    ->join('teams as away_teams', 'away_teams.id', '=', 'games.away_team_id')
                    ->where('games.id', $gameId)
                    ->select([
                        'games.number',
                        'games.country_code',
                        'games.city',
                        'games.hall',
                        'games.division',
                        'games.pool',
                        'games.category',
                        'games.rosters_submitted',
                        'home_teams.id as home_team_id',
                        'home_teams.name as home_team_name',
                        'home_teams.country_code as home_team_country_code',
                        'away_teams.id as away_team_id',
                        'away_teams.name as away_team_name',
                        'away_teams.country_code as away_team_country_code',
                    ])
                    ->first();

                if ($details === null) {
                    return;
                }

                $hasRecordedEvents = DB::table('game_events')
                    ->where('game_id', $gameId)
                    ->exists();

                $hasCompletedGame = DB::table('game_events')
                    ->where('game_id', $gameId)
                    ->where('type', GameEventType::GameEnded->value)
                    ->exists();

                $status = MatchPhase::Setup;

                if ($hasCompletedGame) {
                    $status = MatchPhase::Completed;
                } elseif ($hasRecordedEvents) {
                    $status = MatchPhase::InProgress;
                } else {
                    $hasCompleteMatchDetails = (int) $details->number > 0
                        && $details->country_code !== ''
                        && $details->city !== ''
                        && $details->hall !== ''
                        && $details->division !== ''
                        && $details->pool !== ''
                        && $details->category !== ''
                        && $details->home_team_name !== ''
                        && $details->home_team_country_code !== ''
                        && $details->away_team_name !== ''
                        && $details->away_team_country_code !== '';

                    $hasSubmittedInitialRosters = (bool) $details->rosters_submitted
                        && DB::table('players')
                            ->where('game_id', $gameId)
                            ->where('team_id', (int) $details->home_team_id)
                            ->where('is_rostered', true)
                            ->exists()
                        && DB::table('players')
                            ->where('game_id', $gameId)
                            ->where('team_id', (int) $details->away_team_id)
                            ->where('is_rostered', true)
                            ->exists();

                    $assignedOfficialRoleCount = DB::table('officials')
                        ->where('game_id', $gameId)
                        ->whereNotNull('role')
                        ->distinct()
                        ->count('role');

                    if ($hasCompleteMatchDetails
                        && $hasSubmittedInitialRosters
                        && $assignedOfficialRoleCount === $requiredOfficialRoleCount) {
                        $status = MatchPhase::Ready;
                    }
                }

                DB::table('games')
                    ->where('id', $gameId)
                    ->update(['status' => $status->value]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
