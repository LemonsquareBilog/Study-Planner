<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$u = \App\Models\User::where('email','alex.chen@student.test')->first();
$subjects = $u->subjects()->get(['id','name']);
print_r($subjects->toArray());