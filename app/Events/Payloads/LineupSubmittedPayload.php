<?php

declare(strict_types=1);

namespace App\Events\Payloads;

use App\Enums\TeamAB;
use Illuminate\Support\Collection;

final readonly class LineupSubmittedPayload implements GameEventPayload
{
    /** @param array<int, int> $positions */
    private function __construct(
        public int $set,
        public TeamAB $team,
        public array $positions,
    ) {}

    /**
     * @param  array<int, int>  $positions
     * @param  Collection<int, int>  $validRosterNumbers
     */
    public static function create(int $set, TeamAB $team, array $positions, Collection $validRosterNumbers): static
    {
        if (count($positions) !== 6) {
            throw new \InvalidArgumentException('A lineup must have exactly 6 positions.');
        }

        $keys = array_keys($positions);
        sort($keys);
        if ($keys !== range(1, 6)) {
            throw new \InvalidArgumentException('Lineup positions must be keyed 1 through 6.');
        }

        if (count(array_unique($positions)) !== 6) {
            throw new \InvalidArgumentException('All 6 lineup positions must have different roster numbers.');
        }

        foreach ($positions as $position => $rosterNumber) {
            if ($rosterNumber <= 0) {
                throw new \InvalidArgumentException("Lineup position {$position} must contain a positive roster number.");
            }

            if (! $validRosterNumbers->contains($rosterNumber)) {
                throw new \InvalidArgumentException("Roster number {$rosterNumber} is not on the non-libero roster for the specified team.");
            }
        }

        return new self($set, $team, $positions);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            set: $data['set'],
            team: TeamAB::from($data['team']),
            positions: $data['positions'],
        );
    }

    /** @return array{set: int, team: string, positions: array<int, int>} */
    public function toArray(): array
    {
        return [
            'set' => $this->set,
            'team' => $this->team->value,
            'positions' => $this->positions,
        ];
    }
}
