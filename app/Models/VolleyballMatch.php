<?php

namespace App\Models;

use App\Enums\StaffRole;
use Carbon\CarbonImmutable;
use Database\Factories\VolleyballMatchFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $championship_id
 * @property int $home_team_id
 * @property int $away_team_id
 * @property int $match_number
 * @property string $country_code
 * @property string $city
 * @property string $hall
 * @property CarbonImmutable $match_date_time
 * @property string|null $division
 * @property string|null $pool
 * @property string|null $category
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Championship $championship
 * @property-read Team $homeTeam
 * @property-read Team $awayTeam
 * @property-read Collection<int, MatchOfficial> $officials
 * @property-read Collection<int, MatchPlayer> $matchPlayers
 * @property-read Collection<int, MatchStaff> $matchStaff
 */
class VolleyballMatch extends Model
{
    /** @use HasFactory<VolleyballMatchFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'match_number' => 'integer',
            'match_date_time' => 'immutable_datetime',
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
     * @return HasMany<MatchOfficial, $this>
     */
    public function officials(): HasMany
    {
        return $this->hasMany(MatchOfficial::class);
    }

    /**
     * @return HasMany<MatchPlayer, $this>
     */
    public function matchPlayers(): HasMany
    {
        return $this->hasMany(MatchPlayer::class);
    }

    /**
     * @return HasMany<MatchStaff, $this>
     */
    public function matchStaff(): HasMany
    {
        return $this->hasMany(MatchStaff::class);
    }

    /**
     * @return HasMany<MatchPlayer, $this>
     */
    public function homePlayers(): HasMany
    {
        return $this->hasMany(MatchPlayer::class)->where('team_id', $this->home_team_id);
    }

    /**
     * @return HasMany<MatchPlayer, $this>
     */
    public function awayPlayers(): HasMany
    {
        return $this->hasMany(MatchPlayer::class)->where('team_id', $this->away_team_id);
    }

    /**
     * @return HasMany<MatchStaff, $this>
     */
    public function homeStaff(): HasMany
    {
        return $this->hasMany(MatchStaff::class)->where('team_id', $this->home_team_id);
    }

    /**
     * @return HasMany<MatchStaff, $this>
     */
    public function awayStaff(): HasMany
    {
        return $this->hasMany(MatchStaff::class)->where('team_id', $this->away_team_id);
    }

    /**
     * Add a player to the match roster.
     */
    public function addPlayer(Player $player, ?int $number = null, bool $isCaptain = false, bool $isLibero = false): MatchPlayer
    {
        return $this->matchPlayers()->create([
            'player_id' => $player->id,
            'team_id' => $player->team_id,
            'number' => $number ?? $player->number,
            'is_captain' => $isCaptain,
            'is_libero' => $isLibero,
        ]);
    }

    /**
     * Add a staff member to the match roster.
     */
    public function addStaff(Staff $staff, StaffRole|string $role): MatchStaff
    {
        return $this->matchStaff()->create([
            'staff_id' => $staff->id,
            'team_id' => $staff->team_id,
            'role' => $role,
        ]);
    }
}
