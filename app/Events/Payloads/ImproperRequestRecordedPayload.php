<?php

declare(strict_types=1);

namespace App\Events\Payloads;

use App\Enums\ImproperRequestType;
use App\Enums\TeamAB;

final readonly class ImproperRequestRecordedPayload implements GameEventPayload
{
    public function __construct(
        public TeamAB $team,
        public ImproperRequestType $requestType,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            team: TeamAB::from($data['team']),
            requestType: ImproperRequestType::from($data['request_type']),
        );
    }

    /** @return array{team: string, request_type: string} */
    public function toArray(): array
    {
        return [
            'team' => $this->team->value,
            'request_type' => $this->requestType->value,
        ];
    }
}
