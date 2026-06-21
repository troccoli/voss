<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->foreignId('game_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('side')->nullable()->after('game_id');
            $table->unique(['game_id', 'side']);
        });

        Schema::table('players', function (Blueprint $table): void {
            $table->foreignId('game_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number')->nullable()->after('last_name');
            $table->boolean('is_captain')->default(false)->after('number');
            $table->boolean('is_libero')->default(false)->after('is_captain');
            $table->boolean('is_rostered')->default(false)->after('is_libero');
            $table->unique(['team_id', 'number']);
        });

        Schema::table('staff', function (Blueprint $table): void {
            $table->foreignId('game_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->boolean('is_rostered')->default(false)->after('role');
        });

        Schema::table('officials', function (Blueprint $table): void {
            $table->foreignId('game_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable()->after('country_code');
            $table->unique(['game_id', 'role']);
        });

        DB::table('games')
            ->select(['id', 'home_team_id', 'away_team_id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $game): void {
                DB::table('teams')
                    ->where('id', $game->home_team_id)
                    ->update([
                        'game_id' => $game->id,
                        'side' => 'home',
                    ]);

                DB::table('teams')
                    ->where('id', $game->away_team_id)
                    ->update([
                        'game_id' => $game->id,
                        'side' => 'away',
                    ]);
            });

        DB::table('game_player')
            ->select(['game_id', 'player_id', 'number', 'is_captain', 'is_libero'])
            ->orderBy('id')
            ->get()
            ->each(function (object $rosterPlayer): void {
                DB::table('players')
                    ->where('id', $rosterPlayer->player_id)
                    ->update([
                        'game_id' => $rosterPlayer->game_id,
                        'number' => $rosterPlayer->number,
                        'is_captain' => $rosterPlayer->is_captain,
                        'is_libero' => $rosterPlayer->is_libero,
                        'is_rostered' => true,
                    ]);
            });

        DB::table('game_staff')
            ->select(['game_id', 'staff_id', 'role'])
            ->orderBy('id')
            ->get()
            ->each(function (object $rosterStaff): void {
                DB::table('staff')
                    ->where('id', $rosterStaff->staff_id)
                    ->update([
                        'game_id' => $rosterStaff->game_id,
                        'role' => $rosterStaff->role,
                        'is_rostered' => true,
                    ]);
            });

        DB::table('game_official')
            ->select(['game_id', 'official_id', 'role'])
            ->orderBy('id')
            ->get()
            ->each(function (object $gameOfficial): void {
                DB::table('officials')
                    ->where('id', $gameOfficial->official_id)
                    ->update([
                        'game_id' => $gameOfficial->game_id,
                        'role' => $gameOfficial->role,
                    ]);
            });

        Schema::table('games', function (Blueprint $table): void {
            $table->dropForeign(['competition_id']);
            $table->dropColumn('competition_id');
        });

        Schema::dropIfExists('game_player');
        Schema::dropIfExists('game_staff');
        Schema::dropIfExists('game_official');
        Schema::dropIfExists('competitions');
    }

    public function down(): void
    {
        Schema::create('competitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('games', function (Blueprint $table): void {
            $table->foreignId('competition_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::create('game_official', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('official_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();
        });

        Schema::create('game_player', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->boolean('is_captain')->default(false);
            $table->boolean('is_libero')->default(false);
            $table->timestamps();
        });

        Schema::create('game_staff', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();
        });

        Schema::table('officials', function (Blueprint $table): void {
            $table->dropUnique(['game_id', 'role']);
        });

        Schema::table('players', function (Blueprint $table): void {
            $table->dropUnique(['team_id', 'number']);
        });

        Schema::table('teams', function (Blueprint $table): void {
            $table->dropUnique(['game_id', 'side']);
        });

        Schema::table('officials', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('game_id');
            $table->dropColumn('role');
        });

        Schema::table('staff', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('game_id');
            $table->dropColumn('is_rostered');
        });

        Schema::table('players', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('game_id');
            $table->dropColumn(['number', 'is_captain', 'is_libero', 'is_rostered']);
        });

        Schema::table('teams', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('game_id');
            $table->dropColumn('side');
        });
    }
};
