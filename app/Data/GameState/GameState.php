<?php

declare(strict_types=1);

namespace App\Data\GameState;

use App\Enums\TeamAB;
use App\Models\GameStateSnapshot;
use Livewire\Wireable;

class GameState implements Wireable
{
    /** @var array<int, int> */
    public array $rotationTeamA = [];

    /** @var array<int, int> */
    public array $rotationTeamB = [];

    public function __construct(
        public int $setNumber = 0,
        public int $scoreTeamA = 0,
        public int $scoreTeamB = 0,
        public int $setsWonTeamA = 0,
        public int $setsWonTeamB = 0,
        public int $timeoutsTeamA = 0,
        public int $timeoutsTeamB = 0,
        public int $substitutionsTeamA = 0,
        public int $substitutionsTeamB = 0,
        public ?TeamAB $servingTeam = null,
        public bool $setInProgress = false,
        public bool $gameEnded = false,
    ) {}

    public static function initial(): self
    {
        return new self;
    }

    public static function fromSnapshot(GameStateSnapshot $snapshot): self
    {
        $state = new self(
            setNumber: $snapshot->set_number,
            scoreTeamA: $snapshot->score_team_a,
            scoreTeamB: $snapshot->score_team_b,
            setsWonTeamA: $snapshot->sets_won_team_a,
            setsWonTeamB: $snapshot->sets_won_team_b,
            timeoutsTeamA: $snapshot->timeouts_team_a,
            timeoutsTeamB: $snapshot->timeouts_team_b,
            substitutionsTeamA: $snapshot->substitutions_team_a,
            substitutionsTeamB: $snapshot->substitutions_team_b,
            servingTeam: $snapshot->serving_team,
            setInProgress: $snapshot->set_in_progress,
            gameEnded: $snapshot->game_ended,
        );

        $state->rotationTeamA = self::normalizeRotation($snapshot->rotation_team_a);
        $state->rotationTeamB = self::normalizeRotation($snapshot->rotation_team_b);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromAttributes(array $attributes): self
    {
        $state = new self(
            setNumber: self::toInteger($attributes['set_number'] ?? 0),
            scoreTeamA: self::toInteger($attributes['score_team_a'] ?? 0),
            scoreTeamB: self::toInteger($attributes['score_team_b'] ?? 0),
            setsWonTeamA: self::toInteger($attributes['sets_won_team_a'] ?? 0),
            setsWonTeamB: self::toInteger($attributes['sets_won_team_b'] ?? 0),
            timeoutsTeamA: self::toInteger($attributes['timeouts_team_a'] ?? 0),
            timeoutsTeamB: self::toInteger($attributes['timeouts_team_b'] ?? 0),
            substitutionsTeamA: self::toInteger($attributes['substitutions_team_a'] ?? 0),
            substitutionsTeamB: self::toInteger($attributes['substitutions_team_b'] ?? 0),
            servingTeam: is_string($attributes['serving_team'] ?? null)
                ? TeamAB::tryFrom($attributes['serving_team'])
                : null,
            setInProgress: (bool) ($attributes['set_in_progress'] ?? false),
            gameEnded: (bool) ($attributes['game_ended'] ?? false),
        );

        $rotationTeamA = $attributes['rotation_team_a'] ?? [];
        $rotationTeamB = $attributes['rotation_team_b'] ?? [];

        $state->rotationTeamA = is_array($rotationTeamA)
            ? self::normalizeRotation($rotationTeamA)
            : [];
        $state->rotationTeamB = is_array($rotationTeamB)
            ? self::normalizeRotation($rotationTeamB)
            : [];

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'set_number' => $this->setNumber,
            'score_team_a' => $this->scoreTeamA,
            'score_team_b' => $this->scoreTeamB,
            'sets_won_team_a' => $this->setsWonTeamA,
            'sets_won_team_b' => $this->setsWonTeamB,
            'timeouts_team_a' => $this->timeoutsTeamA,
            'timeouts_team_b' => $this->timeoutsTeamB,
            'substitutions_team_a' => $this->substitutionsTeamA,
            'substitutions_team_b' => $this->substitutionsTeamB,
            'serving_team' => $this->servingTeam?->value,
            'rotation_team_a' => $this->rotationTeamA,
            'rotation_team_b' => $this->rotationTeamB,
            'set_in_progress' => $this->setInProgress,
            'game_ended' => $this->gameEnded,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toLivewire(): array
    {
        return $this->toAttributes();
    }

    public static function fromLivewire(mixed $value): self
    {
        return is_array($value) ? self::fromAttributes($value) : self::initial();
    }

    public function resetCurrentSetCounters(): void
    {
        $this->scoreTeamA = 0;
        $this->scoreTeamB = 0;
        $this->timeoutsTeamA = 0;
        $this->timeoutsTeamB = 0;
        $this->substitutionsTeamA = 0;
        $this->substitutionsTeamB = 0;
    }

    /**
     * @param  array<int|string, int|string>  $positions
     * @return array<int, int>
     */
    private static function normalizeRotation(array $positions): array
    {
        $normalized = [];

        foreach ($positions as $position => $playerId) {
            $normalized[(int) $position] = (int) $playerId;
        }

        ksort($normalized);

        return $normalized;
    }

    private static function toInteger(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
