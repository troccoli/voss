<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OfficialRole;
use Carbon\CarbonImmutable;
use Database\Factories\OfficialFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $game_id
 * @property string $first_name
 * @property string $last_name
 * @property string $country_code
 * @property OfficialRole|null $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Game|null $game
 * @property-read Official $assignment
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
            'role' => OfficialRole::class,
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
     * @return Attribute<$this, never>
     */
    protected function assignment(): Attribute
    {
        return Attribute::get(fn (): self => $this);
    }
}
