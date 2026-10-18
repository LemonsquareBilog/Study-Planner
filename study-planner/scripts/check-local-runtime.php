<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

require __DIR__.'/local-environment.php';

try {
    if (! in_array('sqlite', PDO::getAvailableDrivers(), true) || ! extension_loaded('mbstring')) {
        throw new RuntimeException('Local PHP requires PDO SQLite and mbstring. Use npm run dev:local to enable the installed extensions.');
    }
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    if (! $app->environment('local') || config('database.default') !== 'sqlite') {
        throw new RuntimeException('Local startup refused an unexpected environment or database driver.');
    }
    $connection = DB::connection();
    $connection->select('SELECT 1');
    if (! $connection->getSchemaBuilder()->hasTable('users')) {
        throw new RuntimeException('The development database has no users table. Review migrations before starting.');
    }
    echo 'Local runtime ready: PHP '.PHP_VERSION.', PDO SQLite, .env.development, '.$connection->table('users')->count()." preserved users.\n";
} catch (Throwable $error) {
    // Do not print database connection details, SQL bindings or configuration secrets.
    fwrite(STDERR, $error instanceof RuntimeException && ! $error instanceof PDOException &&
        ! $error instanceof QueryException ? $error->getMessage().PHP_EOL :
        "Local database preflight failed. Review the private development configuration.\n");
    exit(1);
}
