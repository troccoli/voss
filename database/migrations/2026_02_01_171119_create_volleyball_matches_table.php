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
        Schema::create('volleyball_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('championship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedTinyInteger('match_number');
            $table->char('country_code', 3);
            $table->string('city');
            $table->string('hall');
            $table->dateTime('match_date_time');
            $table->string('division')->nullable();
            $table->string('pool')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }
};
