<?php

declare(strict_types=1);

namespace App\Events\Payloads;

use App\Enums\MisconductSanction;
use App\Enums\MisconductSubjectType;
use App\Enums\TeamAB;

final readonly class MisconductRecordedPayload implements GameEventPayload
{
    public function __construct(
        public TeamAB $team,
        public MisconductSubjectType $subjectType,
        public int $subjectId,
        public MisconductSanction $sanction,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            team: TeamAB::from($data['team']),
            subjectType: MisconductSubjectType::from($data['subject_type']),
            subjectId: (int) $data['subject_id'],
            sanction: MisconductSanction::from($data['sanction']),
        );
    }

    /** @return array{team: string, subject_type: string, subject_id: int, sanction: string} */
    public function toArray(): array
    {
        return [
            'team' => $this->team->value,
            'subject_type' => $this->subjectType->value,
            'subject_id' => $this->subjectId,
            'sanction' => $this->sanction->value,
        ];
    }
}
