<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Between Sets Duration
    |--------------------------------------------------------------------------
    |
    | The number of seconds that must elapse between the end of one set and
    | the start of the next. This value also drives the on-screen countdown.
    |
    */

    'between_sets_duration' => (int) env('GAME_BETWEEN_SETS_DURATION', 180),

    /*
    |--------------------------------------------------------------------------
    | Timeout Duration
    |--------------------------------------------------------------------------
    |
    | The number of seconds shown on the timeout countdown timer. Reduce this
    | during development to avoid waiting the full 30 seconds.
    |
    */

    'timeout_duration' => (int) env('GAME_TIMEOUT_DURATION', 30),

];
