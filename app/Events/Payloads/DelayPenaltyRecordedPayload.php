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
        public ?ImproperRequestType $requestType = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            team: TeamAB::from($data['team']),
            awardedTeam: TeamAB::from($data['awarded_team']),
            requestType: isset($data['request_type']) ? ImproperRequestType::from($data['request_type']) : null,
        );
    }

    /** @return array{team: string, awarded_team: string, request_type?: string} */
    public function toArray(): array
    {
        $payload = [
            'team' => $this->team->value,
            'awarded_team' => $this->awardedTeam->value,
        ];

        if ($this->requestType !== null) {
            $payload['request_type'] = $this->requestType->value;
        }

        return $payload;
    }
}
