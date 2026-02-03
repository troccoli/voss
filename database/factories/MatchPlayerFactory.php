<?php

namespace Database\Factories;

use App\Models\MatchPlayer;
use App\Models\Player;
use App\Models\Team;
use App\Models\VolleyballMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchPlayer>
 */
class MatchPlayerFactory extends Factory
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
            'player_id' => Player::factory(),
            'team_id' => Team::factory(),
            'number' => $this->faker->numberBetween(1, 99),
            'is_captain' => false,
            'is_libero' => false,
        ];
    }
}
