<?php

namespace App\Events\Payloads;

final readonly class GameEndedPayload implements GameEventPayload
{
    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [];
    }
}
