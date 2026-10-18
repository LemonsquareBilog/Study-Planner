<?php

return [
    'driver' => env('HASH_DRIVER', 'argon2id'),
    // Accept existing bcrypt hashes during migration; new writes use Argon2id.
    'bcrypt' => ['rounds' => (int) env('BCRYPT_ROUNDS', 12), 'verify' => false, 'limit' => 72],
    'argon' => ['memory' => 65536, 'threads' => 1, 'time' => 4, 'verify' => false],
    'rehash_on_login' => true,
];
