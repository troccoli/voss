<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TeamSide;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    use WithLocales;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = $this->randomCountryCode();

        return [
            'name' => $this->countries[$code],
            'country_code' => $code,
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    public function withCountryCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => $code,
        ]);
    }

    public function identifiedAs(string $name, string $countryCode): static
    {
        return $this->named($name)->withCountryCode($countryCode);
    }

    public function forMatch(Game $game, TeamSide $side): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $game->getKey(),
            'side' => $side,
        ]);
    }
}
