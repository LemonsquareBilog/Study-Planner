<?php

// Used only by the loopback PHP server; never loaded by the deployed application.
if (! in_array(PHP_SAPI, ['cli', 'cli-server'], true)) {
    http_response_code(404);
    exit;
}

require_once __DIR__.'/../vendor/autoload.php';

$localRoot = realpath(__DIR__.'/..');
$localFile = $localRoot.'/.env.development';
if (! is_file($localFile)) {
    throw new RuntimeException('Create .env.development from .env.development.example before starting the local server.');
}
$localValues = Dotenv\Dotenv::parse(file_get_contents($localFile));
if (($localValues['APP_ENV'] ?? '') !== 'local' ||
    ($localValues['DB_CONNECTION'] ?? '') !== 'sqlite' ||
    ! empty($localValues['DB_URL']) || ! empty($localValues['DATABASE_URL'])) {
    throw new RuntimeException('Local startup requires APP_ENV=local, DB_CONNECTION=sqlite, and no remote database URL.');
}
$localDatabase = $localValues['DB_DATABASE'] ?? '';
if (! preg_match('~^(?:[A-Za-z]:[/\\\\]|/)~', $localDatabase)) {
    $localDatabase = $localRoot.'/'.$localDatabase;
}
$localDatabase = realpath($localDatabase);
if (! $localDatabase || ! is_file($localDatabase)) {
    throw new RuntimeException('The configured development SQLite file is missing. No database was created or reset.');
}
$localValues['DB_DATABASE'] = $localDatabase;
// Isolate this process from root/Docker configuration and inherited cloud settings.
$localValues['DB_URL'] = '';
$localValues['DATABASE_URL'] = '';
$localValues['APP_CONFIG_CACHE'] = $localRoot.'/storage/framework/local-development-config.php';
foreach ($localValues as $localName => $localValue) {
    $_ENV[$localName] = $localValue;
    $_SERVER[$localName] = $localValue;
    putenv($localName.'='.$localValue);
}
