<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_state_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_event_id')->constrained('game_events')->cascadeOnDelete();
            $table->unsignedTinyInteger('set_number')->default(0);
            $table->unsignedTinyInteger('score_team_a')->default(0);
            $table->unsignedTinyInteger('score_team_b')->default(0);
            $table->unsignedTinyInteger('sets_won_team_a')->default(0);
            $table->unsignedTinyInteger('sets_won_team_b')->default(0);
            $table->unsignedTinyInteger('timeouts_team_a')->default(0);
            $table->unsignedTinyInteger('timeouts_team_b')->default(0);
            $table->unsignedTinyInteger('substitutions_team_a')->default(0);
            $table->unsignedTinyInteger('substitutions_team_b')->default(0);
            $table->string('serving_team')->nullable(); // App\Enums\TeamAB
            $table->json('rotation_team_a');
            $table->json('rotation_team_b');
            $table->boolean('set_in_progress')->default(false);
            $table->boolean('game_ended')->default(false);
            $table->timestamp('created_at');

            $table->unique('game_event_id');
            $table->index(['game_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_state_snapshots');
    }
};
