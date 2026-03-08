<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StaffRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $game_id
 * @property int $staff_id
 * @property int $team_id
 * @property StaffRole $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RosterStaff extends Pivot
{
    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'role' => StaffRole::class,
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
