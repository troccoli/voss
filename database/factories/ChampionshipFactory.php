<?php

namespace Database\Factories;

use App\Models\Championship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Championship>
 */
class ChampionshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var string $name */
        $name = $this->faker->words(3, true);

        return [
            'name' => $name.' Championship',
        ];
    }

    /**
     * Set the name of the championship.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}
