<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/** Explicit, additive migration of the original professor-demo dataset, never a startup seeder. */
class DemoAccountRestoreService
{
    public const EMAILS = [
        'admin@studyplanner.test', 'alex.chen@student.test', 'maria.santos@student.test',
        'james.wilson@student.test', 'sarah.kim@student.test',
    ];

    private const TABLES = [
        'users' => [],
        'study_preferences' => ['user_id' => 'users'],
        'subjects' => ['user_id' => 'users'],
        'topics' => ['subject_id' => 'subjects'],
        'tasks' => ['user_id' => 'users', 'subject_id' => 'subjects', 'topic_id' => 'topics'],
        'study_sessions' => ['user_id' => 'users', 'subject_id' => 'subjects', 'topic_id' => 'topics', 'task_id' => 'tasks'],
        'quizzes' => ['topic_id' => 'topics'],
        'questions' => ['quiz_id' => 'quizzes'],
        'quiz_attempts' => ['user_id' => 'users', 'quiz_id' => 'quizzes'],
        'quiz_answers' => ['quiz_attempt_id' => 'quiz_attempts', 'question_id' => 'questions'],
        'resources' => [],
        'resource_subject' => ['resource_id' => 'resources', 'subject_id' => 'subjects'],
        'resource_topic' => ['resource_id' => 'resources', 'topic_id' => 'topics'],
        'notifications' => ['user_id' => 'users'],
        'recommendation_views' => ['user_id' => 'users', 'resource_id' => 'resources', 'topic_id' => 'topics'],
    ];

    public function readSource(string $path): array
    {
        $path = realpath($path);
        if (! $path || ! is_file($path)) {
            throw new InvalidArgumentException('The preserved SQLite source must exist.');
        }
        $source = new PDO('sqlite:file:'.str_replace('\\', '/', $path).'?mode=ro');
        $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $source->exec('PRAGMA query_only=ON');
        $source->beginTransaction();
        $rows = [];
        foreach (self::TABLES as $table => $foreignKeys) {
            $rows[$table] = $source->query('SELECT * FROM '.$table)->fetchAll(PDO::FETCH_ASSOC);
        }
        $source->rollBack();
        $emails = array_column($rows['users'], 'email');
        $expected = self::EMAILS;
        sort($emails);
        sort($expected);
        if ($emails !== $expected) {
            throw new InvalidArgumentException('Source must contain exactly the five original demo accounts.');
        }
        foreach ($rows['users'] as $user) {
            $expectedRole = $user['email'] === self::EMAILS[0] ? 'admin' : 'student';
            if ($user['role'] !== $expectedRole || $user['deleted_at'] !== null) {
                throw new InvalidArgumentException('Source account role or deletion status is unexpected.');
            }
        }

        return ['sha256' => hash_file('sha256', $path), 'tables' => $rows];
    }

    public function plan(ConnectionInterface $target, array $source): array
    {
        $existing = $target->table('users')->whereIn('email', self::EMAILS)->pluck('email')->all();
        if ($existing !== []) {
            throw new RuntimeException('Restore refused: a selected demo account already exists. Existing users and passwords will not be overwritten.');
        }
        foreach ($source['tables'] as $table => $rows) {
            if (! $target->getSchemaBuilder()->hasTable($table)) {
                throw new RuntimeException('Target schema is missing '.$table.'.');
            }
        }

        return ['source_sha256' => $source['sha256'], 'accounts' => self::EMAILS,
            'rows' => array_map('count', $source['tables']),
            'existing_target_users' => $target->table('users')->count()];
    }

    public function apply(ConnectionInterface $target, array $source, array $passwords): array
    {
        foreach (self::EMAILS as $email) {
            $password = $passwords[$email] ?? null;
            if (! is_string($password) || strlen($password) < 20 || strlen($password) > 64) {
                throw new InvalidArgumentException('Each hosted demo account needs a private password of 20–64 bytes.');
            }
        }

        return $target->transaction(function () use ($target, $source, $passwords) {
            $plan = $this->plan($target, $source);
            $mapping = [];
            $inserted = [];
            foreach (self::TABLES as $table => $foreignKeys) {
                $mapping[$table] = [];
                $inserted[$table] = [];
                $rows = $source['tables'][$table];
                $numericIds = [];
                if ($rows !== [] && ! in_array($table, ['resource_subject', 'resource_topic', 'notifications'], true)) {
                    if ($target->getDriverName() === 'pgsql') {
                        $numericIds = array_map(fn ($result) => (int) $result->id, $target->select(
                            "SELECT nextval(pg_get_serial_sequence(?, 'id')) AS id FROM generate_series(1, ?)",
                            [$table, count($rows)]));
                    } else {
                        $first = (int) $target->table($table)->max('id') + 1;
                        $numericIds = range($first, $first + count($rows) - 1);
                    }
                }
                $batch = [];
                foreach ($rows as $index => $row) {
                    $oldId = $row['id'] ?? null;
                    unset($row['id']);
                    foreach ($foreignKeys as $column => $parent) {
                        if ($row[$column] !== null) {
                            if (! isset($mapping[$parent][$row[$column]])) {
                                throw new RuntimeException('Unmapped source relationship in '.$table.'.');
                            }
                            $row[$column] = $mapping[$parent][$row[$column]];
                        }
                    }
                    if ($table === 'users') {
                        // Compatible with the existing deployed bcrypt verifier; future login can rehash to Argon2id.
                        $row['password'] = password_hash($passwords[$row['email']], PASSWORD_BCRYPT, ['cost' => 12]);
                        $row['remember_token'] = null;
                    }
                    if (in_array($table, ['resource_subject', 'resource_topic'], true)) {
                        $inserted[$table][] = $row;
                    } else {
                        $row['id'] = $table === 'notifications' ? (string) Str::uuid() : $numericIds[$index];
                        $mapping[$table][$oldId] = $row['id'];
                        $inserted[$table][] = $row['id'];
                    }
                    $batch[] = $row;
                }
                if ($batch !== []) {
                    $size = min(100, max(1, intdiv(900, count($batch[0]))));
                    foreach (array_chunk($batch, $size) as $chunk) {
                        $target->table($table)->insert($chunk);
                    }
                }
            }
            foreach ($source['tables']['users'] as $user) {
                $newId = $mapping['users'][$user['id']];
                foreach (['subjects', 'tasks', 'study_sessions', 'quiz_attempts', 'notifications'] as $table) {
                    $expected = count(array_filter($source['tables'][$table], fn ($row) => $row['user_id'] === $user['id']));
                    if ($target->table($table)->where('user_id', $newId)->count() !== $expected) {
                        throw new RuntimeException('Restored account record counts do not match the source.');
                    }
                }
            }

            return [...$plan, 'mapping' => $mapping, 'inserted' => $inserted, 'completed_at' => now()->toIso8601String()];
        });
    }
}
