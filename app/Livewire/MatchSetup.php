<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Competition;
use App\Models\Game;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use App\Services\CurrentMatchResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MatchSetup extends Component
{
    public ?Game $game = null;

    public string $step = 'missing-match';

    public string $competitionName = '';

    public bool $editingCompetition = false;

    public string $matchNumber = '';

    public string $matchCountryCode = '';

    public string $city = '';

    public string $hall = '';

    public string $matchDateTime = '';

    public string $division = 'Men';

    public string $pool = '';

    public string $category = 'Senior';

    public string $homeTeamName = '';

    public string $homeTeamCountryCode = '';

    public string $awayTeamName = '';

    public string $awayTeamCountryCode = '';

    /** @var array<int, array{first_name: string, last_name: string, number: string, is_captain: bool, is_libero: bool}> */
    public array $homePlayerRows = [];

    /** @var array<int, array{first_name: string, last_name: string, number: string, is_captain: bool, is_libero: bool}> */
    public array $awayPlayerRows = [];

    public string $homeCaptainSelection = '';

    public string $awayCaptainSelection = '';

    /** @var array<int, array{role: string, first_name: string, last_name: string}> */
    public array $homeStaffRows = [];

    /** @var array<int, array{role: string, first_name: string, last_name: string}> */
    public array $awayStaffRows = [];

    /** @var array<int, array{role: string, first_name: string, last_name: string, country_code: string}> */
    public array $officialRows = [];

    public function mount(CurrentMatchResolver $currentMatchResolver): void
    {
        $this->synchronizeCompetitionState();
        $this->synchronizeState($currentMatchResolver->current());
    }

    public function saveCompetition(): void
    {
        $this->competitionName = trim($this->competitionName);

        $validated = $this->validate([
            'competitionName' => ['required', 'string', 'max:255'],
        ]);

        $competition = Competition::ensureSingleton();
        $competition->forceFill([
            'name' => trim((string) $validated['competitionName']),
        ])->save();

        $this->editingCompetition = false;
        $this->synchronizeCompetitionState();
        $this->synchronizeState($this->game?->fresh(['homeTeam', 'awayTeam', 'officials']));
    }

    public function editCompetition(): void
    {
        if (! Competition::setupComplete()) {
            return;
        }

        $this->editingCompetition = true;
        $this->step = 'competition';
    }

    public function createMatch(): void
    {
        if (! Competition::setupComplete()) {
            $this->step = 'competition';

            return;
        }

        if ($this->game !== null) {
            $this->synchronizeState($this->game->fresh(['homeTeam', 'awayTeam', 'officials']));

            return;
        }

        $this->synchronizeState(Game::ensureSingleton());
    }

    public function openStep(string $step): void
    {
        if (! in_array($step, ['competition', 'match-details', 'rosters', 'officials', 'ready'], true)) {
            return;
        }

        if ($step === 'competition') {
            $this->step = 'competition';

            return;
        }

        if ($this->game === null) {
            return;
        }

        if ($this->game->setupLocked()) {
            $this->step = 'ready';

            return;
        }

        $requiredStep = $this->resolver()->nextStep($this->game);
        $availableSteps = ['competition'];

        if (! Competition::setupComplete()) {
            $this->step = 'competition';

            return;
        }

        $availableSteps[] = 'match-details';

        if ($this->game->hasCompleteMatchDetails()) {
            $availableSteps[] = 'rosters';
        }

        if ($this->game->hasSubmittedInitialRosters()) {
            $availableSteps[] = 'officials';
        }

        if ($this->resolver()->isSetupComplete($this->game)) {
            $availableSteps[] = 'ready';
        }

        if (in_array($step, $availableSteps, true)) {
            $this->step = $step;

            return;
        }

        $this->step = $requiredStep;
    }

    public function addPlayerRow(string $side): void
    {
        if ($this->setupLocked()) {
            return;
        }

        if ($side === 'home') {
            $this->homePlayerRows[] = $this->emptyPlayerRow();

            return;
        }

        if ($side === 'away') {
            $this->awayPlayerRows[] = $this->emptyPlayerRow();
        }
    }

    public function removePlayerRow(string $side, int $index): void
    {
        if ($this->setupLocked()) {
            return;
        }

        if ($side === 'home') {
            if (count($this->homePlayerRows) <= 6) {
                return;
            }

            $this->adjustCaptainSelectionAfterRowRemoval('home', $index);
            unset($this->homePlayerRows[$index]);
            $this->homePlayerRows = array_values($this->homePlayerRows);

            return;
        }

        if ($side === 'away') {
            if (count($this->awayPlayerRows) <= 6) {
                return;
            }

            $this->adjustCaptainSelectionAfterRowRemoval('away', $index);
            unset($this->awayPlayerRows[$index]);
            $this->awayPlayerRows = array_values($this->awayPlayerRows);
        }
    }

    public function saveMatchDetails(): void
    {
        $game = $this->activeGameForSetup();

        if ($game === null) {
            return;
        }

        if (! $this->ensureSetupEditable($game)) {

            return;
        }

        $validated = $this->validate([
            'matchNumber' => ['required', 'integer', 'min:1', 'max:99'],
            'matchCountryCode' => ['required', 'regex:/^[A-Za-z]{3}$/'],
            'city' => ['required', 'string', 'max:255'],
            'hall' => ['required', 'string', 'max:255'],
            'matchDateTime' => ['required', 'date'],
            'division' => ['required', 'in:Men,Women'],
            'pool' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:Senior,Junior,Youth'],
            'homeTeamName' => ['required', 'string', 'max:255'],
            'homeTeamCountryCode' => ['required', 'regex:/^[A-Za-z]{3}$/'],
            'awayTeamName' => ['required', 'string', 'max:255'],
            'awayTeamCountryCode' => ['required', 'regex:/^[A-Za-z]{3}$/'],
        ]);

        $game->homeTeam->forceFill([
            'name' => trim((string) $validated['homeTeamName']),
            'country_code' => $this->normalizeCountryCode($validated['homeTeamCountryCode']),
        ])->save();

        $game->awayTeam->forceFill([
            'name' => trim((string) $validated['awayTeamName']),
            'country_code' => $this->normalizeCountryCode($validated['awayTeamCountryCode']),
        ])->save();

        $game->forceFill([
            'number' => (int) $validated['matchNumber'],
            'country_code' => $this->normalizeCountryCode($validated['matchCountryCode']),
            'city' => trim((string) $validated['city']),
            'hall' => trim((string) $validated['hall']),
            'date_time' => $validated['matchDateTime'],
            'division' => $validated['division'],
            'pool' => trim((string) $validated['pool']),
            'category' => $validated['category'],
        ])->save();
        $game->synchronizeStatus();

        $this->resetValidation();
        $this->synchronizeState($game->fresh(['homeTeam', 'awayTeam', 'officials']));
        $this->step = 'rosters';
    }

    public function saveRosters(): void
    {
        $game = $this->activeGameForSetup();

        if ($game === null) {
            return;
        }

        if (! $this->ensureSetupEditable($game)) {

            return;
        }

        $this->resetValidation();

        $homePlayers = $this->validatedPlayerRows($this->homePlayerRows, 'homePlayerRows', $this->homeCaptainSelection);
        $awayPlayers = $this->validatedPlayerRows($this->awayPlayerRows, 'awayPlayerRows', $this->awayCaptainSelection);
        $homeStaff = $this->validatedStaffRows($this->homeStaffRows, 'homeStaffRows');
        $awayStaff = $this->validatedStaffRows($this->awayStaffRows, 'awayStaffRows');

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () use ($game, $homePlayers, $awayPlayers, $homeStaff, $awayStaff): void {
            $this->syncTeamRoster($game, $game->homeTeam, TeamAB::TeamA, $homePlayers, $homeStaff);
            $this->syncTeamRoster($game, $game->awayTeam, TeamAB::TeamB, $awayPlayers, $awayStaff);
            $game->markRostersSubmitted();
        });

        $this->synchronizeState($game->fresh(['homeTeam', 'awayTeam', 'officials']));
        $this->step = 'officials';
    }

    public function saveOfficials(): void
    {
        $game = $this->activeGameForSetup();

        if ($game === null) {
            return;
        }

        if (! $this->ensureSetupEditable($game)) {

            return;
        }

        $this->resetValidation();

        $officials = $this->validatedOfficialRows();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $game->replaceOfficials($officials);

        $this->synchronizeState($game->fresh(['homeTeam', 'awayTeam', 'officials']));
        $this->step = 'ready';
    }

    public function render(): View
    {
        return view('livewire.match-setup', [
            'currentCompetitionName' => $this->game?->competitionName() ?? $this->competitionName,
            'competitionConfigured' => Competition::setupComplete(),
            'currentRequiredStep' => $this->resolver()->nextStep($this->game),
            'isSetupComplete' => $this->game !== null && $this->resolver()->isSetupComplete($this->game),
            'isSetupLocked' => $this->setupLocked(),
            'setupSteps' => [
                'competition' => 'Competition',
                'match-details' => 'Match details',
                'rosters' => 'Team rosters',
                'officials' => 'Officials',
                'ready' => 'Ready state',
            ],
        ]);
    }

    private function synchronizeState(?Game $game): void
    {
        $this->game = $game;

        if (! Competition::setupComplete()) {
            $this->step = 'competition';

            if ($game === null) {
                $this->resetFormState();
            }

            return;
        }

        if ($game === null) {
            $this->step = 'missing-match';
            $this->resetFormState();

            return;
        }

        $game->loadMissing(['homeTeam', 'awayTeam', 'officials']);

        $this->matchNumber = (string) $game->number;
        $this->matchCountryCode = $game->country_code;
        $this->city = $game->city;
        $this->hall = $game->hall;
        $this->matchDateTime = $game->date_time->format('Y-m-d\TH:i');
        $this->division = $game->division === '' ? 'Men' : $game->division;
        $this->pool = $game->pool;
        $this->category = $game->category === '' ? 'Senior' : $game->category;
        $this->homeTeamName = $game->homeTeam->name;
        $this->homeTeamCountryCode = $game->homeTeam->country_code;
        $this->awayTeamName = $game->awayTeam->name;
        $this->awayTeamCountryCode = $game->awayTeam->country_code;
        $this->homePlayerRows = $this->playerRowsForTeam($game->homeTeam, $game->homePlayers()->get());
        $this->awayPlayerRows = $this->playerRowsForTeam($game->awayTeam, $game->awayPlayers()->get());
        $this->homeCaptainSelection = $this->captainSelectionForRows($this->homePlayerRows);
        $this->awayCaptainSelection = $this->captainSelectionForRows($this->awayPlayerRows);
        $this->homeStaffRows = $this->staffRowsForTeam($game->homeTeam, $game->homeStaff()->get());
        $this->awayStaffRows = $this->staffRowsForTeam($game->awayTeam, $game->awayStaff()->get());
        $this->officialRows = $this->officialRowsForGame($game);
        $this->step = $game->setupLocked()
            ? 'ready'
            : $this->resolver()->nextStep($game);
    }

    private function resetFormState(): void
    {
        $this->matchNumber = '';
        $this->matchCountryCode = '';
        $this->city = '';
        $this->hall = '';
        $this->matchDateTime = '';
        $this->division = 'Men';
        $this->pool = '';
        $this->category = 'Senior';
        $this->homeTeamName = '';
        $this->homeTeamCountryCode = '';
        $this->awayTeamName = '';
        $this->awayTeamCountryCode = '';
        $this->homePlayerRows = $this->emptyPlayerRows();
        $this->awayPlayerRows = $this->emptyPlayerRows();
        $this->homeCaptainSelection = '';
        $this->awayCaptainSelection = '';
        $this->homeStaffRows = $this->emptyStaffRows();
        $this->awayStaffRows = $this->emptyStaffRows();
        $this->officialRows = $this->emptyOfficialRows();
    }

    private function synchronizeCompetitionState(): void
    {
        $competition = Competition::current();

        $this->competitionName = $competition !== null
            ? $competition->name
            : '';
    }

    /**
     * @param  array<int, array{first_name: string, last_name: string, number: int, is_captain: bool, is_libero: bool}>  $players
     * @param  array<int, array{role: StaffRole, first_name: string, last_name: string}>  $staffRows
     */
    private function syncTeamRoster(Game $game, Team $team, TeamAB $teamAb, array $players, array $staffRows): void
    {
        Player::query()->where('team_id', $team->getKey())->delete();
        Staff::query()->where('team_id', $team->getKey())->delete();

        $createdPlayers = [];

        foreach ($players as $playerRow) {
            $player = $team->players()->create([
                'game_id' => $game->getKey(),
                'first_name' => $playerRow['first_name'],
                'last_name' => $playerRow['last_name'],
            ]);

            $createdPlayers[] = [
                'player' => $player,
                'number' => $playerRow['number'],
                'is_captain' => $playerRow['is_captain'],
                'is_libero' => $playerRow['is_libero'],
            ];
        }

        $createdStaff = [];

        foreach ($staffRows as $staffRow) {
            $createdStaff[] = $team->staff()->create([
                'game_id' => $game->getKey(),
                'first_name' => $staffRow['first_name'],
                'last_name' => $staffRow['last_name'],
                'role' => $staffRow['role'],
            ]);
        }

        $game->replaceRosterForTeam($teamAb, $createdPlayers, $createdStaff);
    }

    /**
     * @param  array<int, array{first_name: string, last_name: string, number: string, is_captain: bool, is_libero: bool}>  $rows
     * @return array<int, array{first_name: string, last_name: string, number: int, is_captain: bool, is_libero: bool}>
     */
    private function validatedPlayerRows(array $rows, string $fieldPrefix, string $captainSelection): array
    {
        $validatedRows = [];
        $selectedNumbers = [];
        $captainCount = 0;
        $liberoCount = 0;
        $nonLiberoCount = 0;

        foreach ($rows as $index => $row) {
            $firstName = trim($row['first_name']);
            $lastName = trim($row['last_name']);
            $numberInput = trim($row['number']);
            $isCaptain = $captainSelection !== '' && (int) $captainSelection === $index;
            $isLibero = $row['is_libero'];
            $hasAnyValue = $firstName !== '' || $lastName !== '' || $numberInput !== '';

            if (! $hasAnyValue) {
                if ($isLibero) {
                    $this->addError("{$fieldPrefix}.{$index}", 'Only rostered players can be marked as libero.');
                }

                continue;
            }

            if ($firstName === '') {
                $this->addError("{$fieldPrefix}.{$index}.first_name", 'Enter the player first name.');
            }

            if ($lastName === '') {
                $this->addError("{$fieldPrefix}.{$index}.last_name", 'Enter the player last name.');
            }

            if ($numberInput === '') {
                $this->addError("{$fieldPrefix}.{$index}.number", 'Enter a roster number between 1 and 99.');

                continue;
            }

            if (! preg_match('/^\d+$/', $numberInput)) {
                $this->addError("{$fieldPrefix}.{$index}.number", 'Roster numbers must be numeric.');

                continue;
            }

            $number = (int) $numberInput;

            if ($number < 1 || $number > 99) {
                $this->addError("{$fieldPrefix}.{$index}.number", 'Roster numbers must be between 1 and 99.');

                continue;
            }

            if (in_array($number, $selectedNumbers, true)) {
                $this->addError("{$fieldPrefix}.{$index}.number", 'Roster numbers must be unique within the team.');

                continue;
            }

            $selectedNumbers[] = $number;
            $captainCount += $isCaptain ? 1 : 0;
            $liberoCount += $isLibero ? 1 : 0;
            $nonLiberoCount += $isLibero ? 0 : 1;

            $validatedRows[] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'number' => $number,
                'is_captain' => $isCaptain,
                'is_libero' => $isLibero,
            ];
        }

        if ($captainCount !== 1) {
            $this->addError($fieldPrefix, 'Select exactly one captain for the roster.');
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

        return $validatedRows;
    }

    /**
     * @param  array<int, array{role: string, first_name: string, last_name: string}>  $rows
     * @return array<int, array{role: StaffRole, first_name: string, last_name: string}>
     */
    private function validatedStaffRows(array $rows, string $fieldPrefix): array
    {
        $validatedRows = [];

        foreach ($rows as $index => $row) {
            $firstName = trim($row['first_name']);
            $lastName = trim($row['last_name']);

            if ($firstName === '' && $lastName === '') {
                continue;
            }

            if ($firstName === '') {
                $this->addError("{$fieldPrefix}.{$index}.first_name", 'Enter the staff first name.');
            }

            if ($lastName === '') {
                $this->addError("{$fieldPrefix}.{$index}.last_name", 'Enter the staff last name.');
            }

            $validatedRows[] = [
                'role' => StaffRole::from($row['role']),
                'first_name' => $firstName,
                'last_name' => $lastName,
            ];
        }

        return $validatedRows;
    }

    /**
     * @return array<int, array{
     *     role: OfficialRole,
     *     first_name: string,
     *     last_name: string,
     *     country_code: string
     * }>
     */
    private function validatedOfficialRows(): array
    {
        $validatedRows = [];

        foreach ($this->officialRows as $index => $row) {
            $firstName = trim($row['first_name']);
            $lastName = trim($row['last_name']);
            $countryCode = $this->normalizeCountryCode($row['country_code']);

            if ($firstName === '') {
                $this->addError("officialRows.{$index}.first_name", 'Enter the official first name.');
            }

            if ($lastName === '') {
                $this->addError("officialRows.{$index}.last_name", 'Enter the official last name.');
            }

            if (! preg_match('/^[A-Z]{3}$/', $countryCode)) {
                $this->addError("officialRows.{$index}.country_code", 'Use a three-letter country code.');
            }

            $validatedRows[] = [
                'role' => OfficialRole::from($row['role']),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'country_code' => $countryCode,
            ];
        }

        return $validatedRows;
    }

    /**
     * @param  Collection<int, Player>  $rosteredPlayers
     * @return array<int, array{first_name: string, last_name: string, number: string, is_captain: bool, is_libero: bool}>
     */
    private function playerRowsForTeam(Team $team, Collection $rosteredPlayers): array
    {
        $rows = $team->players()
            ->orderBy('id')
            ->get()
            ->map(function (Player $player) use ($rosteredPlayers): array {
                /** @var Player|null $rosteredPlayer */
                $rosteredPlayer = $rosteredPlayers->firstWhere($player->getKeyName(), $player->getKey());

                return [
                    'first_name' => $player->first_name,
                    'last_name' => $player->last_name,
                    'number' => $rosteredPlayer === null ? '' : (string) $rosteredPlayer->roster->number,
                    'is_captain' => $rosteredPlayer === null ? false : $rosteredPlayer->roster->is_captain,
                    'is_libero' => $rosteredPlayer === null ? false : $rosteredPlayer->roster->is_libero,
                ];
            })
            ->values()
            ->all();

        while (count($rows) < 6) {
            $rows[] = $this->emptyPlayerRow();
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Staff>  $rosteredStaff
     * @return array<int, array{role: string, first_name: string, last_name: string}>
     */
    private function staffRowsForTeam(Team $team, Collection $rosteredStaff): array
    {
        return collect(StaffRole::cases())
            ->map(function (StaffRole $role) use ($team, $rosteredStaff): array {
                /** @var Staff|null $staffMember */
                $staffMember = $rosteredStaff->first(fn (Staff $staff): bool => $staff->roster->role === $role);
                $staffMember ??= $team->staff()->where('role', $role)->first();

                return [
                    'role' => $role->value,
                    'first_name' => $staffMember === null ? '' : $staffMember->first_name,
                    'last_name' => $staffMember === null ? '' : $staffMember->last_name,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{role: string, first_name: string, last_name: string, country_code: string}>
     */
    private function officialRowsForGame(Game $game): array
    {
        $officials = $game->officials()->get();

        return collect(OfficialRole::cases())
            ->map(function (OfficialRole $role) use ($officials): array {
                $official = $officials->first(
                    fn (Official $assignedOfficial): bool => $assignedOfficial->assignment->role === $role,
                );

                return [
                    'role' => $role->value,
                    'first_name' => $official === null ? '' : $official->first_name,
                    'last_name' => $official === null ? '' : $official->last_name,
                    'country_code' => $official === null ? '' : $official->country_code,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{first_name: string, last_name: string, number: string, is_captain: bool, is_libero: bool}>
     */
    private function emptyPlayerRows(): array
    {
        return array_fill(0, 6, $this->emptyPlayerRow());
    }

    /**
     * @return array{first_name: string, last_name: string, number: string, is_captain: bool, is_libero: bool}
     */
    private function emptyPlayerRow(): array
    {
        return [
            'first_name' => '',
            'last_name' => '',
            'number' => '',
            'is_captain' => false,
            'is_libero' => false,
        ];
    }

    /**
     * @return array<int, array{role: string, first_name: string, last_name: string}>
     */
    private function emptyStaffRows(): array
    {
        return collect(StaffRole::cases())
            ->map(fn (StaffRole $role): array => [
                'role' => $role->value,
                'first_name' => '',
                'last_name' => '',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{role: string, first_name: string, last_name: string, country_code: string}>
     */
    private function emptyOfficialRows(): array
    {
        return collect(OfficialRole::cases())
            ->map(fn (OfficialRole $role): array => [
                'role' => $role->value,
                'first_name' => '',
                'last_name' => '',
                'country_code' => '',
            ])
            ->values()
            ->all();
    }

    private function activeGameForSetup(): ?Game
    {
        $game = $this->game ?? $this->resolver()->current();

        if ($game !== null) {
            return $game;
        }

        $this->addError('setup', 'Create the current match before continuing.');

        return null;
    }

    private function normalizeCountryCode(string $countryCode): string
    {
        return strtoupper(trim($countryCode));
    }

    /**
     * @param  array<int, array{first_name: string, last_name: string, number: string, is_captain: bool, is_libero: bool}>  $rows
     */
    private function captainSelectionForRows(array $rows): string
    {
        foreach ($rows as $index => $row) {
            if ($row['is_captain']) {
                return (string) $index;
            }
        }

        return '';
    }

    private function adjustCaptainSelectionAfterRowRemoval(string $side, int $removedIndex): void
    {
        $selection = $side === 'home'
            ? $this->homeCaptainSelection
            : $this->awayCaptainSelection;

        if ($selection === '') {
            return;
        }

        $selectedIndex = (int) $selection;

        if ($selectedIndex === $removedIndex) {
            if ($side === 'home') {
                $this->homeCaptainSelection = '';
            } else {
                $this->awayCaptainSelection = '';
            }

            return;
        }

        if ($selectedIndex > $removedIndex) {
            if ($side === 'home') {
                $this->homeCaptainSelection = (string) ($selectedIndex - 1);
            } else {
                $this->awayCaptainSelection = (string) ($selectedIndex - 1);
            }
        }
    }

    private function setupLocked(): bool
    {
        return $this->game?->setupLocked() ?? false;
    }

    private function ensureSetupEditable(Game $game): bool
    {
        try {
            $game->assertSetupEditable();
        } catch (InvalidGameEventTransition $exception) {
            $this->addError('setup', $exception->getMessage());

            return false;
        }

        return true;
    }

    private function resolver(): CurrentMatchResolver
    {
        return app(CurrentMatchResolver::class);
    }
}
