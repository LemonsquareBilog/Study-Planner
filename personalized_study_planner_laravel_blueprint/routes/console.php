<?php

use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a simple
| approach to defining console commands without the need to define a
| separate command class.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Str::random(10));
})->purpose('Display an inspiring quote');