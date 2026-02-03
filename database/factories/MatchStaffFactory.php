<?php

namespace Database\Factories;

use App\Enums\StaffRole;
use App\Models\MatchStaff;
use App\Models\Staff;
use App\Models\Team;
use App\Models\VolleyballMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchStaff>
 */
class MatchStaffFactory extends Factory
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
            'staff_id' => Staff::factory(),
            'team_id' => Team::factory(),
            'role' => $this->faker->randomElement(StaffRole::cases()),
        ];
    }
}
