<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StaffRole;
use Carbon\CarbonImmutable;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $game_id
 * @property int $team_id
 * @property string $first_name
 * @property string $last_name
 * @property StaffRole $role
 * @property bool $is_rostered
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Game|null $game
 * @property-read Team $team
 * @property-read Staff $roster
 */
class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'role' => StaffRole::class,
            'is_rostered' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return Attribute<$this, never>
     */
    protected function roster(): Attribute
    {
        return Attribute::get(fn (): self => $this);
    }
}
