<?php

use Illuminate\Http\Request;

// PHP's built-in server does not apply auto_prepend_file to an included router.
// Load the isolated development environment explicitly before Laravel boots.
$localPublic = realpath(__DIR__.'/../public');
$localPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$localAsset = realpath($localPublic.rawurldecode($localPath));
if ($localPath !== '/' && $localAsset &&
    str_starts_with($localAsset, $localPublic.DIRECTORY_SEPARATOR) && is_file($localAsset)) {
    return false;
}

define('LARAVEL_START', microtime(true));
require __DIR__.'/local-environment.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->loadEnvironmentFrom('.env.development');
$app->handleRequest(Request::capture());
