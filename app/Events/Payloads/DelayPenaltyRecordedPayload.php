<?php

declare(strict_types=1);

namespace App\Events\Payloads;

use App\Enums\ImproperRequestType;
use App\Enums\TeamAB;

final readonly class DelayPenaltyRecordedPayload implements GameEventPayload
{
    public function __construct(
        public TeamAB $team,
        public TeamAB $awardedTeam,
        public ImproperRequestType $requestType,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            team: TeamAB::from($data['team']),
            awardedTeam: TeamAB::from($data['awarded_team']),
            requestType: ImproperRequestType::from($data['request_type']),
        );
    }

    /** @return array{team: string, awarded_team: string, request_type: string} */
    public function toArray(): array
    {
        return [
            'team' => $this->team->value,
            'awarded_team' => $this->awardedTeam->value,
            'request_type' => $this->requestType->value,
        ];
    }
}
