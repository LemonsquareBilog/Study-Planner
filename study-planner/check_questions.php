<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$quiz = \App\Models\Quiz::find(3);
echo "Quiz: {$quiz->title}\n";
echo "Topic: {$quiz->topic->name}\n\n";

$questions = $quiz->questions()->orderBy('question_order')->get();
echo "Questions count: {$questions->count()}\n\n";

foreach ($questions as $q) {
    echo "Q{$q->question_order}: {$q->question}\n";
    echo "  A: {$q->option_a}\n";
    echo "  B: {$q->option_b}\n";
    echo "  C: {$q->option_c}\n";
    echo "  D: {$q->option_d}\n";
    echo "  Correct: {$q->correct_answer}\n\n";
}