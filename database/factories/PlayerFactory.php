<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    use WithLocales;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
        ];
    }

    /**
     * Indicate that the player should have a name consistent with the given locale.
     */
    public function withLocale(string $locale): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => fake($locale)->firstName(),
            'last_name' => fake($locale)->lastName(),
        ]);
    }

    public function forCountry(string $code): static
    {
        return $this->withLocale($this->getLocaleForCountry($code));
    }

    public function named(string $firstName, string $lastName): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    public function forMatch(Game $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $game->getKey(),
        ]);
    }

    public function rostered(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_rostered' => true,
        ]);
    }

    public function withNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'number' => $number,
        ]);
    }

    public function asCaptain(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_captain' => true,
        ]);
    }

    public function asLibero(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_libero' => true,
        ]);
    }
}
