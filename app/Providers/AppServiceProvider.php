<?php

namespace App\Providers;

use App\Models\GameEvent;
use App\Observers\GameEventObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        GameEvent::observe(GameEventObserver::class);
    }
}
