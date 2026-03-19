<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;
use App\Models\GameEvent;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Court extends Component
{
    #[Reactive]
    public ?int $gameId = null;

    /** @var array<string, mixed> */
    #[Reactive]
    public array $gameState = [];

    /**
     * @param  array<string, mixed>  $gameState
     */
    public function mount(?int $gameId = null, array $gameState = []): void
    {
        $this->gameId = $gameId;
        $this->gameState = $gameState;
    }

    public function render(): View
    {
        return view('livewire.court', $this->courtContext());
    }

    /**
     * @return array{
     *     leftTeam: TeamAB,
     *     rightTeam: TeamAB,
     *     leftRotation: array<int, int>,
     *     rightRotation: array<int, int>
     * }
     */
    private function courtContext(): array
    {
        $defaultContext = [
            'leftTeam' => TeamAB::TeamA,
            'rightTeam' => TeamAB::TeamB,
            'leftRotation' => $this->rotationForTeam(TeamAB::TeamA),
            'rightRotation' => $this->rotationForTeam(TeamAB::TeamB),
        ];

        if ($this->gameId === null) {
            return $defaultContext;
        }

        $game = Game::query()->find($this->gameId);

        if ($game === null) {
            return $defaultContext;
        }

        if ($this->isTeamAOnLeft($game)) {
            return $defaultContext;
        }

        return [
            'leftTeam' => TeamAB::TeamB,
            'rightTeam' => TeamAB::TeamA,
            'leftRotation' => $this->rotationForTeam(TeamAB::TeamB),
            'rightRotation' => $this->rotationForTeam(TeamAB::TeamA),
        ];
    }

    private function isTeamAOnLeft(Game $game): bool
    {
        $baseTeamAOnLeft = $this->teamASideForTossSets($game) === TeamSide::Home;
        $setNumber = $this->setNumber();

        if ($setNumber >= 2 && $setNumber <= 4) {
            return $setNumber % 2 === 1
                ? $baseTeamAOnLeft
                : ! $baseTeamAOnLeft;
        }

        return $baseTeamAOnLeft;
    }

    private function teamASideForTossSets(Game $game): TeamSide
    {
        $tossPayload = $this->latestTossPayload($game);

        if ($tossPayload === null) {
            return TeamSide::Home;
        }

        return $tossPayload->teamA;
    }

    private function setNumber(): int
    {
        $setNumber = $this->gameState['set_number'] ?? 0;

        return is_int($setNumber) ? $setNumber : 0;
    }

    private function latestTossPayload(Game $game): ?TossCompletedPayload
    {
        /** @var GameEvent|null $tossEvent */
        $tossEvent = $game->events()
            ->where('type', GameEventType::TossCompleted)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($tossEvent === null) {
            return null;
        }

        /** @var TossCompletedPayload */
        return $tossEvent->payload;
    }

    /**
     * @return array<int, int>
     */
    private function rotationForTeam(TeamAB $team): array
    {
        $key = $team === TeamAB::TeamA ? 'rotation_team_a' : 'rotation_team_b';
        $rotation = $this->gameState[$key] ?? [];

        if (! is_array($rotation)) {
            return [];
        }

        $normalizedRotation = [];

        foreach ($rotation as $position => $number) {
            if (! is_numeric((string) $position) || ! is_numeric((string) $number)) {
                continue;
            }

            $normalizedPosition = (int) $position;
            $normalizedNumber = (int) $number;

            if ($normalizedPosition < 1 || $normalizedPosition > 6 || $normalizedNumber <= 0) {
                continue;
            }

            $normalizedRotation[$normalizedPosition] = $normalizedNumber;
        }

        ksort($normalizedRotation);

        return $normalizedRotation;
    }
}
