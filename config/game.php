<?php

declare(strict_types=1);

return [

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
