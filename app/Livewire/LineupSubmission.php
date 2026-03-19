<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use App\Models\GameEvent;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class LineupSubmission extends Component
{
    #[Reactive]
    #[Locked]
    public int $gameId;

    public TeamAB $team = TeamAB::TeamA;

    /** @var array<string, mixed> */
    #[Reactive]
    public array $gameState = [];

    /** @var array<int, string> */
    public array $lineup = [];

    /**
     * @param  array<string, mixed>  $gameState
     */
    public function mount(TeamAB $team, ?int $gameId = null, array $gameState = []): void
    {
        abort_if(is_null($gameId), 404);

        $this->team = $team;
        $this->gameId = $gameId;
        $this->gameState = $gameState;
        $this->lineup = $this->defaultLineup();
    }

    public function modalName(): string
    {
        return 'submit-lineup-'.$this->team->value;
    }

    public function modalHeading(): string
    {
        return $this->team === TeamAB::TeamA
            ? 'Team A Lineup'
            : 'Team B Lineup';
    }

    public function submit(): void
    {
        $this->resetValidation();

        if (! $this->validateLineup()) {
            return;
        }

        $activeGame = Game::query()->whereKey($this->gameId)->first();

        if ($activeGame === null) {
            $this->addError('submit', 'No active game is available to record the lineup.');

            return;
        }

        $positions = $this->lineupPositions();
        $eligibleNumbers = $this->eligibleRosterNumbers($activeGame);

        if ($eligibleNumbers !== [] && ! $this->lineupUsesEligibleRosterNumbers($positions, $eligibleNumbers)) {
            return;
        }

        $set = $activeGame->stateAt()->setNumber + 1;

        try {
            $activeGame->recordLineup($set, $this->team, $positions);
        } catch (InvalidGameEventTransition|InvalidArgumentException $exception) {
            $this->addError('submit', $exception->getMessage());

            return;
        }

        Flux::modal($this->modalName())->close();
        $this->dispatch('game-event-recorded');

        $this->resetValidation();
        $this->lineup = $this->defaultLineup();
    }

    public function render(): View
    {
        return view('livewire.lineup-submission', [
            'canSubmitLineup' => $this->canSubmitLineup(),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'lineup' => ['required', 'array', 'size:6'],
            'lineup.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'lineup.size' => 'A lineup must contain exactly 6 roster numbers.',
            'lineup.*.required' => 'Each lineup position must have a roster number.',
            'lineup.*.integer' => 'Roster numbers must be positive integers.',
            'lineup.*.min' => 'Roster numbers must be positive integers.',
            'lineup.*.distinct' => 'All lineup roster numbers must be different.',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function lineupPositions(): array
    {
        $positions = [];

        foreach (range(1, 6) as $position) {
            $positions[$position] = (int) $this->lineup[$position];
        }

        return $positions;
    }

    /**
     * @return array<int, int>
     */
    private function eligibleRosterNumbers(Game $game): array
    {
        /** @var GameEvent|null $tossEvent */
        $tossEvent = $game->events()
            ->where('type', GameEventType::TossCompleted)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($tossEvent === null) {
            return [];
        }

        /** @var TossCompletedPayload $tossPayload */
        $tossPayload = $tossEvent->payload;

        $side = $this->team === TeamAB::TeamA
            ? $tossPayload->teamA
            : ($tossPayload->teamA === TeamSide::Home ? TeamSide::Away : TeamSide::Home);

        $rosterNumbers = $side === TeamSide::Home
            ? $game->homePlayers()->wherePivot('is_libero', false)->pluck('game_player.number')
            : $game->awayPlayers()->wherePivot('is_libero', false)->pluck('game_player.number');

        return $rosterNumbers
            ->map(fn (mixed $number): int => (int) $number)
            ->all();
    }

    /**
     * @param  array<int, int>  $positions
     * @param  array<int, int>  $eligibleNumbers
     */
    private function lineupUsesEligibleRosterNumbers(array $positions, array $eligibleNumbers): bool
    {
        foreach ($positions as $position => $rosterNumber) {
            if (! in_array($rosterNumber, $eligibleNumbers, true)) {
                $this->addError('submit', "Lineup position {$position} is not in this team roster or is a libero.");

                return false;
            }
        }

        return true;
    }

    private function validateLineup(): bool
    {
        $validator = Validator::make(
            data: ['lineup' => $this->lineup],
            rules: $this->rules(),
            messages: $this->messages(),
        )->stopOnFirstFailure();

        if ($validator->fails()) {
            $this->addError('submit', $validator->errors()->first());

            return false;
        }

        return true;
    }

    private function canSubmitLineup(): bool
    {
        if ($this->isSetInProgress() || $this->isGameEnded()) {
            return false;
        }

        return ! $this->hasSubmittedLineupForUpcomingSet();
    }

    private function hasSubmittedLineupForUpcomingSet(): bool
    {
        $activeGame = Game::query()->whereKey($this->gameId)->first();

        if ($activeGame === null) {
            return false;
        }

        return $activeGame->events()
            ->where('type', GameEventType::LineupSubmitted)
            ->where('payload->set', $this->upcomingSetNumber())
            ->where('payload->team', $this->team->value)
            ->exists();
    }

    private function upcomingSetNumber(): int
    {
        return $this->currentSetNumber() + 1;
    }

    private function currentSetNumber(): int
    {
        $setNumber = $this->gameState['set_number'] ?? 0;

        if (is_int($setNumber)) {
            return $setNumber;
        }

        return is_numeric($setNumber) ? (int) $setNumber : 0;
    }

    private function isSetInProgress(): bool
    {
        $setInProgress = $this->gameState['set_in_progress'] ?? false;

        return (bool) $setInProgress;
    }

    private function isGameEnded(): bool
    {
        $gameEnded = $this->gameState['game_ended'] ?? false;

        return (bool) $gameEnded;
    }

    /**
     * @return array<int, string>
     */
    private function defaultLineup(): array
    {
        return [
            1 => '',
            2 => '',
            3 => '',
            4 => '',
            5 => '',
            6 => '',
        ];
    }
}
