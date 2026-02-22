<?php

namespace App\Events\Payloads;

use App\Enums\TeamAB;
use App\Enums\TeamSide;

final readonly class TossCompletedPayload implements GameEventPayload
{
    public function __construct(
        public TeamSide $teamA,
        public TeamAB $serving,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            teamA: TeamSide::from($data['team_a']),
            serving: TeamAB::from($data['serving']),
        );
    }

    /** @return array{team_a: string, serving: string} */
    public function toArray(): array
    {
        return [
            'team_a' => $this->teamA->value,
            'serving' => $this->serving->value,
        ];
    }
}
