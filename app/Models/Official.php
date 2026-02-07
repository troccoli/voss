<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\OfficialFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $country_code
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, MatchOfficial> $matches
 */
class Official extends Model
{
    /** @use HasFactory<OfficialFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<MatchOfficial, $this>
     */
    public function matches(): HasMany
    {
        return $this->hasMany(MatchOfficial::class);
    }
}
