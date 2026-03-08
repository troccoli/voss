<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $game_id
 * @property int $player_id
 * @property int $team_id
 * @property int $number
 * @property bool $is_captain
 * @property bool $is_libero
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RosterPlayer extends Pivot
{
    public $incrementing = true;

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
}
