<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CompetitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use LogicException;

/**
 * @property string $name
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name'])]
class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
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

    public static function current(): ?self
    {
        try {
            /** @var self */
            return static::query()->sole();
        } catch (ModelNotFoundException) {
            return null;
        } catch (MultipleRecordsFoundException $exception) {
            throw new LogicException('The single-competition application found multiple competition records.', previous: $exception);
        }
    }

    public static function ensureSingleton(): self
    {
        $competition = static::current();

        if ($competition !== null) {
            return $competition;
        }

        return static::query()->create([
            'name' => config('competition.name'),
        ]);
    }

    public static function setupComplete(): bool
    {
        $competition = static::current();

        return $competition !== null && trim($competition->name) !== '';
    }
}
