<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamAB;
use App\Enums\TeamSide;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $game_id
 * @property int $game_event_id
 * @property int $set_number
 * @property int $score_team_a
 * @property int $score_team_b
 * @property int $sets_won_team_a
 * @property int $sets_won_team_b
 * @property int $timeouts_team_a
 * @property int $timeouts_team_b
 * @property int $substitutions_team_a
 * @property int $substitutions_team_b
 * @property TeamSide|null $team_a_side
 * @property TeamAB|null $serving_team
 * @property array<int, int> $rotation_team_a
 * @property array<int, int> $rotation_team_b
 * @property bool $set_in_progress
 * @property bool $game_ended
 * @property CarbonImmutable $created_at
 * @property-read Game $game
 * @property-read GameEvent $event
 */
class GameStateSnapshot extends Model
{
    const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'set_number' => 'integer',
            'score_team_a' => 'integer',
            'score_team_b' => 'integer',
            'sets_won_team_a' => 'integer',
            'sets_won_team_b' => 'integer',
            'timeouts_team_a' => 'integer',
            'timeouts_team_b' => 'integer',
            'substitutions_team_a' => 'integer',
            'substitutions_team_b' => 'integer',
            'team_a_side' => TeamSide::class,
            'serving_team' => TeamAB::class,
            'rotation_team_a' => 'array',
            'rotation_team_b' => 'array',
            'set_in_progress' => 'boolean',
            'game_ended' => 'boolean',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<GameEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(GameEvent::class, 'game_event_id');
    }
}
