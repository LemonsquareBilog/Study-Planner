<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Login as student
$user = \App\Models\User::where('email','alex.chen@student.test')->first();
$token = $user->createToken('test')->plainTextToken;
echo "Token: $token\n";

// Test the quiz endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/student/quizzes/3');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);
echo "Questions count: " . (isset($data['data']['questions']) ? count($data['data']['questions']) : 'no questions key') . "\n";
if (isset($data['data']['questions'])) {
    foreach ($data['data']['questions'] as $q) {
        echo "Q: " . $q['question'] . "\n";
    }
}