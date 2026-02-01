<?php

namespace Database\Factories;

use App\Enums\OfficialRole;
use App\Models\MatchOfficial;
use App\Models\Official;
use App\Models\VolleyballMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchOfficial>
 */
class MatchOfficialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'volleyball_match_id' => VolleyballMatch::factory(),
            'official_id' => Official::factory(),
            'role' => $this->faker->randomElement(OfficialRole::cases()),
        ];
    }

    /**
     * Set the role for the official.
     */
    public function withRole(OfficialRole|string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }
}
