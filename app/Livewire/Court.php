<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\MisconductSanction;
use App\Enums\MisconductSubjectType;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Events\Payloads\MisconductRecordedPayload;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Player;
use App\Models\Staff;
use App\Services\GameSideResolver;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Court extends Component
{
    #[Reactive]
    public ?int $gameId = null;

    #[Reactive]
    public ?GameState $gameState = null;

    public ?string $pendingDelayWarningTeam = null;

    public ?string $pendingDelayPenaltyTeam = null;

    public ?string $delaySanctionRecordedTitle = null;

    public ?string $delaySanctionRecordedMessage = null;

    public ?string $pendingMisconductTeam = null;

    public ?string $pendingMisconductSanction = null;

    public ?string $pendingMisconductSubjectType = null;

    public ?int $pendingMisconductSubjectId = null;

    public ?string $pendingMisconductSubjectLabel = null;

    public function mount(?int $gameId = null): void
    {
        $this->gameId = $gameId;
    }

    public function render(): View
    {
        return view('livewire.court', $this->courtContext());
    }

    public function requestDelayWarning(string $team): void
    {
        $this->pendingDelayWarningTeam = TeamAB::from($team)->value;

        Flux::modal('record-delay-warning-confirm')->show();
    }

    public function requestDelayPenalty(string $team): void
    {
        $this->pendingDelayPenaltyTeam = TeamAB::from($team)->value;

        Flux::modal('record-delay-penalty-confirm')->show();
    }

    public function requestMisconduct(string $team, string $sanction): void
    {
        $this->pendingMisconductTeam = TeamAB::from($team)->value;
        $this->pendingMisconductSanction = MisconductSanction::from($sanction)->value;
        $this->pendingMisconductSubjectType = null;
        $this->pendingMisconductSubjectId = null;
        $this->pendingMisconductSubjectLabel = null;

        Flux::modal('select-misconduct-subject')->show();
    }

    public function selectMisconductSubject(string $subjectType, int $subjectId): void
    {
        if ($this->pendingMisconductTeam === null || $this->pendingMisconductSanction === null) {
            return;
        }

        $this->pendingMisconductSubjectType = MisconductSubjectType::from($subjectType)->value;
        $this->pendingMisconductSubjectId = $subjectId;
        $this->pendingMisconductSubjectLabel = $this->misconductSubjectLabel(
            MisconductSubjectType::from($subjectType),
            $subjectId,
        );

        Flux::modal('select-misconduct-subject')->close();
        Flux::modal('record-misconduct-confirm')->show();
    }

    public function recordPendingDelayWarning(): void
    {
        if ($this->pendingDelayWarningTeam === null || $this->gameId === null) {
            return;
        }

        $team = TeamAB::from($this->pendingDelayWarningTeam);
        $game = Game::query()->findSole($this->gameId);
        $game->recordDelayWarning($team);

        $this->pendingDelayWarningTeam = null;
        $this->delaySanctionRecordedTitle = 'Delay warning recorded';
        $this->delaySanctionRecordedMessage = "A delay warning has been recorded for {$team->label()}.";

        Flux::modal('record-delay-warning-confirm')->close();
        Flux::modal('delay-sanction-recorded')->show();

        $this->dispatch('game-event-recorded');
    }

    public function recordPendingDelayPenalty(): void
    {
        if ($this->pendingDelayPenaltyTeam === null || $this->gameId === null) {
            return;
        }

        $team = TeamAB::from($this->pendingDelayPenaltyTeam);
        $game = Game::query()->findSole($this->gameId);
        $game->recordDelayPenalty($team);

        $this->pendingDelayPenaltyTeam = null;
        $this->delaySanctionRecordedTitle = 'Delay penalty recorded';
        $this->delaySanctionRecordedMessage = "A delay penalty has been recorded for {$team->label()}.";

        Flux::modal('record-delay-penalty-confirm')->close();
        Flux::modal('delay-sanction-recorded')->show();

        $this->dispatch('game-event-recorded');
    }

    public function recordPendingMisconduct(): void
    {
        if (
            $this->pendingMisconductTeam === null
            || $this->pendingMisconductSanction === null
            || $this->pendingMisconductSubjectType === null
            || $this->pendingMisconductSubjectId === null
            || $this->gameId === null
        ) {
            return;
        }

        $team = TeamAB::from($this->pendingMisconductTeam);
        $sanction = MisconductSanction::from($this->pendingMisconductSanction);
        $subjectType = MisconductSubjectType::from($this->pendingMisconductSubjectType);
        $game = Game::query()->findSole($this->gameId);

        $game->recordMisconduct($team, $subjectType, $this->pendingMisconductSubjectId, $sanction);

        $this->delaySanctionRecordedTitle = 'Misconduct recorded';
        $this->delaySanctionRecordedMessage = "{$this->misconductSanctionLabel($sanction)} has been recorded for {$this->pendingMisconductSubjectLabel}.";
        $this->resetPendingMisconduct();

        Flux::modal('record-misconduct-confirm')->close();
        Flux::modal('delay-sanction-recorded')->show();

        $this->dispatch('game-event-recorded');
    }

    public function dismissDelaySanctionRecordedMessage(): void
    {
        $this->delaySanctionRecordedTitle = null;
        $this->delaySanctionRecordedMessage = null;

        Flux::modal('delay-sanction-recorded')->close();
    }

    /**
     * @return array{
     *     leftTeam: TeamAB,
     *     rightTeam: TeamAB,
     *     servingTeam: TeamAB|null,
     *     showRosters: bool,
     *     leftRotation: array<int, int>,
     *     rightRotation: array<int, int>,
     *     leftDelayWarningDisabled: bool,
     *     rightDelayWarningDisabled: bool,
     *     leftDelayPenaltyDisabled: bool,
     *     rightDelayPenaltyDisabled: bool,
     *     leftMinorMisconductDisabled: bool,
     *     rightMinorMisconductDisabled: bool,
     *     misconductSubjects: array{
     *         players: array<int, array{subject_type: string, subject_id: int, marker: string, name: string, unavailable: bool, unavailable_icon: string|null}>,
     *         staff: array<int, array{subject_type: string, subject_id: int, marker: string, name: string, unavailable: bool, unavailable_icon: string|null}>
     *     },
     *     pendingMisconductSanctionLabel: string|null
     * }
     */
    private function courtContext(): array
    {
        $game = $this->activeGame();
        $state = $this->resolvedGameState();
        $leftTeam = $this->gameSideResolver()->teamOnLeftForState($state);
        $rightTeam = $this->gameSideResolver()->teamOnRightForState($state);
        $showRosters = $game !== null && $this->gameSideResolver()->hasRecordedToss($game);

        return [
            'leftTeam' => $leftTeam,
            'rightTeam' => $rightTeam,
            'servingTeam' => $state->servingTeam,
            'showRosters' => $showRosters,
            'leftRotation' => $this->rotationForTeam($leftTeam),
            'rightRotation' => $this->rotationForTeam($rightTeam),
            'leftDelayWarningDisabled' => $this->hasDelaySanctionFor($leftTeam),
            'rightDelayWarningDisabled' => $this->hasDelaySanctionFor($rightTeam),
            'leftDelayPenaltyDisabled' => ! $this->hasDelaySanctionFor($leftTeam),
            'rightDelayPenaltyDisabled' => ! $this->hasDelaySanctionFor($rightTeam),
            'leftMinorMisconductDisabled' => $this->hasMinorMisconductFor($leftTeam),
            'rightMinorMisconductDisabled' => $this->hasMinorMisconductFor($rightTeam),
            'misconductSubjects' => $this->pendingMisconductSubjects(),
            'pendingMisconductSanctionLabel' => $this->pendingMisconductSanctionLabel(),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function rotationForTeam(TeamAB $team): array
    {
        return $team === TeamAB::TeamA
            ? $this->resolvedGameState()->rotationTeamA
            : $this->resolvedGameState()->rotationTeamB;
    }

    private function resolvedGameState(): GameState
    {
        return $this->gameState ?? GameState::initial();
    }

    private function activeGame(): ?Game
    {
        if ($this->gameId === null) {
            return null;
        }

        return Game::query()->whereKey($this->gameId)->first();
    }

    private function gameSideResolver(): GameSideResolver
    {
        return app(GameSideResolver::class);
    }

    /**
     * @return array{
     *     players: array<int, array{subject_type: string, subject_id: int, marker: string, name: string, unavailable: bool, unavailable_icon: string|null}>,
     *     staff: array<int, array{subject_type: string, subject_id: int, marker: string, name: string, unavailable: bool, unavailable_icon: string|null}>
     * }
     */
    private function pendingMisconductSubjects(): array
    {
        if ($this->pendingMisconductTeam === null) {
            return ['players' => [], 'staff' => []];
        }

        $game = $this->activeGame();

        if ($game === null) {
            return ['players' => [], 'staff' => []];
        }

        $team = TeamAB::from($this->pendingMisconductTeam);
        $players = $team === TeamAB::TeamA ? $game->homePlayers() : $game->awayPlayers();
        $staff = $team === TeamAB::TeamA ? $game->homeStaff() : $game->awayStaff();
        $sanction = $this->pendingMisconductSanction === null
            ? null
            : MisconductSanction::from($this->pendingMisconductSanction);

        return [
            'players' => $players
                ->orderByPivot('number')
                ->get()
                ->map(function (Player $player) use ($game, $team, $sanction): array {
                    $recordedSanction = $sanction === null
                        ? null
                        : $this->blockingMisconductSanction($game, $team, MisconductSubjectType::Player, $player->getKey(), $sanction);

                    return [
                        'subject_type' => MisconductSubjectType::Player->value,
                        'subject_id' => $player->getKey(),
                        'marker' => (string) $player->roster->number,
                        'name' => "{$player->first_name} {$player->last_name}",
                        'unavailable' => $recordedSanction !== null,
                        'unavailable_icon' => $recordedSanction === null ? null : $this->misconductSanctionIcon($recordedSanction),
                    ];
                })
                ->all(),
            'staff' => $this->staffMisconductSubjects($staff->orderByPivot('id')->get()->all(), $game, $team, $sanction),
        ];
    }

    /**
     * @param  array<int, Staff>  $staff
     * @return array<int, array{subject_type: string, subject_id: int, marker: string, name: string, unavailable: bool, unavailable_icon: string|null}>
     */
    private function staffMisconductSubjects(array $staff, Game $game, TeamAB $team, ?MisconductSanction $sanction): array
    {
        $assistantCoachCount = 0;

        return array_map(function (Staff $staffMember) use (&$assistantCoachCount, $game, $team, $sanction): array {
            $marker = match ($staffMember->roster->role) {
                StaffRole::Coach => 'C',
                StaffRole::AssistantCoach => 'AC'.(++$assistantCoachCount),
                StaffRole::Doctor => 'D',
                StaffRole::Therapist => 'T',
            };
            $recordedSanction = $sanction === null
                ? null
                : $this->blockingMisconductSanction($game, $team, MisconductSubjectType::Staff, $staffMember->getKey(), $sanction);

            return [
                'subject_type' => MisconductSubjectType::Staff->value,
                'subject_id' => $staffMember->getKey(),
                'marker' => $marker,
                'name' => "{$staffMember->first_name} {$staffMember->last_name}",
                'unavailable' => $recordedSanction !== null,
                'unavailable_icon' => $recordedSanction === null ? null : $this->misconductSanctionIcon($recordedSanction),
            ];
        }, $staff);
    }

    private function blockingMisconductSanction(
        Game $game,
        TeamAB $team,
        MisconductSubjectType $subjectType,
        int $subjectId,
        MisconductSanction $sanction,
    ): ?MisconductSanction {
        /** @var MisconductSanction|null $highestRecordedSanction */
        $highestRecordedSanction = $game->events()
            ->where('type', GameEventType::MisconductRecorded)
            ->get()
            ->map(fn (GameEvent $event): mixed => $event->payload)
            ->filter(fn (mixed $payload): bool => $payload instanceof MisconductRecordedPayload
                && $payload->team === $team
                && $payload->subjectType === $subjectType
                && $payload->subjectId === $subjectId)
            ->map(fn (MisconductRecordedPayload $payload): MisconductSanction => $payload->sanction)
            ->sortByDesc(fn (MisconductSanction $recordedSanction): int => $this->misconductSanctionRank($recordedSanction))
            ->first();

        if ($highestRecordedSanction === null || $this->misconductSanctionRank($sanction) > $this->misconductSanctionRank($highestRecordedSanction)) {
            return null;
        }

        return $highestRecordedSanction;
    }

    private function misconductSubjectLabel(MisconductSubjectType $subjectType, int $subjectId): ?string
    {
        $subjects = $this->pendingMisconductSubjects();
        $group = $subjectType === MisconductSubjectType::Player ? $subjects['players'] : $subjects['staff'];

        foreach ($group as $subject) {
            if ($subject['subject_id'] === $subjectId) {
                return $subject['marker'];
            }
        }

        return null;
    }

    private function pendingMisconductSanctionLabel(): ?string
    {
        if ($this->pendingMisconductSanction === null) {
            return null;
        }

        return $this->misconductSanctionLabel(MisconductSanction::from($this->pendingMisconductSanction));
    }

    private function misconductSanctionLabel(MisconductSanction $sanction): string
    {
        return match ($sanction) {
            MisconductSanction::Warning => 'Minor misconduct',
            MisconductSanction::Penalty => 'Penalty',
            MisconductSanction::Expulsion => 'Expulsion',
            MisconductSanction::Disqualification => 'Disqualification',
        };
    }

    private function misconductSanctionIcon(MisconductSanction $sanction): string
    {
        return match ($sanction) {
            MisconductSanction::Warning => asset('icons/yellow-card.svg'),
            MisconductSanction::Penalty => asset('icons/red-card.svg'),
            MisconductSanction::Expulsion => asset('icons/yellow-red-card.svg'),
            MisconductSanction::Disqualification => asset('icons/yellow-red-side-by-side-card.svg'),
        };
    }

    private function misconductSanctionRank(MisconductSanction $sanction): int
    {
        return match ($sanction) {
            MisconductSanction::Warning => 1,
            MisconductSanction::Penalty => 2,
            MisconductSanction::Expulsion => 3,
            MisconductSanction::Disqualification => 4,
        };
    }

    private function resetPendingMisconduct(): void
    {
        $this->pendingMisconductTeam = null;
        $this->pendingMisconductSanction = null;
        $this->pendingMisconductSubjectType = null;
        $this->pendingMisconductSubjectId = null;
        $this->pendingMisconductSubjectLabel = null;
    }

    private function hasDelaySanctionFor(TeamAB $team): bool
    {
        $state = $this->resolvedGameState();

        if ($team === TeamAB::TeamA) {
            return $state->delayWarningsTeamA + $state->delayPenaltiesTeamA > 0;
        }

        return $state->delayWarningsTeamB + $state->delayPenaltiesTeamB > 0;
    }

    private function hasMinorMisconductFor(TeamAB $team): bool
    {
        $state = $this->resolvedGameState();

        if ($team === TeamAB::TeamA) {
            return $state->misconductWarningsTeamA > 0;
        }

        return $state->misconductWarningsTeamB > 0;
    }
}
