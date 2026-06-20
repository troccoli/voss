<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\MatchPhase;
use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Concerns\RecordsCourtSideSwap;
use App\Models\Concerns\RecordsEndOfGame;
use App\Models\Concerns\RecordsEndOfRally;
use App\Models\Concerns\RecordsEndOfSet;
use App\Models\Concerns\RecordsImproperRequest;
use App\Models\Concerns\RecordsLineup;
use App\Models\Concerns\RecordsMisconduct;
use App\Models\Concerns\RecordsStartOfSet;
use App\Models\Concerns\RecordsSubstitution;
use App\Models\Concerns\RecordsTimeOut;
use App\Models\Concerns\RecordsToss;
use App\Services\CurrentMatchResolver;
use Carbon\CarbonImmutable;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * @property int $home_team_id
 * @property int $away_team_id
 * @property int $number
 * @property string $country_code
 * @property string $city
 * @property string $hall
 * @property CarbonImmutable $date_time
 * @property string $division
 * @property string $pool
 * @property string $category
 * @property bool $rosters_submitted
 * @property MatchPhase $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Team $homeTeam
 * @property-read Team $awayTeam
 * @property-read EloquentCollection<int, Official> $officials
 * @property-read EloquentCollection<int, Player> $players
 * @property-read EloquentCollection<int, Player> $homePlayers
 * @property-read EloquentCollection<int, Player> $awayPlayers
 * @property-read EloquentCollection<int, Staff> $staff
 * @property-read EloquentCollection<int, Staff> $homeStaff
 * @property-read EloquentCollection<int, Staff> $awayStaff
 * @property-read EloquentCollection<int, GameEvent> $events
 * @property-read EloquentCollection<int, GameStateSnapshot> $stateSnapshots
 */
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    use RecordsCourtSideSwap;
    use RecordsEndOfGame;
    use RecordsEndOfRally;
    use RecordsEndOfSet;
    use RecordsImproperRequest;
    use RecordsLineup;
    use RecordsMisconduct;
    use RecordsStartOfSet;
    use RecordsSubstitution;
    use RecordsTimeOut;
    use RecordsToss;

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'rosters_submitted' => 'boolean',
            'status' => MatchPhase::class,
            'date_time' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * Resolve the singleton match for the current database.
     */
    public static function current(): self
    {
        return app(CurrentMatchResolver::class)->currentOrFail();
    }

    /**
     * Create or reuse the singleton match record and its scoped teams.
     *
     * @param  array<string, mixed>  $gameAttributes
     * @param  array<string, mixed>  $homeTeamAttributes
     * @param  array<string, mixed>  $awayTeamAttributes
     */
    public static function ensureSingleton(
        array $gameAttributes = [],
        array $homeTeamAttributes = [],
        array $awayTeamAttributes = [],
    ): self {
        /** @var self|null $existingGame */
        $existingGame = static::query()->first();

        if ($existingGame !== null) {
            return self::synchronizeSingleton($existingGame, $gameAttributes, $homeTeamAttributes, $awayTeamAttributes);
        }

        try {
            /** @var self */
            return DB::transaction(function () use ($gameAttributes, $homeTeamAttributes, $awayTeamAttributes): self {
                $homeTeam = new Team;
                $homeTeam->forceFill(array_merge([
                    'name' => '',
                    'country_code' => '',
                ], $homeTeamAttributes))->save();

                $awayTeam = new Team;
                $awayTeam->forceFill(array_merge([
                    'name' => '',
                    'country_code' => '',
                ], $awayTeamAttributes))->save();

                $game = new self;
                $game->forceFill(array_merge([
                    'home_team_id' => $homeTeam->getKey(),
                    'away_team_id' => $awayTeam->getKey(),
                    'number' => 1,
                    'country_code' => '',
                    'city' => '',
                    'hall' => '',
                    'date_time' => now(),
                    'division' => '',
                    'pool' => '',
                    'category' => '',
                    'rosters_submitted' => false,
                    'status' => MatchPhase::Setup,
                ], $gameAttributes))->save();

                return self::synchronizeSingleton($game, [], [], []);
            });
        } catch (QueryException $exception) {
            /** @var self|null $createdByConcurrentRequest */
            $createdByConcurrentRequest = static::query()->first();

            if ($createdByConcurrentRequest !== null) {
                return self::synchronizeSingleton(
                    $createdByConcurrentRequest,
                    $gameAttributes,
                    $homeTeamAttributes,
                    $awayTeamAttributes,
                );
            }

            throw $exception;
        }
    }

    /**
     * Runtime competition metadata now comes from application config.
     */
    public function competitionName(): string
    {
        return Config::string('competition.name');
    }

    public function resetForSetup(): void
    {
        DB::transaction(function (): void {
            $this->stateSnapshots()->delete();
            $this->events()->delete();

            Player::query()
                ->where('game_id', $this->getKey())
                ->delete();

            Staff::query()
                ->where('game_id', $this->getKey())
                ->delete();

            $this->officials()->delete();

            $this->forceFill([
                'rosters_submitted' => false,
                'status' => MatchPhase::Setup,
            ])->save();
        });
    }

    public function hasCompleteMatchDetails(): bool
    {
        return $this->number > 0
            && $this->country_code !== ''
            && $this->city !== ''
            && $this->hall !== ''
            && $this->division !== ''
            && $this->pool !== ''
            && $this->category !== ''
            && $this->homeTeam->name !== ''
            && $this->homeTeam->country_code !== ''
            && $this->awayTeam->name !== ''
            && $this->awayTeam->country_code !== '';
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * @return HasMany<Official, $this>
     */
    public function officials(): HasMany
    {
        return $this->hasMany(Official::class)->orderBy('id');
    }

    /**
     * @return HasMany<Player, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class)->where('is_rostered', true);
    }

    /**
     * @return HasMany<Player, $this>
     */
    public function homePlayers(): HasMany
    {
        return $this->players()->where('team_id', $this->homeTeam->getKey());
    }

    /**
     * @return HasMany<Player, $this>
     */
    public function awayPlayers(): HasMany
    {
        return $this->players()->where('team_id', $this->awayTeam->getKey());
    }

    /**
     * @return HasMany<Staff, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class)->where('is_rostered', true);
    }

    /**
     * @return HasMany<Staff, $this>
     */
    public function homeStaff(): HasMany
    {
        return $this->staff()->where('team_id', $this->homeTeam->getKey());
    }

    /**
     * @return HasMany<Staff, $this>
     */
    public function awayStaff(): HasMany
    {
        return $this->staff()->where('team_id', $this->awayTeam->getKey());
    }

    /**
     * Add a player to the match roster.
     */
    public function addPlayer(Player $player, int $number, bool $isCaptain = false, bool $isLibero = false): void
    {
        $player->forceFill([
            'game_id' => $this->getKey(),
            'number' => $number,
            'is_captain' => $isCaptain,
            'is_libero' => $isLibero,
            'is_rostered' => true,
        ])->save();

        $this->synchronizeStatus();
    }

    /**
     * Add a staff member to the match roster.
     */
    public function addStaff(Staff $staff, StaffRole|string|null $role = null): void
    {
        $resolvedRole = $role instanceof StaffRole
            ? $role
            : ($role !== null ? StaffRole::from($role) : $staff->role);

        $staff->forceFill([
            'game_id' => $this->getKey(),
            'role' => $resolvedRole,
            'is_rostered' => true,
        ])->save();

        $this->synchronizeStatus();
    }

    /**
     * @param  array<int, array{player: Player, number: int, is_captain: bool, is_libero: bool}>  $players
     * @param  array<int, Staff>  $staff
     */
    public function replaceRosterForTeam(TeamAB $team, array $players, array $staff): void
    {
        $this->assertSetupEditable();

        $teamModel = $team === TeamAB::TeamA ? $this->homeTeam : $this->awayTeam;
        $teamId = $teamModel->getKey();

        DB::transaction(function () use ($players, $staff, $teamId): void {
            Player::query()
                ->where('game_id', $this->getKey())
                ->where('team_id', $teamId)
                ->update([
                    'number' => null,
                    'is_captain' => false,
                    'is_libero' => false,
                    'is_rostered' => false,
                ]);

            Staff::query()
                ->where('game_id', $this->getKey())
                ->where('team_id', $teamId)
                ->update([
                    'is_rostered' => false,
                ]);

            foreach ($players as $playerRoster) {
                $this->addPlayer(
                    player: $playerRoster['player'],
                    number: $playerRoster['number'],
                    isCaptain: $playerRoster['is_captain'],
                    isLibero: $playerRoster['is_libero'],
                );
            }

            foreach ($staff as $staffMember) {
                $this->addStaff($staffMember);
            }
        });

        $this->synchronizeStatus();
    }

    public function hasSubmittedInitialRosters(): bool
    {
        return $this->rosters_submitted
            && $this->homePlayers()->exists()
            && $this->awayPlayers()->exists();
    }

    public function markRostersSubmitted(): void
    {
        $this->forceFill([
            'rosters_submitted' => true,
        ])->save();

        $this->synchronizeStatus();
    }

    public function hasRequiredOfficials(): bool
    {
        /** @var array<int, string> $assignedRoles */
        $assignedRoles = $this->officials()
            ->get()
            ->map(fn (Official $official): ?string => $official->role?->value)
            ->unique()
            ->filter()
            ->values()
            ->all();

        if (count($assignedRoles) !== count(OfficialRole::cases())) {
            return false;
        }

        return array_all(OfficialRole::cases(), fn (OfficialRole $role): bool => in_array($role->value, $assignedRoles, true));
    }

    /**
     * @param  array<int, array{
     *     role: OfficialRole,
     *     first_name: string,
     *     last_name: string,
     *     country_code: string
     * }>  $assignments
     */
    public function replaceOfficials(array $assignments): void
    {
        $this->assertSetupEditable();

        DB::transaction(function () use ($assignments): void {
            /** @var EloquentCollection<int, Official> $existingOfficials */
            $existingOfficials = $this->officials()->get();
            $existingByRole = $existingOfficials->keyBy(
                fn (Official $official): string => $official->role === null ? '' : $official->role->value,
            );

            foreach ($assignments as $assignment) {
                /** @var Official|null $official */
                $official = $existingByRole->get($assignment['role']->value);
                $official ??= new Official;

                $official->forceFill([
                    'game_id' => $this->getKey(),
                    'role' => $assignment['role'],
                    'first_name' => $assignment['first_name'],
                    'last_name' => $assignment['last_name'],
                    'country_code' => $assignment['country_code'],
                ])->save();
            }

            $rolesToKeep = collect($assignments)
                ->map(fn (array $assignment): string => $assignment['role']->value)
                ->all();

            $this->officials()
                ->whereNotIn('role', $rolesToKeep)
                ->delete();
        });

        $this->synchronizeStatus();
    }

    public function setupLocked(): bool
    {
        return ! $this->status->allowsSetupEdits();
    }

    /**
     * Add an official to the match.
     */
    public function addOfficial(Official $official, OfficialRole $role): void
    {
        $official->forceFill([
            'game_id' => $this->getKey(),
            'role' => $role,
        ])->save();

        $this->synchronizeStatus();
    }

    /**
     * @return HasMany<GameEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(GameEvent::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return HasMany<GameStateSnapshot, $this>
     */
    public function stateSnapshots(): HasMany
    {
        return $this->hasMany(GameStateSnapshot::class)
            ->orderBy('created_at')
            ->orderBy('game_event_id');
    }

    public function snapshotAt(?CarbonImmutable $at = null): ?GameStateSnapshot
    {
        $query = $this->stateSnapshots();

        if ($at !== null) {
            $query->where('created_at', '<=', $at);
        }

        /** @var GameStateSnapshot|null */
        return $query
            ->reorder()
            ->orderByDesc('created_at')
            ->orderByDesc('game_event_id')
            ->first();
    }

    public function stateAt(?CarbonImmutable $at = null): GameState
    {
        $snapshot = $this->snapshotAt($at);

        return $snapshot === null
            ? GameState::initial()
            : GameState::fromSnapshot($snapshot);
    }

    public function isSetupEditable(): bool
    {
        return $this->status->allowsSetupEdits();
    }

    public function canRecordGameplay(): bool
    {
        return $this->status->allowsGameplayRecording();
    }

    public function canGeneratePdf(): bool
    {
        return $this->status->allowsPdfGeneration();
    }

    public function assertSetupEditable(): void
    {
        if ($this->isSetupEditable()) {
            return;
        }

        throw new InvalidGameEventTransition('Match setup cannot be edited after gameplay has begun.');
    }

    public function assertCanGeneratePdf(): void
    {
        if ($this->canGeneratePdf()) {
            return;
        }

        throw new InvalidGameEventTransition('The PDF can only be generated after the match has been completed.');
    }

    public function markPdfGenerated(): void
    {
        $this->assertCanGeneratePdf();

        if ($this->status === MatchPhase::PdfGenerated) {
            return;
        }

        $this->forceFill([
            'status' => MatchPhase::PdfGenerated,
        ])->save();
    }

    public function synchronizeStatus(): void
    {
        $resolvedStatus = $this->resolvedStatus();

        if ($this->status === $resolvedStatus) {
            return;
        }

        $this->forceFill([
            'status' => $resolvedStatus,
        ])->save();
    }

    private function resolvedStatus(): MatchPhase
    {
        $baseStatus = $this->events()->where('type', GameEventType::GameEnded->value)->exists()
            ? MatchPhase::Completed
            : ($this->events()->exists()
                ? MatchPhase::InProgress
                : ($this->hasCompleteMatchDetails()
                    && $this->hasSubmittedInitialRosters()
                    && $this->hasRequiredOfficials()
                    ? MatchPhase::Ready
                    : MatchPhase::Setup));

        if ($this->status === MatchPhase::PdfGenerated && $baseStatus === MatchPhase::Completed) {
            return MatchPhase::PdfGenerated;
        }

        return $baseStatus;
    }

    /**
     * @param  array<string, mixed>  $gameAttributes
     * @param  array<string, mixed>  $homeTeamAttributes
     * @param  array<string, mixed>  $awayTeamAttributes
     */
    private static function synchronizeSingleton(
        self $game,
        array $gameAttributes,
        array $homeTeamAttributes,
        array $awayTeamAttributes,
    ): self {
        /** @var self */
        return DB::transaction(function () use ($game, $gameAttributes, $homeTeamAttributes, $awayTeamAttributes): self {
            $game->loadMissing(['homeTeam', 'awayTeam', 'officials']);

            $game->homeTeam->forceFill(array_merge($homeTeamAttributes, [
                'game_id' => $game->getKey(),
                'side' => TeamSide::Home,
            ]))->save();

            $game->awayTeam->forceFill(array_merge($awayTeamAttributes, [
                'game_id' => $game->getKey(),
                'side' => TeamSide::Away,
            ]))->save();

            if ($gameAttributes !== []) {
                $game->forceFill($gameAttributes)->save();
            }

            /** @var self */
            return $game->fresh(['homeTeam', 'awayTeam', 'officials']);
        });
    }
}
