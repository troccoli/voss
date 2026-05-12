<?php

declare(strict_types=1);

namespace App\Events\Payloads;

use App\Enums\TeamAB;
use App\Enums\TeamSide;

final readonly class TossCompletedPayload implements GameEventPayload
{
    public function __construct(
        public TeamSide $teamA,
        public TeamAB $serving,
        public TeamAB $leftTeam = TeamAB::TeamA,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            teamA: TeamSide::from($data['team_a']),
            serving: TeamAB::from($data['serving']),
            leftTeam: TeamAB::from($data['left_team'] ?? TeamAB::TeamA->value),
        );
    }

    /** @return array{team_a: string, serving: string, left_team: string} */
    public function toArray(): array
    {
        return [
            'team_a' => $this->teamA->value,
            'serving' => $this->serving->value,
            'left_team' => $this->leftTeam->value,
        ];
    }
}
