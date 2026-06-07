<?php

declare(strict_types=1);

use App\Livewire\Game;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::livewire('/game/{game}', Game::class)->name('game');
