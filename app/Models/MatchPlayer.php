<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MatchPlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $volleyball_match_id
 * @property int $player_id
 * @property int $team_id
 * @property int $number
 * @property bool $is_captain
 * @property bool $is_libero
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read VolleyballMatch $match
 * @property-read Player $player
 * @property-read Team $team
 */
class MatchPlayer extends Model
{
    /** @use HasFactory<MatchPlayerFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_captain' => 'boolean',
            'is_libero' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<VolleyballMatch, $this>
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(VolleyballMatch::class, 'volleyball_match_id');
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
