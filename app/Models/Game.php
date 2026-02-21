<?php

namespace App\Models;

use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use Carbon\CarbonImmutable;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $championship_id
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
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Championship $championship
 * @property-read Team $homeTeam
 * @property-read Team $awayTeam
 * @property-read Collection<int, Official> $officials
 * @property-read Collection<int, Player> $players
 * @property-read Collection<int, Player> $homePlayers
 * @property-read Collection<int, Player> $awayPlayers
 * @property-read Collection<int, Staff> $staff
 * @property-read Collection<int, Staff> $homeStaff
 * @property-read Collection<int, Staff> $awayStaff
 */
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'date_time' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Championship, $this>
     */
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
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
     * @return BelongsToMany<Official, $this, GameOfficial, 'assignment'>
     */
    public function officials(): BelongsToMany
    {
        return $this->belongsToMany(Official::class)
            ->using(GameOfficial::class)
            ->as('assignment')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Player, $this, RosterPlayer, 'roster'>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class)
            ->using(RosterPlayer::class)
            ->as('roster')
            ->withPivot('number', 'is_captain', 'is_libero', 'team_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Player, $this, RosterPlayer, 'roster'>
     */
    public function homePlayers(): BelongsToMany
    {
        return $this->players()->wherePivot('team_id', $this->home_team_id);
    }

    /**
     * @return BelongsToMany<Player, $this, RosterPlayer, 'roster'>
     */
    public function awayPlayers(): BelongsToMany
    {
        return $this->players()->wherePivot('team_id', $this->away_team_id);
    }

    /**
     * @return BelongsToMany<Staff, $this, RosterStaff, 'roster'>
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class)
            ->using(RosterStaff::class)
            ->as('roster')
            ->withPivot('role', 'team_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Staff, $this, RosterStaff, 'roster'>
     */
    public function homeStaff(): BelongsToMany
    {
        return $this->staff()->wherePivot('team_id', $this->home_team_id);
    }

    /**
     * @return BelongsToMany<Staff, $this, RosterStaff, 'roster'>
     */
    public function awayStaff(): BelongsToMany
    {
        return $this->staff()->wherePivot('team_id', $this->away_team_id);
    }

    /**
     * Add a player to the match roster.
     */
    public function addPlayer(Player $player, int $number, bool $isCaptain = false, bool $isLibero = false): void
    {
        $this->players()->attach($player, [
            'team_id' => $player->team_id,
            'number' => $number,
            'is_captain' => $isCaptain,
            'is_libero' => $isLibero,
        ]);
    }

    /**
     * Add a staff member to the match roster.
     */
    public function addStaff(Staff $staff, StaffRole|string $role): void
    {
        $this->staff()->attach($staff, [
            'team_id' => $staff->team_id,
            'role' => $role,
        ]);
    }

    /**
     * Add an official to the match.
     */
    public function addOfficial(Official $official, OfficialRole $role): void
    {
        $this->officials()->attach($official, ['role' => $role]);
    }
}
