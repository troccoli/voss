<?php

namespace Database\Factories;

use App\Models\Championship;
use App\Models\Team;
use App\Models\VolleyballMatch;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VolleyballMatch>
 */
class VolleyballMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'championship_id' => Championship::factory(),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'match_number' => (string) $this->faker->numberBetween(1, 100),
            'country_code' => $this->faker->countryCode(),
            'city' => $this->faker->city(),
            'hall' => $this->faker->company().' Arena',
            'match_date_time' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'division' => $this->faker->randomElement(['Division 1', 'Division 2', 'Pro League']),
            'pool' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'category' => $this->faker->randomElement(['Men', 'Women']),
        ];
    }

    /**
     * Set the teams for the match.
     */
    public function betweenTeams(Team $homeTeam, Team $awayTeam): static
    {
        return $this->state(fn (array $attributes) => [
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);
    }

    /**
     * Set the match number.
     */
    public function withMatchNumber(string $number): static
    {
        return $this->state(fn (array $attributes) => [
            'match_number' => $number,
        ]);
    }

    /**
     * Set the country code.
     */
    public function withCountryCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => $code,
        ]);
    }

    /**
     * Set the location of the match.
     */
    public function at(string $city, string $hall): static
    {
        return $this->state(fn (array $attributes) => [
            'city' => $city,
            'hall' => $hall,
        ]);
    }

    /**
     * Set the scheduled time of the match.
     */
    public function scheduledAt(DateTimeInterface $dateTime): static
    {
        return $this->state(fn (array $attributes) => [
            'match_date_time' => $dateTime,
        ]);
    }
}
