<?php

namespace Database\Factories;

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
            'number' => $this->faker->numberBetween(1, 99),
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
}
