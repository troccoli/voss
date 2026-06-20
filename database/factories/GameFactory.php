<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MatchPhase;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Models\Team;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
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
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'number' => $this->faker->numberBetween(1, 99),
            'country_code' => $code,
            'city' => str(fake($this->getLocaleForCountry($code))->city())->limit(limit: 14, end: ''),
            'hall' => str(fake($this->getLocaleForCountry($code))->company())->limit(limit: 10, end: ''),
            'date_time' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'category' => $this->faker->randomElement(['Senior', 'Junior', 'Youth']),
            'pool' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'division' => $this->faker->randomElement(['Men', 'Women']),
            'status' => MatchPhase::Setup,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Game $game): void {
            $game->homeTeam->forceFill([
                'game_id' => $game->getKey(),
                'side' => TeamSide::Home,
            ])->save();

            $game->awayTeam->forceFill([
                'game_id' => $game->getKey(),
                'side' => TeamSide::Away,
            ])->save();
        });
    }

    /**
     * Set the teams for the match.
     */
    public function betweenTeams(Team $homeTeam, Team $awayTeam): static
    {
        return $this->state(fn (array $attributes) => [
            'home_team_id' => $homeTeam->getKey(),
            'away_team_id' => $awayTeam->getKey(),
        ]);
    }

    /**
     * Set the match number.
     */
    public function withMatchNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'number' => $number,
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
            'date_time' => $dateTime,
        ]);
    }

    /**
     * Leave match details blank so setup must complete them.
     */
    public function withoutMatchDetails(): static
    {
        return $this->state(fn (array $attributes) => [
            'number' => 1,
            'country_code' => '',
            'city' => '',
            'hall' => '',
            'date_time' => now(),
            'category' => '',
            'pool' => '',
            'division' => '',
        ]);
    }

    public function configuredSetup(): static
    {
        $code = 'ITA';

        return $this->withMatchNumber(1)
            ->withCountryCode($code)
            ->at('Bologna', 'PalaDozza')
            ->scheduledAt(now()->addDay()->setTime(20, 30))
            ->state(fn (array $attributes) => [
                'category' => 'Senior',
                'pool' => 'A',
                'division' => 'Men',
            ]);
    }

    public function withStatus(MatchPhase $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
