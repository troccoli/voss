<?php

namespace App\Models;

use App\Enums\OfficialRole;
use Carbon\CarbonImmutable;
use Database\Factories\MatchOfficialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $volleyball_match_id
 * @property int $official_id
 * @property OfficialRole $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read VolleyballMatch $match
 * @property-read Official $official
 */
class MatchOfficial extends Model
{
    /** @use HasFactory<MatchOfficialFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => OfficialRole::class,
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
     * @return BelongsTo<Official, $this>
     */
    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class);
    }
}
