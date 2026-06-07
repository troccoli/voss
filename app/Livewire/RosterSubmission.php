<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Models\Game;
use App\Models\Player;
use App\Models\Staff;
use App\Services\GameSideResolver;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class RosterSubmission extends Component
{
    #[Reactive]
    #[Locked]
    public ?int $gameId = null;

    #[Reactive]
    public ?GameState $gameState = null;

    /** @var array<int, string> */
    public array $homeRosterInputs = [];

    /** @var array<int, string> */
    public array $awayRosterInputs = [];

    /** @var array<int, bool> */
    public array $homeStaffSelection = [];

    /** @var array<int, bool> */
    public array $awayStaffSelection = [];

    /** @var array<int, bool> */
    public array $homeLiberoSelection = [];

    /** @var array<int, bool> */
    public array $awayLiberoSelection = [];

    public string $homeCaptainSelection = '';

    public string $awayCaptainSelection = '';

    public function mount(?int $gameId = null): void
    {
        $this->gameId = $gameId;
    }

    public function updated(string $property): void
    {
        if (
            str_starts_with($property, 'homeRosterInputs.')
            || str_starts_with($property, 'homeLiberoSelection.')
            || $property === 'homeCaptainSelection'
        ) {
            $this->refreshTeamRosterValidation(TeamAB::TeamA);

            return;
        }

        if (
            str_starts_with($property, 'awayRosterInputs.')
            || str_starts_with($property, 'awayLiberoSelection.')
            || $property === 'awayCaptainSelection'
        ) {
            $this->refreshTeamRosterValidation(TeamAB::TeamB);
        }
    }

    public function openRosterModal(): void
    {
        $this->initializeRosterForm();
        $this->resetValidation();

        Flux::modal('submit-rosters')->show();
    }

    public function submitRosters(): void
    {
        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            $this->addError('rosters', 'No active game is available to record the rosters.');

            return;
        }

        $homeTeam = $activeGame->homeTeam;
        $awayTeam = $activeGame->awayTeam;

        $homePlayers = $homeTeam->players()->orderBy('id')->get();
        $awayPlayers = $awayTeam->players()->orderBy('id')->get();
        $homeStaff = $homeTeam->staff()->orderBy('id')->get();
        $awayStaff = $awayTeam->staff()->orderBy('id')->get();

        $homePlayerRoster = $this->validatedPlayerRoster(
            $homePlayers,
            $this->homeRosterInputs,
            $this->homeLiberoSelection,
            $this->homeCaptainSelection,
            'homeRosterInputs',
        );
        $awayPlayerRoster = $this->validatedPlayerRoster(
            $awayPlayers,
            $this->awayRosterInputs,
            $this->awayLiberoSelection,
            $this->awayCaptainSelection,
            'awayRosterInputs',
        );
        $homeStaffRoster = $this->selectedStaffRoster($homeStaff, $this->homeStaffSelection);
        $awayStaffRoster = $this->selectedStaffRoster($awayStaff, $this->awayStaffSelection);

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        Flux::modal('submit-rosters')->close();
        Flux::modal('confirm-rosters')->show();
    }

    public function confirmRosters(): void
    {
        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            $this->addError('rosters', 'No active game is available to record the rosters.');

            return;
        }

        $homeTeam = $activeGame->homeTeam;
        $awayTeam = $activeGame->awayTeam;

        $homePlayers = $homeTeam->players()->orderBy('id')->get();
        $awayPlayers = $awayTeam->players()->orderBy('id')->get();
        $homeStaff = $homeTeam->staff()->orderBy('id')->get();
        $awayStaff = $awayTeam->staff()->orderBy('id')->get();

        $homePlayerRoster = $this->validatedPlayerRoster(
            $homePlayers,
            $this->homeRosterInputs,
            $this->homeLiberoSelection,
            $this->homeCaptainSelection,
            'homeRosterInputs',
        );
        $awayPlayerRoster = $this->validatedPlayerRoster(
            $awayPlayers,
            $this->awayRosterInputs,
            $this->awayLiberoSelection,
            $this->awayCaptainSelection,
            'awayRosterInputs',
        );
        $homeStaffRoster = $this->selectedStaffRoster($homeStaff, $this->homeStaffSelection);
        $awayStaffRoster = $this->selectedStaffRoster($awayStaff, $this->awayStaffSelection);

        if ($this->getErrorBag()->isNotEmpty()) {
            Flux::modal('confirm-rosters')->close();
            Flux::modal('submit-rosters')->show();

            return;
        }

        $activeGame->replaceRosterForTeam(TeamAB::TeamA, $homePlayerRoster, $homeStaffRoster);
        $activeGame->replaceRosterForTeam(TeamAB::TeamB, $awayPlayerRoster, $awayStaffRoster);
        $activeGame->markRostersSubmitted();

        Flux::modal('confirm-rosters')->close();
        $this->resetValidation();
        $this->dispatch('game-event-recorded');
    }

    public function returnToRoster(): void
    {
        Flux::modal('confirm-rosters')->close();
        Flux::modal('submit-rosters')->show();
    }

    public function render(): View
    {
        $activeGame = $this->activeGame();
        $homePlayers = $activeGame?->homeTeam->players()->orderBy('id')->get() ?? collect();
        $awayPlayers = $activeGame?->awayTeam->players()->orderBy('id')->get() ?? collect();
        $homeStaff = $activeGame?->homeTeam->staff()->orderBy('id')->get() ?? collect();
        $awayStaff = $activeGame?->awayTeam->staff()->orderBy('id')->get() ?? collect();

        return view('livewire.roster-submission', [
            'hasSubmittedRosters' => $this->hasSubmittedRosters($activeGame),
            'isBeforeInitialToss' => $this->isBeforeInitialToss($activeGame),
            'homeTeamCode' => $activeGame?->homeTeam->country_code ?? 'Home Team',
            'awayTeamCode' => $activeGame?->awayTeam->country_code ?? 'Away Team',
            'homePlayers' => $homePlayers,
            'awayPlayers' => $awayPlayers,
            'homeStaff' => $homeStaff,
            'awayStaff' => $awayStaff,
            'homeRosterConfirmation' => $this->confirmationRosterForTeam(
                $homePlayers,
                $homeStaff,
                $this->homeRosterInputs,
                $this->homeLiberoSelection,
                $this->homeCaptainSelection,
                $this->homeStaffSelection,
            ),
            'awayRosterConfirmation' => $this->confirmationRosterForTeam(
                $awayPlayers,
                $awayStaff,
                $this->awayRosterInputs,
                $this->awayLiberoSelection,
                $this->awayCaptainSelection,
                $this->awayStaffSelection,
            ),
        ]);
    }

    private function hasSubmittedRosters(?Game $activeGame = null): bool
    {
        $game = $activeGame ?? $this->activeGame();

        return $game?->hasSubmittedInitialRosters() ?? false;
    }

    private function isBeforeInitialToss(?Game $activeGame = null): bool
    {
        $state = $this->resolvedGameState($activeGame);

        return ! $this->gameSideResolver()->hasRequiredToss($state)
            && ! $this->gameSideResolver()->requiresFifthSetToss($state);
    }

    private function initializeRosterForm(): void
    {
        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            $this->homeRosterInputs = [];
            $this->awayRosterInputs = [];
            $this->homeStaffSelection = [];
            $this->awayStaffSelection = [];
            $this->homeLiberoSelection = [];
            $this->awayLiberoSelection = [];
            $this->homeCaptainSelection = '';
            $this->awayCaptainSelection = '';

            return;
        }

        $this->homeRosterInputs = $this->existingRosterInputs(
            $activeGame->homeTeam->players()->orderBy('id')->get(),
            $activeGame->homePlayers()->get(),
        );
        $this->awayRosterInputs = $this->existingRosterInputs(
            $activeGame->awayTeam->players()->orderBy('id')->get(),
            $activeGame->awayPlayers()->get(),
        );
        $this->homeStaffSelection = $this->existingStaffSelection(
            $activeGame->homeTeam->staff()->orderBy('id')->get(),
            $activeGame->homeStaff()->get(),
        );
        $this->awayStaffSelection = $this->existingStaffSelection(
            $activeGame->awayTeam->staff()->orderBy('id')->get(),
            $activeGame->awayStaff()->get(),
        );
        $this->homeLiberoSelection = $this->existingLiberoSelection(
            $activeGame->homeTeam->players()->orderBy('id')->get(),
            $activeGame->homePlayers()->get(),
        );
        $this->awayLiberoSelection = $this->existingLiberoSelection(
            $activeGame->awayTeam->players()->orderBy('id')->get(),
            $activeGame->awayPlayers()->get(),
        );
        $this->homeCaptainSelection = $this->existingCaptainSelection(
            $activeGame->homePlayers()->get(),
        );
        $this->awayCaptainSelection = $this->existingCaptainSelection(
            $activeGame->awayPlayers()->get(),
        );
    }

    private function refreshTeamRosterValidation(TeamAB $team): void
    {
        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            return;
        }

        if ($team === TeamAB::TeamA) {
            $fieldPrefix = 'homeRosterInputs';
            $teamPlayers = $activeGame->homeTeam->players()->orderBy('id')->get();
            $inputs = $this->homeRosterInputs;
            $liberoSelection = $this->homeLiberoSelection;
            $captainSelection = $this->homeCaptainSelection;
        } else {
            $fieldPrefix = 'awayRosterInputs';
            $teamPlayers = $activeGame->awayTeam->players()->orderBy('id')->get();
            $inputs = $this->awayRosterInputs;
            $liberoSelection = $this->awayLiberoSelection;
            $captainSelection = $this->awayCaptainSelection;
        }

        $this->resetValidation($this->validationFieldsForTeam($teamPlayers, $fieldPrefix, $this->captainFieldPrefix($team)));

        $this->validatedPlayerRoster($teamPlayers, $inputs, $liberoSelection, $captainSelection, $fieldPrefix);
    }

    /**
     * @param  EloquentCollection<int, Player>  $teamPlayers
     * @param  array<int, string>  $inputs
     * @param  array<int, bool>  $liberoSelection
     * @return array<int, array{player: Player, number: int, is_captain: bool, is_libero: bool}>
     */
    private function validatedPlayerRoster(EloquentCollection $teamPlayers, array $inputs, array $liberoSelection, string $captainSelection, string $fieldPrefix): array
    {
        $selectedPlayers = [];
        $rosteredPlayerIds = [];
        $selectedNumbers = [];
        $liberoCount = 0;
        $nonLiberoCount = 0;

        foreach ($teamPlayers as $player) {
            $input = trim($inputs[$player->getKey()] ?? '');
            $isLibero = (bool) ($liberoSelection[$player->getKey()] ?? false);

            if ($input === '') {
                if ($isLibero) {
                    $this->addError("{$fieldPrefix}.{$player->getKey()}", 'Libero must be a rostered player.');
                }

                continue;
            }

            if (! preg_match('/^\d+$/', $input)) {
                $this->addError("{$fieldPrefix}.{$player->getKey()}", 'Use a roster number between 1 and 99.');

                continue;
            }

            $number = (int) $input;

            if ($number < 1 || $number > 99) {
                $this->addError("{$fieldPrefix}.{$player->getKey()}", 'Roster numbers must be between 1 and 99.');

                continue;
            }

            if (in_array($number, $selectedNumbers, true)) {
                $this->addError("{$fieldPrefix}.{$player->getKey()}", 'Roster numbers must be unique within the team.');

                continue;
            }

            $selectedNumbers[] = $number;
            $rosteredPlayerIds[] = $player->getKey();
            $selectedPlayers[] = [
                'player' => $player,
                'number' => $number,
                'is_captain' => false,
                'is_libero' => $isLibero,
            ];

            if ($isLibero) {
                $liberoCount++;
            } else {
                $nonLiberoCount++;
            }
        }

        if ($liberoCount > 2) {
            $this->addError($fieldPrefix, 'A team can have at most two liberos.');
        }

        if ($nonLiberoCount < 6) {
            $this->addError($fieldPrefix, 'A team must have at least six non-libero players.');
        }

        if ($nonLiberoCount > 12) {
            $this->addError($fieldPrefix, 'A team can have at most 12 non-libero players.');
        }

        if ($captainSelection === '') {
            $this->addError($this->captainFieldFromRosterField($fieldPrefix), 'Select a captain.');

            return $selectedPlayers;
        }

        $captainPlayerId = (int) $captainSelection;
        if (! in_array($captainPlayerId, $rosteredPlayerIds, true)) {
            $this->addError($this->captainFieldFromRosterField($fieldPrefix), 'Captain must be one of the rostered players.');

            return $selectedPlayers;
        }

        foreach ($selectedPlayers as $index => $selectedPlayer) {
            if ($selectedPlayer['player']->getKey() === $captainPlayerId) {
                $selectedPlayers[$index]['is_captain'] = true;

                break;
            }
        }

        return $selectedPlayers;
    }

    /**
     * @param  EloquentCollection<int, Staff>  $teamStaff
     * @param  array<int, bool>  $selection
     * @return array<int, Staff>
     */
    private function selectedStaffRoster(EloquentCollection $teamStaff, array $selection): array
    {
        return $teamStaff
            ->filter(fn (Staff $staff): bool => (bool) ($selection[$staff->getKey()] ?? false))
            ->values()
            ->all();
    }

    /**
     * @param  EloquentCollection<int, Player>  $teamPlayers
     * @param  EloquentCollection<int, Player>  $rosteredPlayers
     * @return array<int, string>
     */
    private function existingRosterInputs(EloquentCollection $teamPlayers, EloquentCollection $rosteredPlayers): array
    {
        $inputs = [];

        foreach ($teamPlayers as $player) {
            /** @var Player|null $rosteredPlayer */
            $rosteredPlayer = $rosteredPlayers->firstWhere($player->getKeyName(), $player->getKey());

            $inputs[$player->getKey()] = $rosteredPlayer === null
                ? ''
                : (string) $rosteredPlayer->roster->number;
        }

        return $inputs;
    }

    /**
     * @param  EloquentCollection<int, Player>  $teamPlayers
     * @param  EloquentCollection<int, Player>  $rosteredPlayers
     * @return array<int, bool>
     */
    private function existingLiberoSelection(EloquentCollection $teamPlayers, EloquentCollection $rosteredPlayers): array
    {
        $selection = [];

        foreach ($teamPlayers as $player) {
            /** @var Player|null $rosteredPlayer */
            $rosteredPlayer = $rosteredPlayers->firstWhere($player->getKeyName(), $player->getKey());

            $selection[$player->getKey()] = $rosteredPlayer?->roster->is_libero ?? false;
        }

        return $selection;
    }

    /**
     * @param  EloquentCollection<int, Player>  $rosteredPlayers
     */
    private function existingCaptainSelection(EloquentCollection $rosteredPlayers): string
    {
        /** @var Player|null $captain */
        $captain = $rosteredPlayers->first(fn (Player $player): bool => $player->roster->is_captain);

        return $captain === null ? '' : (string) $captain->getKey();
    }

    /**
     * @param  EloquentCollection<int, Staff>  $teamStaff
     * @param  EloquentCollection<int, Staff>  $rosteredStaff
     * @return array<int, bool>
     */
    private function existingStaffSelection(EloquentCollection $teamStaff, EloquentCollection $rosteredStaff): array
    {
        $selection = [];

        foreach ($teamStaff as $staffMember) {
            $selection[$staffMember->getKey()] = $rosteredStaff
                ->contains(fn (Staff $rosteredStaffMember): bool => $rosteredStaffMember->getKey() === $staffMember->getKey());
        }

        return $selection;
    }

    private function resolvedGameState(?Game $activeGame = null): GameState
    {
        if ($activeGame !== null) {
            return $activeGame->stateAt();
        }

        if ($this->gameId === null) {
            return $this->gameState ?? GameState::initial();
        }

        $activeGame = Game::query()->whereKey($this->gameId)->first();

        return $activeGame?->stateAt() ?? ($this->gameState ?? GameState::initial());
    }

    private function activeGame(): ?Game
    {
        if ($this->gameId === null) {
            return null;
        }

        return Game::query()
            ->with(['homeTeam', 'awayTeam'])
            ->whereKey($this->gameId)
            ->first();
    }

    private function gameSideResolver(): GameSideResolver
    {
        return app(GameSideResolver::class);
    }

    private function captainFieldPrefix(TeamAB $team): string
    {
        return $team === TeamAB::TeamA
            ? 'homeCaptainSelection'
            : 'awayCaptainSelection';
    }

    private function captainFieldFromRosterField(string $fieldPrefix): string
    {
        return $fieldPrefix === 'homeRosterInputs'
            ? 'homeCaptainSelection'
            : 'awayCaptainSelection';
    }

    /**
     * @param  EloquentCollection<int, Player>  $teamPlayers
     * @return array<int, string>
     */
    private function validationFieldsForTeam(EloquentCollection $teamPlayers, string $fieldPrefix, string $captainField): array
    {
        $fields = [$fieldPrefix, $captainField];

        foreach ($teamPlayers as $player) {
            $fields[] = "{$fieldPrefix}.{$player->getKey()}";
        }

        return $fields;
    }

    /**
     * @param  EloquentCollection<int, Player>  $teamPlayers
     * @param  EloquentCollection<int, Staff>  $teamStaff
     * @param  array<int, string>  $inputs
     * @param  array<int, bool>  $liberoSelection
     * @param  array<int, bool>  $staffSelection
     * @return array{
     *     players: Collection<int, array{name: string, number: int, is_captain: bool}>,
     *     liberos: Collection<int, array{name: string, number: int}>,
     *     bench: Collection<int, array{name: string, role: string}>
     * }
     */
    private function confirmationRosterForTeam(
        EloquentCollection $teamPlayers,
        EloquentCollection $teamStaff,
        array $inputs,
        array $liberoSelection,
        string $captainSelection,
        array $staffSelection,
    ): array {
        $captainPlayerId = $captainSelection === '' ? null : (int) $captainSelection;

        $rosteredPlayers = $teamPlayers
            ->map(function (Player $player) use ($inputs, $liberoSelection, $captainPlayerId): ?array {
                $input = trim($inputs[$player->getKey()] ?? '');

                if ($input === '' || ! preg_match('/^\d+$/', $input)) {
                    return null;
                }

                $number = (int) $input;

                if ($number < 1 || $number > 99) {
                    return null;
                }

                return [
                    'name' => $this->formattedPersonName($player->last_name, $player->first_name),
                    'number' => $number,
                    'is_captain' => $captainPlayerId !== null && $player->getKey() === $captainPlayerId,
                    'is_libero' => (bool) ($liberoSelection[$player->getKey()] ?? false),
                ];
            })
            ->filter()
            ->sortBy('number')
            ->values();

        $selectedStaff = $teamStaff
            ->filter(fn (Staff $staff): bool => (bool) ($staffSelection[$staff->getKey()] ?? false))
            ->sortBy(fn (Staff $staff): array => [
                $this->staffOrder($staff->role),
                $staff->role === StaffRole::AssistantCoach ? mb_strtolower($staff->last_name) : '',
                mb_strtolower($staff->first_name),
            ])
            ->values();

        $assistantCoachCount = 0;

        $selectedStaff = $selectedStaff
            ->map(function (Staff $staff) use (&$assistantCoachCount): array {
                $roleCode = match ($staff->role) {
                    StaffRole::Coach => 'C',
                    StaffRole::AssistantCoach => 'AC'.(++$assistantCoachCount),
                    StaffRole::Therapist => 'T',
                    StaffRole::Doctor => 'D',
                };

                return [
                    'name' => $this->formattedPersonName($staff->last_name, $staff->first_name),
                    'role' => $roleCode,
                ];
            })
            ->values();

        return [
            'players' => $rosteredPlayers
                ->reject(fn (array $player): bool => $player['is_libero'])
                ->map(fn (array $player): array => [
                    'name' => $player['name'],
                    'number' => $player['number'],
                    'is_captain' => $player['is_captain'],
                ])
                ->values(),
            'liberos' => $rosteredPlayers
                ->filter(fn (array $player): bool => $player['is_libero'])
                ->map(fn (array $player): array => [
                    'name' => $player['name'],
                    'number' => $player['number'],
                ])
                ->values(),
            'bench' => $selectedStaff,
        ];
    }

    private function formattedPersonName(string $lastName, string $firstName): string
    {
        $firstInitial = mb_substr($firstName, 0, 1);

        return "{$lastName} {$firstInitial}.";
    }

    private function staffOrder(StaffRole $role): int
    {
        return match ($role) {
            StaffRole::Coach => 0,
            StaffRole::AssistantCoach => 1,
            StaffRole::Therapist => 2,
            StaffRole::Doctor => 3,
        };
    }
}
