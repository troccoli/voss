<?php

namespace App\Models;

use App\Enums\GameEventType;
use App\Events\Payloads\GameEventPayload;
use App\Events\Payloads\LineupSubmittedPayload;
use App\Events\Payloads\TossCompletedPayload;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $game_id
 * @property GameEventType $type
 * @property GameEventPayload $payload
 * @property CarbonImmutable $created_at
 * @property-read Game $game
 */
class GameEvent extends Model
{
    const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \LogicException('Game events are immutable and cannot be modified.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GameEventType::class,
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return Attribute<GameEventPayload, GameEventPayload>
     */
    public function payload(): Attribute
    {
        return Attribute::make(
            get: function (string $value): GameEventPayload {
                $data = json_decode($value, true);

                return match ($this->type) {
                    GameEventType::TossCompleted => TossCompletedPayload::fromArray($data),
                    GameEventType::LineupSubmitted => LineupSubmittedPayload::fromArray($data),
                };
            },
            set: fn (GameEventPayload $value): string => json_encode($value->toArray(), JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
