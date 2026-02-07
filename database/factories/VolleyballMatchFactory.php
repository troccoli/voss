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
            'match_number' => $this->faker->numberBetween(1, 99),
            'country_code' => $this->faker->countryISOAlpha3(),
            'city' => str($this->faker->city())->limit(limit: 14, end: ''),
            'hall' => str($this->faker->company())->limit(limit: 10, end: ''),
            'match_date_time' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'category' => $this->faker->randomElement(['Senior', 'Junior', 'Youth']),
            'pool' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'division' => $this->faker->randomElement(['Men', 'Women']),
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
    public function withMatchNumber(int $number): static
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
