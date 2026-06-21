<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
        ]);
    }
}
