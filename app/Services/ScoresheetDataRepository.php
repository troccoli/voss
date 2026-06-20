<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use App\Services\GameState\GameStateProjector;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ScoresheetDataRepository
{
    /**
     * @return array{
     *     competition_name: string,
     *     city: string,
     *     country_code: string,
     *     hall: string,
     *     pool: string,
     *     match_number: int,
     *     scheduled_at: CarbonImmutable,
     *     division: string,
     *     category: string,
     *     home_team_code: string,
     *     away_team_code: string
     * }
     */
    public function matchInfo(Game $game): array
    {
        $game->loadMissing(['homeTeam', 'awayTeam']);

        return [
            'competition_name' => $game->competitionName(),
            'city' => $game->city,
            'country_code' => $game->country_code,
            'hall' => $game->hall,
            'pool' => $game->pool,
            'match_number' => $game->number,
            'scheduled_at' => $game->date_time,
            'division' => $game->division,
            'category' => $game->category,
            'home_team_code' => $game->homeTeam->country_code,
            'away_team_code' => $game->awayTeam->country_code,
        ];
    }

    /**
     * @return array{
     *     team_code: string,
     *     players: array<int, array{
     *         player_key: int,
     *         number: int,
     *         last_name: string,
     *         is_captain: bool
     *     }>,
     *     liberos: array<int, array{
     *         player_key: int,
     *         number: int,
     *         last_name: string
     *     }>,
     *     staff: array<int, array{
     *         staff_key: int,
     *         role: StaffRole,
     *         last_name: string,
     *         first_name: string
     *     }>
     * }
     */
    public function teamSheet(Game $game, TeamSide $side): array
    {
        $game->loadMissing(['homeTeam', 'awayTeam']);

        return [
            'team_code' => $side === TeamSide::Home
                ? $game->homeTeam->country_code
                : $game->awayTeam->country_code,
            'players' => $this->playersForSide($game, $side),
            'liberos' => $this->liberosForSide($game, $side),
            'staff' => $this->staffForSide($game, $side),
        ];
    }

    /**
     * @return array<int, array{
     *     player_key: int,
     *     number: int,
     *     last_name: string,
     *     is_captain: bool
     * }>
     */
    public function playersForSide(Game $game, TeamSide $side): array
    {
        $players = $side === TeamSide::Home
            ? $game->homePlayers()
            : $game->awayPlayers();

        return $players
            ->where('is_libero', false)
            ->orderBy('number')
            ->get()
            ->map(fn (Player $player): ?array => $player->number === null ? null : [
                'player_key' => $player->getKey(),
                'number' => $player->number,
                'last_name' => $player->last_name,
                'is_captain' => $player->roster->is_captain,
            ])
            ->filter()
            ->all();
    }

    /**
     * @return array<int, array{
     *     player_key: int,
     *     number: int,
     *     last_name: string
     * }>
     */
    public function liberosForSide(Game $game, TeamSide $side): array
    {
        $players = $side === TeamSide::Home
            ? $game->homePlayers()
            : $game->awayPlayers();

        return $players
            ->where('is_libero', true)
            ->orderBy('number')
            ->get()
            ->map(fn (Player $player): ?array => $player->number === null ? null : [
                'player_key' => $player->getKey(),
                'number' => $player->number,
                'last_name' => $player->last_name,
            ])
            ->filter()
            ->all();
    }

    /**
     * @return array<int, array{
     *     staff_key: int,
     *     role: StaffRole,
     *     last_name: string,
     *     first_name: string
     * }>
     */
    public function staffForSide(Game $game, TeamSide $side): array
    {
        $staff = $side === TeamSide::Home
            ? $game->homeStaff()
            : $game->awayStaff();

        return $staff
            ->orderBy('id')
            ->get()
            ->map(fn (Staff $staffMember): array => [
                'staff_key' => $staffMember->getKey(),
                'role' => $staffMember->roster->role,
                'last_name' => $staffMember->last_name,
                'first_name' => $staffMember->first_name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{
     *     role: OfficialRole,
     *     first_name: string,
     *     last_name: string,
     *     country_code: string
     * }>
     */
    public function officials(Game $game): array
    {
        /** @var EloquentCollection<int, Official> $officials */
        $officials = $game->officials()->get();

        $officialsByRole = $officials->keyBy(
            fn (Official $official): string => $official->role === null ? '' : $official->role->value,
        );

        return collect(OfficialRole::cases())
            ->map(function (OfficialRole $role) use ($officialsByRole): ?array {
                /** @var Official|null $official */
                $official = $officialsByRole->get($role->value);

                if ($official === null) {
                    return null;
                }

                return [
                    'role' => $role,
                    'first_name' => $official->first_name,
                    'last_name' => $official->last_name,
                    'country_code' => $official->country_code,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function latestTossPayload(Game $game): ?TossCompletedPayload
    {
        /** @var GameEvent|null $tossEvent */
        $tossEvent = $game->events()
            ->reorder()
            ->where('type', GameEventType::TossCompleted)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($tossEvent === null) {
            return null;
        }

        /** @var TossCompletedPayload */
        return $tossEvent->payload;
    }

    /**
     * @return array{
     *     team_a_code: string,
     *     team_b_code: string,
     *     winner_team_code: string,
     *     team_a_sets_won: int,
     *     team_b_sets_won: int,
     *     match_start_time: ?CarbonImmutable,
     *     match_end_time: ?CarbonImmutable,
     *     total_duration_minutes: ?int,
     *     total_set_duration_minutes: int,
     *     sets: array<int, array{
     *         set_number: int,
     *         team_a_points: int,
     *         team_b_points: int,
     *         team_a_timeouts: int,
     *         team_b_timeouts: int,
     *         team_a_substitutions: int,
     *         team_b_substitutions: int,
     *         team_a_sets_won: int,
     *         team_b_sets_won: int,
     *         duration_minutes: int
     *     }>
     * }
     */
    public function results(Game $game): array
    {
        $game->loadMissing(['homeTeam', 'awayTeam']);

        $projector = new GameStateProjector;
        $state = GameState::initial();
        $setStartedAt = null;
        $matchStartedAt = null;
        $matchEndedAt = null;
        $totalSetDurationMinutes = 0;
        $setSummaries = [];

        /** @var EloquentCollection<int, GameEvent> $events */
        $events = $game->events()->get();

        /** @var GameEvent $event */
        foreach ($events as $event) {
            $stateBeforeEvent = GameState::fromAttributes($state->toAttributes());
            $state = $projector->project($state, $event);

            if ($event->type === GameEventType::SetStarted) {
                $setStartedAt = $event->created_at;
                $matchStartedAt ??= $event->created_at;

                continue;
            }

            if ($event->type === GameEventType::SetEnded) {
                $durationMinutes = (int) ($setStartedAt?->diffInMinutes($event->created_at) ?? 0);
                $totalSetDurationMinutes += $durationMinutes;
                $matchEndedAt = $event->created_at;

                $setSummaries[] = [
                    'set_number' => $stateBeforeEvent->setNumber,
                    'team_a_points' => $stateBeforeEvent->scoreTeamA,
                    'team_b_points' => $stateBeforeEvent->scoreTeamB,
                    'team_a_timeouts' => $stateBeforeEvent->timeoutsTeamA,
                    'team_b_timeouts' => $stateBeforeEvent->timeoutsTeamB,
                    'team_a_substitutions' => $stateBeforeEvent->substitutionsTeamA,
                    'team_b_substitutions' => $stateBeforeEvent->substitutionsTeamB,
                    'team_a_sets_won' => $state->setsWonTeamA,
                    'team_b_sets_won' => $state->setsWonTeamB,
                    'duration_minutes' => $durationMinutes,
                ];
                $setStartedAt = null;

                continue;
            }

            if ($event->type === GameEventType::GameEnded) {
                $matchEndedAt = $event->created_at;
            }
        }

        $winner = match (true) {
            $state->setsWonTeamA > $state->setsWonTeamB => TeamAB::TeamA,
            $state->setsWonTeamB > $state->setsWonTeamA => TeamAB::TeamB,
            default => null,
        };

        return [
            'team_a_code' => $game->homeTeam->country_code,
            'team_b_code' => $game->awayTeam->country_code,
            'winner_team_code' => match ($winner) {
                TeamAB::TeamA => $game->homeTeam->country_code,
                TeamAB::TeamB => $game->awayTeam->country_code,
                default => '',
            },
            'team_a_sets_won' => $state->setsWonTeamA,
            'team_b_sets_won' => $state->setsWonTeamB,
            'match_start_time' => $matchStartedAt,
            'match_end_time' => $matchEndedAt,
            'total_duration_minutes' => $matchStartedAt !== null && $matchEndedAt !== null
                ? (int) $matchStartedAt->diffInMinutes($matchEndedAt)
                : null,
            'total_set_duration_minutes' => $totalSetDurationMinutes,
            'sets' => $setSummaries,
        ];
    }
}
