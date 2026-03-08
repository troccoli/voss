<?php

declare(strict_types=1);

namespace Database\Factories;

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
}
