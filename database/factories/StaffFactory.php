<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StaffRole;
use App\Models\Staff;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
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
            'role' => $this->faker->randomElement(StaffRole::cases()),
        ];
    }

    /**
     * Indicate that the staff should have a name consistent with the given locale.
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

    public function withRole(StaffRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }

    public function asCoach(): static
    {
        return $this->withRole(StaffRole::Coach);
    }

    public function asAssistantCoach(): static
    {
        return $this->withRole(StaffRole::AssistantCoach);
    }

    public function asDoctor(): static
    {
        return $this->withRole(StaffRole::Doctor);
    }

    public function asTherapist(): static
    {
        return $this->withRole(StaffRole::Therapist);
    }
}
