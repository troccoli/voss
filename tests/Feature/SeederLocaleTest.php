<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederLocaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test to verify that names are consistent with the team's country locale.
     */
    public function test_player_and_staff_names_match_team_locale(): void
    {
        // Test with Italy (it_IT)
        $italyTeam = Team::factory()->create([
            'country_code' => 'ITA',
            'name' => 'Italy',
        ]);

        // We can't easily test if a name is "Italian" without a complex library,
        // but we can test that it doesn't contain characters from other locales that use different scripts.

        // Test with Japan (ja_JP) - this is easier because of the script
        $japanTeam = Team::factory()->create([
            'country_code' => 'JPN',
            'name' => 'Japan',
        ]);

        // Manually trigger the logic that would be in the seeder, or better,
        // test the factory state directly since the seeder just uses it.
        $playerJa = Player::factory()->for($japanTeam)->withLocale('ja_JP')->create();
        $staffJa = Staff::factory()->for($japanTeam)->withLocale('ja_JP')->create();

        // Japanese names in Faker ja_JP use multibyte characters
        $this->assertTrue(mb_strlen($playerJa->first_name) < strlen($playerJa->first_name), 'Japanese player name should contain multibyte characters');
        $this->assertTrue(mb_strlen($staffJa->first_name) < strlen($staffJa->first_name), 'Japanese staff name should contain multibyte characters');

        // Test with Italy again to ensure no leak
        $playerIt = Player::factory()->for($italyTeam)->withLocale('it_IT')->create();
        $this->assertEquals(mb_strlen($playerIt->first_name), strlen($playerIt->first_name), 'Italian player name should not contain multibyte characters (usually)');
    }

    /**
     * Test the mapping in DatabaseSeeder indirectly by running a mini-version of it.
     */
    public function test_seeder_logic_applies_correct_locales(): void
    {
        // This is a bit tricky as we can't easily call private methods of the seeder.
        // But we can check if the seeder is doing what we expect.

        // I'll use Reflection to test the private method of DatabaseSeeder
        $seeder = new \Database\Seeders\DatabaseSeeder;
        $reflection = new \ReflectionClass($seeder);
        $method = $reflection->getMethod('getLocaleForCountry');
        $method->setAccessible(true);

        $this->assertEquals('it_IT', $method->invoke($seeder, 'ITA'));
        $this->assertEquals('ja_JP', $method->invoke($seeder, 'JPN'));
        $this->assertEquals('en_US', $method->invoke($seeder, 'USA'));
        $this->assertEquals('fr_FR', $method->invoke($seeder, 'FRA'));
    }
}
