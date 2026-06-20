<?php

declare(strict_types=1);

use App\Livewire\Game;
use App\Livewire\MatchSetup;
use App\Services\CurrentMatchResolver;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (CurrentMatchResolver $currentMatchResolver) => redirect()->route($currentMatchResolver->landingRouteName()))->name('home');
Route::livewire('/setup', MatchSetup::class)->name('match.setup');
Route::livewire('/game', Game::class)->name('game');
