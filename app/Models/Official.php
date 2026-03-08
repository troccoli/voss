<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\OfficialFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $first_name
 * @property string $last_name
 * @property string $country_code
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read EloquentCollection<int, Game> $games
 * @property-read GameOfficial $assignment
 */
class Official extends Model
{
    /** @use HasFactory<OfficialFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsToMany<Game, $this, GameOfficial, 'assignment'>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class)
            ->using(GameOfficial::class)
            ->as('assignment')
            ->withPivot('role')
            ->withTimestamps();
    }
}
