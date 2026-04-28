<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
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

    #[Reactive]
    public ?GameState $gameState = null;

    /** @var array<int, string> */
    public array $lineup = [];

    public function mount(TeamAB $team, ?int $gameId = null): void
    {
        abort_if(is_null($gameId), 404);

        $this->team = $team;
        $this->gameId = $gameId;
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

        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            $this->addError('submit', 'No active game is available to record the lineup.');

            return;
        }

        if (! $this->canSubmitLineup()) {
            $this->addError('submit', 'A lineup can only be submitted before a set starts when the toss is recorded.');

            return;
        }

        $positions = $this->lineupPositions();
        $eligibleNumbers = $this->eligibleRosterNumbers($activeGame);

        if ($eligibleNumbers !== [] && ! $this->lineupUsesEligibleRosterNumbers($positions, $eligibleNumbers)) {
            return;
        }

        $set = $this->upcomingSetNumber();

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
            'rosterNumbers' => $this->rosterNumbers(),
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
        $teamSide = $this->teamSideForToss();

        if ($teamSide === null) {
            return [];
        }

        $rosterNumbers = $teamSide === TeamSide::Home
            ? $game->homePlayers()->wherePivot('is_libero', false)->orderByPivot('number')->pluck('game_player.number')
            : $game->awayPlayers()->wherePivot('is_libero', false)->orderByPivot('number')->pluck('game_player.number');

        return $rosterNumbers
            ->map(fn (mixed $number): int => (int) $number)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function rosterNumbers(): array
    {
        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            return [];
        }

        return $this->eligibleRosterNumbers($activeGame);
    }

    private function teamSideForToss(): ?TeamSide
    {
        $teamASide = $this->resolvedGameState()->teamASide;

        if ($teamASide === null) {
            return null;
        }

        if ($this->team === TeamAB::TeamA) {
            return $teamASide;
        }

        return $teamASide === TeamSide::Home
            ? TeamSide::Away
            : TeamSide::Home;
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
        if (! $this->hasSubmittedToss()) {
            return false;
        }

        if ($this->isSetInProgress() || $this->isGameEnded()) {
            return false;
        }

        return ! $this->hasSubmittedLineupForUpcomingSet();
    }

    private function hasSubmittedLineupForUpcomingSet(): bool
    {
        $state = $this->resolvedGameState();

        $lineup = $this->team === TeamAB::TeamA
            ? $state->rotationTeamA
            : $state->rotationTeamB;

        return $lineup !== [];
    }

    private function hasSubmittedToss(): bool
    {
        $state = $this->resolvedGameState();

        return $state->teamASide !== null && $state->servingTeam !== null;
    }

    private function upcomingSetNumber(): int
    {
        return $this->currentSetNumber() + 1;
    }

    private function currentSetNumber(): int
    {
        return $this->resolvedGameState()->setNumber;
    }

    private function isSetInProgress(): bool
    {
        return $this->resolvedGameState()->setInProgress;
    }

    private function isGameEnded(): bool
    {
        return $this->resolvedGameState()->gameEnded;
    }

    private function resolvedGameState(): GameState
    {
        $activeGame = $this->activeGame();

        if ($activeGame !== null) {
            return $activeGame->stateAt();
        }

        return $this->gameState ?? GameState::initial();
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

    private function activeGame(): ?Game
    {
        return Game::query()->whereKey($this->gameId)->first();
    }
}
