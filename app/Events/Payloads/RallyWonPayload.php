<?php

namespace App\Events\Payloads;

use App\Enums\TeamAB;

final readonly class RallyWonPayload implements GameEventPayload
{
    public function __construct(
        public TeamAB $team,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            team: TeamAB::from($data['team']),
        );
    }

    /** @return array{team: string} */
    public function toArray(): array
    {
        return [
            'team' => $this->team->value,
        ];
    }
}
