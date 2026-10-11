<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Vercel's application bundle is read-only; only temporary runtime files go here.
$runtimeStorage = sys_get_temp_dir().'/study-planner';
foreach (['framework/views', 'framework/sessions', 'framework/cache/data', 'logs'] as $directory) {
    $path = $runtimeStorage.'/'.$directory;
    if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
        throw new RuntimeException('Unable to initialize application runtime storage.');
    }
}

foreach ([
    'LARAVEL_STORAGE_PATH' => $runtimeStorage,
    'VIEW_COMPILED_PATH' => $runtimeStorage.'/framework/views',
    'APP_PACKAGES_CACHE' => $runtimeStorage.'/framework/packages.php',
    'APP_SERVICES_CACHE' => $runtimeStorage.'/framework/services.php',
] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require __DIR__.'/../bootstrap/app.php';
$app->useStoragePath($runtimeStorage);
$app->addAbsoluteCachePathPrefix($runtimeStorage);

// The function lives in /api; Laravel routes still use the full request path.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

$app->handleRequest(Request::capture());
