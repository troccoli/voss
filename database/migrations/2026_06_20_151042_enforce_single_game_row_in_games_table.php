<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $gameIdsToRemove = DB::table('games')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->pluck('id')
            ->skip(1)
            ->all();

        if ($gameIdsToRemove !== []) {
            DB::table('games')->whereIn('id', $gameIdsToRemove)->delete();
        }

        DB::statement('CREATE UNIQUE INDEX games_singleton_row ON games ((1))');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS games_singleton_row');
    }
};
