<?php

declare(strict_types=1);

namespace App\Events\Payloads;

use App\Enums\TeamAB;

final readonly class SubstitutionCompletedPayload implements GameEventPayload
{
    public function __construct(
        public TeamAB $team,
        public int $playerOut,
        public int $playerIn,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            team: TeamAB::from($data['team']),
            playerOut: $data['player_out'],
            playerIn: $data['player_in'],
        );
    }

    /** @return array{team: string, player_out: int, player_in: int} */
    public function toArray(): array
    {
        return [
            'team' => $this->team->value,
            'player_out' => $this->playerOut,
            'player_in' => $this->playerIn,
        ];
    }
}
