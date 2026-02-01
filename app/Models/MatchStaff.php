<?php

namespace App\Models;

use App\Enums\StaffRole;
use Carbon\CarbonImmutable;
use Database\Factories\MatchStaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $volleyball_match_id
 * @property int $staff_id
 * @property int $team_id
 * @property StaffRole $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read VolleyballMatch $match
 * @property-read Staff $staff
 * @property-read Team $team
 */
class MatchStaff extends Model
{
    /** @use HasFactory<MatchStaffFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'role' => StaffRole::class,
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
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
