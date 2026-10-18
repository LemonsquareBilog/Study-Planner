<?php

// CLI only. No route, automatic seeder, reset, or overwrite of existing accounts.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__.'/../vendor/autoload.php';

use App\Services\DemoAccountRestoreService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

$options = getopt('', ['source:', 'target-env:', 'target-url:', 'apply', 'allow-production-demo']);
$root = realpath(__DIR__.'/..');
$private = $root.'/.verification';
$target = null;
try {
    foreach (['source', 'target-env', 'target-url'] as $required) {
        if (empty($options[$required])) {
            throw new RuntimeException('Required options: --source, --target-env, --target-url. Default is dry run.');
        }
    }
    $values = Dotenv\Dotenv::parse(file_get_contents($options['target-env']));
    if (($values['APP_ENV'] ?? '') !== 'production' ||
        rtrim($values['APP_URL'] ?? '', '/') !== rtrim($options['target-url'], '/') ||
        ! str_starts_with($options['target-url'], 'https://')) {
        throw new RuntimeException('Target environment and exact HTTPS application URL must match.');
    }
    $values['DB_URL'] = $values['DATABASE_URL_UNPOOLED'] ?? $values['DATABASE_URL'] ?? '';
    $values['DB_CONNECTION'] = 'pgsql';
    if (! $values['DB_URL']) {
        throw new RuntimeException('Target database configuration is absent.');
    }
    foreach ($values as $key => $value) {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key.'='.$value);
    }
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $target = DB::connection();
    $source = (new DemoAccountRestoreService)->readSource($options['source']);
    $fingerprint = hash('sha256', $values['DB_URL']);
    $receiptPath = $private.'/hosted-demo-restore-receipt.json';
    if (is_file($receiptPath)) {
        $receipt = json_decode(file_get_contents($receiptPath), true, flags: JSON_THROW_ON_ERROR);
        if ($receipt['target_fingerprint'] !== $fingerprint || $receipt['source_sha256'] !== $source['sha256']) {
            throw new RuntimeException('A receipt exists for a different target or source. Refusing to continue.');
        }
        foreach ($receipt['accounts'] as $email) {
            if (! $target->table('users')->where('email', $email)->exists()) {
                throw new RuntimeException('Receipt exists but a restored account is missing. Manual review required.');
            }
        }
        echo "Already restored. Existing accounts and their current passwords/data were preserved.\n";
        exit;
    }
    $service = new DemoAccountRestoreService;
    $plan = $service->plan($target, $source);
    echo json_encode(['mode' => isset($options['apply']) ? 'apply' : 'dry-run', 'application' => $options['target-url'], ...$plan], JSON_PRETTY_PRINT).PHP_EOL;
    if (! isset($options['apply'])) {
        exit;
    }
    if (! isset($options['allow-production-demo'])) {
        throw new RuntimeException('Hosted demo restoration requires explicit --allow-production-demo. Normal production seeding stays blocked.');
    }
    $credentialsPath = $private.'/hosted-demo-credentials.json';
    if (file_exists($credentialsPath)) {
        $passwords = json_decode(file_get_contents($credentialsPath), true, flags: JSON_THROW_ON_ERROR);
    } else {
        $passwords = [];
        foreach (DemoAccountRestoreService::EMAILS as $email) {
            $passwords[$email] = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        }
        if (file_put_contents($credentialsPath, json_encode($passwords, JSON_PRETTY_PRINT), LOCK_EX) === false) {
            throw new RuntimeException('Unable to save private credentials; no accounts were inserted.');
        }
        chmod($credentialsPath, 0600);
    }
    $target->beginTransaction();
    $target->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $tables = array_keys($source['tables']);
    $backup = [];
    foreach ($tables as $table) {
        $backup[$table] = $target->table($table)->get()->map(fn ($row) => (array) $row)->all();
    }
    $backupPath = $private.'/hosted-demo-before-'.gmdate('Ymd-His').'.json';
    if (file_put_contents($backupPath, json_encode($backup, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
        throw new RuntimeException('Unable to create rollback snapshot; no accounts were inserted.');
    }
    chmod($backupPath, 0600);
    $result = $service->apply($target, $source, $passwords);
    $result['target_fingerprint'] = $fingerprint;
    $result['backup_path'] = $backupPath;
    if (file_put_contents($receiptPath, json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX) === false) {
        throw new RuntimeException('Unable to save import receipt; changes will be rolled back.');
    }
    chmod($receiptPath, 0600);
    $target->commit();
    echo "Restored exactly five demo accounts. Existing hosted users were retained. Credentials and rollback receipt saved privately under .verification/.\n";
} catch (Throwable $error) {
    if ($target?->transactionLevel() > 0) {
        $target->rollBack();
    }
    // Database errors may contain connection credentials or bound hashes; never print them.
    $safe = $error instanceof RuntimeException && ! $error instanceof PDOException && ! $error instanceof QueryException;
    fwrite(STDERR, $safe ? $error->getMessage().PHP_EOL : 'Demo restoration failed ['.get_class($error).']. Sensitive database details redacted.'.PHP_EOL);
    exit(1);
}
