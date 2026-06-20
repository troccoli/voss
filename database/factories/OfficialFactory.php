<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OfficialRole;
use App\Models\Game;
use App\Models\Official;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Official>
 */
class OfficialFactory extends Factory
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
            'first_name' => fake($this->getLocaleForCountry($code))->firstName(),
            'last_name' => fake($this->getLocaleForCountry($code))->lastName(),
            'country_code' => $code,
        ];
    }

    public function named(string $firstName, string $lastName): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    public function withCountryCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => $code,
        ]);
    }

    public function forMatch(Game $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $game->getKey(),
        ]);
    }

    public function withRole(OfficialRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }
}
