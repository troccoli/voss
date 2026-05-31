<?php

declare(strict_types=1);

namespace App\Events\Payloads;

use App\Enums\ImproperRequestType;
use App\Enums\TeamAB;

final readonly class DelayWarningRecordedPayload implements GameEventPayload
{
    public function __construct(
        public TeamAB $team,
        public ?ImproperRequestType $requestType = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            team: TeamAB::from($data['team']),
            requestType: isset($data['request_type']) ? ImproperRequestType::from($data['request_type']) : null,
        );
    }

    /** @return array{team: string, request_type?: string} */
    public function toArray(): array
    {
        $payload = [
            'team' => $this->team->value,
        ];

        if ($this->requestType !== null) {
            $payload['request_type'] = $this->requestType->value;
        }

        return $payload;
    }
}
