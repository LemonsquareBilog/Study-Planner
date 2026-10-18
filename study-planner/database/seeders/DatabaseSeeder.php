<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\RecommendationView;
use App\Models\Resource;
use App\Models\StudyPreference;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Sample data seeding is restricted to local and testing environments.');
        }
        if (User::exists()) {
            $this->command?->warn('Existing users preserved. Use DevelopmentAccountsSeeder to restore missing test accounts.');
            return;
        }
        $this->createSystemSettings();
        $admin = $this->createAdmin();
        $students = $this->createStudents();
        
        foreach ($students as $student) {
            $this->createStudyPreference($student);
            $subjects = $this->createSubjects($student);
            $topics = $this->createTopics($subjects);
            $tasks = $this->createTasks($student, $subjects, $topics);
            $this->createStudySessions($student, $subjects, $topics, $tasks);
            $quizzes = $this->createQuizzes($topics);
            $this->createQuizAttempts($student, $quizzes);
            $this->createNotifications($student, $tasks);
        }
        
        $resources = $this->createResources();
        $this->attachResourcesToSubjects($resources, $students);
        $this->attachResourcesToTopics($resources, $topics);
        $this->createRecommendationViews($students, $resources);
    }

    private function createSystemSettings(): void
    {
        foreach ([
            ['key' => 'weak_topic_threshold', 'value' => '60', 'type' => 'integer', 'description' => 'Quiz percentage below which a topic is considered weak.'],
            ['key' => 'review_topic_threshold', 'value' => '79', 'type' => 'integer', 'description' => 'Maximum percentage considered in need of review.'],
            ['key' => 'deadline_reminder_hours', 'value' => '24', 'type' => 'integer', 'description' => 'Hours before a deadline to send a reminder.'],
            ['key' => 'study_session_reminder_minutes', 'value' => '30', 'type' => 'integer', 'description' => 'Minutes before a study session to send a reminder.'],
            ['key' => 'max_study_plan_days', 'value' => '30', 'type' => 'integer', 'description' => 'Maximum days ahead to generate study plan.'],
            ['key' => 'default_session_duration', 'value' => '50', 'type' => 'integer', 'description' => 'Default study session duration in minutes.'],
            ['key' => 'default_break_duration', 'value' => '10', 'type' => 'integer', 'description' => 'Default break duration in minutes.'],
            ['key' => 'app_maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Enable maintenance mode for the application.'],
        ] as $setting) {
            SystemSetting::create($setting);
        }
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'System Administrator',
            'email' => 'admin@studyplanner.test',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createStudents(): array
    {
        $studentsData = [
            [
                'name' => 'Alex Chen',
                'email' => 'alex.chen@student.test',
                'password' => Hash::make('StudentPass123!'),
                'role' => 'student',
                'course' => 'Bachelor of Science in Computer Science',
                'year_level' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@student.test',
                'password' => Hash::make('StudentPass123!'),
                'role' => 'student',
                'course' => 'Bachelor of Science in Information Technology',
                'year_level' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'James Wilson',
                'email' => 'james.wilson@student.test',
                'password' => Hash::make('StudentPass123!'),
                'role' => 'student',
                'course' => 'Bachelor of Science in Data Science',
                'year_level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Sarah Kim',
                'email' => 'sarah.kim@student.test',
                'password' => Hash::make('StudentPass123!'),
                'role' => 'student',
                'course' => 'Bachelor of Science in Software Engineering',
                'year_level' => 3,
                'is_active' => true,
            ],
        ];

        return collect($studentsData)->map(fn($data) => User::create($data))->all();
    }

    private function createStudyPreference(User $student): void
    {
        $preferences = [
            ['max_daily_minutes' => 180, 'session_duration_minutes' => 50, 'break_duration_minutes' => 10, 'preferred_start_time' => '18:00', 'preferred_end_time' => '22:00'],
            ['max_daily_minutes' => 240, 'session_duration_minutes' => 60, 'break_duration_minutes' => 15, 'preferred_start_time' => '19:00', 'preferred_end_time' => '23:00'],
            ['max_daily_minutes' => 150, 'session_duration_minutes' => 45, 'break_duration_minutes' => 10, 'preferred_start_time' => '17:00', 'preferred_end_time' => '21:00'],
            ['max_daily_minutes' => 200, 'session_duration_minutes' => 55, 'break_duration_minutes' => 12, 'preferred_start_time' => '18:30', 'preferred_end_time' => '22:30'],
        ];

        $pref = $preferences[array_rand($preferences)];
        StudyPreference::create([
            'user_id' => $student->id,
            ...$pref,
        ]);
    }

    private function createSubjects(User $student): array
    {
        $subjectTemplates = [
            [
                'name' => 'Database Management Systems',
                'description' => 'Relational database design, SQL, normalization, transactions, and database administration.',
                'difficulty' => 4,
                'color' => 'blue',
            ],
            [
                'name' => 'Web Application Development',
                'description' => 'Full-stack web development using modern frameworks, APIs, and deployment practices.',
                'difficulty' => 4,
                'color' => 'green',
            ],
            [
                'name' => 'Computer Networks',
                'description' => 'Network protocols, OSI/TCP-IP models, routing, switching, and network security fundamentals.',
                'difficulty' => 3,
                'color' => 'purple',
            ],
            [
                'name' => 'Data Structures & Algorithms',
                'description' => 'Fundamental data structures, algorithm design, complexity analysis, and problem-solving techniques.',
                'difficulty' => 5,
                'color' => 'red',
            ],
            [
                'name' => 'Operating Systems',
                'description' => 'Process management, memory management, file systems, concurrency, and scheduling algorithms.',
                'difficulty' => 4,
                'color' => 'indigo',
            ],
            [
                'name' => 'Software Engineering',
                'description' => 'Software development lifecycle, design patterns, testing, version control, and project management.',
                'difficulty' => 3,
                'color' => 'yellow',
            ],
            [
                'name' => 'Machine Learning Fundamentals',
                'description' => 'Supervised/unsupervised learning, neural networks, model evaluation, and practical ML applications.',
                'difficulty' => 5,
                'color' => 'pink',
            ],
            [
                'name' => 'Cybersecurity Basics',
                'description' => 'Security principles, cryptography, network security, vulnerability assessment, and ethical hacking.',
                'difficulty' => 4,
                'color' => 'red',
            ],
        ];

        $selected = collect($subjectTemplates)->shuffle()->take(rand(4, 6))->values();
        
        return $selected->map(function ($template) use ($student) {
            return Subject::create([
                'user_id' => $student->id,
                ...$template,
                'is_active' => true,
            ]);
        })->all();
    }

    private function createTopics(array $subjects): array
    {
        $topicTemplates = [
            'Database Management Systems' => [
                ['name' => 'Relational Model & ER Diagrams', 'difficulty' => 3],
                ['name' => 'Normalization (1NF-3NF, BCNF)', 'difficulty' => 4],
                ['name' => 'SQL Queries (SELECT, JOINs, Subqueries)', 'difficulty' => 3],
                ['name' => 'Transactions & ACID Properties', 'difficulty' => 4],
                ['name' => 'Indexing & Query Optimization', 'difficulty' => 5],
                ['name' => 'Database Security & Authorization', 'difficulty' => 3],
            ],
            'Web Application Development' => [
                ['name' => 'HTTP & REST API Design', 'difficulty' => 3],
                ['name' => 'Frontend Frameworks (Vue/React)', 'difficulty' => 4],
                ['name' => 'Backend Development (Laravel/Node.js)', 'difficulty' => 4],
                ['name' => 'Authentication & Authorization', 'difficulty' => 3],
                ['name' => 'Database Integration & ORM', 'difficulty' => 3],
                ['name' => 'Deployment & CI/CD', 'difficulty' => 4],
            ],
            'Computer Networks' => [
                ['name' => 'OSI & TCP/IP Models', 'difficulty' => 2],
                ['name' => 'IP Addressing & Subnetting', 'difficulty' => 3],
                ['name' => 'Routing Protocols (OSPF, BGP)', 'difficulty' => 4],
                ['name' => 'Switching & VLANs', 'difficulty' => 3],
                ['name' => 'Network Security (Firewalls, VPNs)', 'difficulty' => 4],
                ['name' => 'Wireless Networks & Mobile IP', 'difficulty' => 3],
            ],
            'Data Structures & Algorithms' => [
                ['name' => 'Arrays, Lists & Strings', 'difficulty' => 2],
                ['name' => 'Stacks, Queues & Deques', 'difficulty' => 2],
                ['name' => 'Trees & Graphs', 'difficulty' => 4],
                ['name' => 'Sorting & Searching Algorithms', 'difficulty' => 3],
                ['name' => 'Dynamic Programming', 'difficulty' => 5],
                ['name' => 'Greedy Algorithms & Backtracking', 'difficulty' => 4],
            ],
            'Operating Systems' => [
                ['name' => 'Processes & Threads', 'difficulty' => 3],
                ['name' => 'CPU Scheduling Algorithms', 'difficulty' => 3],
                ['name' => 'Memory Management & Virtual Memory', 'difficulty' => 4],
                ['name' => 'File Systems & I/O', 'difficulty' => 3],
                ['name' => 'Deadlocks & Synchronization', 'difficulty' => 4],
                ['name' => 'Distributed Systems Basics', 'difficulty' => 5],
            ],
            'Software Engineering' => [
                ['name' => 'SDLC Models (Waterfall, Agile)', 'difficulty' => 2],
                ['name' => 'Requirements Engineering', 'difficulty' => 3],
                ['name' => 'Design Patterns (GoF)', 'difficulty' => 4],
                ['name' => 'Testing Strategies (Unit, Integration, E2E)', 'difficulty' => 3],
                ['name' => 'Version Control (Git)', 'difficulty' => 2],
                ['name' => 'Software Architecture (Microservices, Monolith)', 'difficulty' => 4],
            ],
            'Machine Learning Fundamentals' => [
                ['name' => 'Linear Regression & Classification', 'difficulty' => 3],
                ['name' => 'Decision Trees & Random Forests', 'difficulty' => 3],
                ['name' => 'Neural Networks & Backpropagation', 'difficulty' => 5],
                ['name' => 'Model Evaluation & Cross-Validation', 'difficulty' => 3],
                ['name' => 'Clustering & Dimensionality Reduction', 'difficulty' => 4],
                ['name' => 'MLOps & Model Deployment', 'difficulty' => 4],
            ],
            'Cybersecurity Basics' => [
                ['name' => 'CIA Triad & Security Principles', 'difficulty' => 2],
                ['name' => 'Symmetric & Asymmetric Cryptography', 'difficulty' => 4],
                ['name' => 'Network Attacks & Defense', 'difficulty' => 4],
                ['name' => 'Web Application Security (OWASP Top 10)', 'difficulty' => 4],
                ['name' => 'Vulnerability Scanning & Penetration Testing', 'difficulty' => 5],
                ['name' => 'Security Policies & Compliance', 'difficulty' => 3],
            ],
        ];

        $allTopics = [];
        foreach ($subjects as $subject) {
            $templates = $topicTemplates[$subject->name] ?? [
                ['name' => 'Fundamentals', 'difficulty' => 2],
                ['name' => 'Intermediate Concepts', 'difficulty' => 3],
                ['name' => 'Advanced Topics', 'difficulty' => 4],
            ];
            
            $selected = collect($templates)->shuffle()->take(rand(3, 5))->values();
            
            foreach ($selected as $template) {
                $topic = Topic::create([
                    'subject_id' => $subject->id,
                    'name' => $template['name'],
                    'description' => 'Comprehensive coverage of ' . strtolower($template['name']) . ' with practical examples and exercises.',
                    'difficulty' => $template['difficulty'],
                    'is_active' => true,
                ]);
                $allTopics[] = $topic;
            }
        }

        return $allTopics;
    }

    private function createTasks(User $student, array $subjects, array $topics): array
    {
        $taskTypes = ['assignment', 'exam', 'project', 'quiz', 'reading', 'review', 'other'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        
        $tasks = [];
        $now = Carbon::now();
        
        foreach ($subjects as $subject) {
            $subjectTopics = collect($topics)->where('subject_id', $subject->id)->all();
            $numTasks = rand(4, 8);
            
            for ($i = 0; $i < $numTasks; $i++) {
                $daysOffset = rand(-14, 30);
                $deadline = $now->copy()->addDays($daysOffset)->setTime(rand(9, 23), rand(0, 59));
                $status = $daysOffset < 0 ? 'completed' : $statuses[array_rand($statuses)];
                
                if ($status === 'completed') {
                    $deadline = $now->copy()->subDays(rand(1, 14))->setTime(rand(9, 23), rand(0, 59));
                }
                
                $task = Task::create([
                    'user_id' => $student->id,
                    'subject_id' => $subject->id,
                    'topic_id' => $subjectTopics ? $subjectTopics[array_rand($subjectTopics)]->id : null,
                    'title' => $this->generateTaskTitle($subject->name, $taskTypes[array_rand($taskTypes)]),
                    'description' => $this->generateTaskDescription($subject->name),
                    'task_type' => $taskTypes[array_rand($taskTypes)],
                    'priority' => $priorities[array_rand($priorities)],
                    'deadline' => $deadline,
                    'estimated_minutes' => rand(30, 240),
                    'status' => $status,
                    'completed_at' => $status === 'completed' ? $deadline->copy()->addMinutes(rand(-60, 30)) : null,
                ]);
                
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    private function generateTaskTitle(string $subject, string $type): string
    {
        $titles = [
            'assignment' => [
                'Complete assignment on ',
                'Submit ',
                'Work on ',
            ],
            'exam' => [
                'Prepare for ',
                'Study for ',
                'Review for ',
            ],
            'project' => [
                'Build ',
                'Develop ',
                'Implement ',
            ],
            'quiz' => [
                'Take quiz on ',
                'Complete quiz for ',
            ],
            'reading' => [
                'Read chapter on ',
                'Review literature about ',
            ],
            'review' => [
                'Review ',
                'Summarize key concepts from ',
            ],
            'other' => [
                'Work on ',
                'Complete ',
            ],
        ];

        $prefix = $titles[$type][array_rand($titles[$type])];
        $topic = $this->getSubjectTopic($subject);
        
        return $prefix . $topic;
    }

    private function getSubjectTopic(string $subject): string
    {
        $topics = [
            'Database Management Systems' => ['ER Diagrams', 'Normalization', 'SQL Joins', 'Transactions', 'Indexing', 'Security'],
            'Web Application Development' => ['REST APIs', 'Vue Components', 'Laravel Controllers', 'Authentication', 'Deployment', 'Testing'],
            'Computer Networks' => ['Subnetting', 'Routing Protocols', 'VLAN Configuration', 'Firewall Rules', 'Wireless Setup'],
            'Data Structures & Algorithms' => ['Binary Trees', 'Graph Traversal', 'Dynamic Programming', 'Sorting Algorithms', 'Hash Tables'],
            'Operating Systems' => ['Process Scheduling', 'Memory Allocation', 'File System Design', 'Deadlock Prevention', 'Thread Synchronization'],
            'Software Engineering' => ['Requirements Spec', 'Design Patterns', 'Unit Testing', 'Git Workflow', 'Architecture Design'],
            'Machine Learning Fundamentals' => ['Linear Regression', 'Neural Networks', 'Model Evaluation', 'Clustering', 'Feature Engineering'],
            'Cybersecurity Basics' => ['Cryptography', 'Network Security', 'OWASP Top 10', 'Penetration Testing', 'Compliance'],
        ];

        $list = $topics[$subject] ?? ['General Concepts'];
        return $list[array_rand($list)];
    }

    private function generateTaskDescription(string $subject): string
    {
        return "Detailed task for {$subject}. Complete all required components and submit before deadline. Refer to course materials and recommended resources for guidance.";
    }

    private function createStudySessions(User $student, array $subjects, array $topics, array $tasks): void
    {
        $now = Carbon::now();
        $statuses = ['scheduled', 'in_progress', 'completed', 'skipped', 'cancelled'];
        $completedStatuses = ['completed', 'skipped'];
        $usedSlots = [];
        
        foreach ($subjects as $subject) {
            $subjectTopics = collect($topics)->where('subject_id', $subject->id)->all();
            $subjectTasks = collect($tasks)->where('subject_id', $subject->id)->all();
            
            for ($i = 0; $i < rand(5, 12); $i++) {
                $daysOffset = rand(-21, 14);
                $dateStr = $now->copy()->addDays($daysOffset)->format('Y-m-d');
                
                // Find an available time slot
                $attempts = 0;
                do {
                    $startHour = rand(17, 21);
                    $startMinute = [0, 15, 30, 45][array_rand([0, 15, 30, 45])];
                    $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);
                    
                    $duration = [30, 45, 50, 60, 75, 90, 120][array_rand([30, 45, 50, 60, 75, 90, 120])];
                    $endTime = Carbon::createFromFormat('H:i:s', $startTime)->addMinutes($duration)->format('H:i:s');
                    
                    $slotKey = $student->id . '_' . $dateStr . '_' . $startTime . '_' . $endTime;
                    $attempts++;
                } while (isset($usedSlots[$slotKey]) && $attempts < 10);
                
                if ($attempts >= 10) continue; // Skip if can't find unique slot
                
                $usedSlots[$slotKey] = true;
                
                $status = $daysOffset < 0 ? $completedStatuses[array_rand($completedStatuses)] : $statuses[array_rand($statuses)];
                
                $task = !empty($subjectTasks) && rand(0, 1) ? $subjectTasks[array_rand($subjectTasks)] : null;
                $topic = !empty($subjectTopics) && rand(0, 1) ? $subjectTopics[array_rand($subjectTopics)] : null;
                
                StudySession::create([
                    'user_id' => $student->id,
                    'subject_id' => $subject->id,
                    'topic_id' => $topic?->id,
                    'task_id' => $task?->id,
                    'scheduled_date' => $dateStr,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'duration_minutes' => $duration,
                    'status' => $status,
                    'started_at' => in_array($status, ['in_progress', 'completed']) ? $dateStr . ' ' . $startTime : null,
                    'completed_at' => $status === 'completed' ? $dateStr . ' ' . $endTime : null,
                ]);
            }
        }
    }

    private function createQuizzes(array $topics): array
    {
        $quizzes = [];
        
        foreach ($topics as $topic) {
            $quiz = Quiz::create([
                'topic_id' => $topic->id,
                'title' => $topic->name . ' Quiz',
                'description' => 'Test your understanding of ' . $topic->name . ' with this comprehensive quiz.',
                'passing_score' => rand(60, 75),
                'is_active' => true,
            ]);
            
            $this->createQuestions($quiz);
            $quizzes[] = $quiz;
        }
        
        return $quizzes;
    }

    private function createQuestions(Quiz $quiz): void
    {
        $questions = $this->questionTemplates($quiz->topic->name);

        foreach ($questions as $i => $question) {
            Question::create([
                'quiz_id' => $quiz->id,
                ...$question,
                'question_order' => $i + 1,
            ]);
        }
    }

    private function questionTemplates(string $topicName): array
    {
        $topic = strtolower($topicName);

        if (str_contains($topic, 'normalization')) {
            return [
                ['question' => 'Which normal form removes repeating groups from a relation?', 'option_a' => '1NF', 'option_b' => '2NF', 'option_c' => '3NF', 'option_d' => 'BCNF', 'correct_answer' => 'A'],
                ['question' => 'What does a candidate key uniquely identify?', 'option_a' => 'A table engine', 'option_b' => 'A row', 'option_c' => 'A database', 'option_d' => 'A query plan', 'correct_answer' => 'B'],
                ['question' => 'A relation is in 3NF when it is in 2NF and has no non-key attribute depending on what?', 'option_a' => 'A view', 'option_b' => 'A foreign table', 'option_c' => 'Another non-key attribute', 'option_d' => 'An index', 'correct_answer' => 'C'],
                ['question' => 'What is the main purpose of normalization?', 'option_a' => 'Reduce redundant data', 'option_b' => 'Increase duplicate rows', 'option_c' => 'Remove all indexes', 'option_d' => 'Avoid primary keys', 'correct_answer' => 'A'],
            ];
        }

        if (str_contains($topic, 'sql') || str_contains($topic, 'query')) {
            return [
                ['question' => 'Which SQL clause filters rows before grouping?', 'option_a' => 'ORDER BY', 'option_b' => 'WHERE', 'option_c' => 'HAVING', 'option_d' => 'LIMIT', 'correct_answer' => 'B'],
                ['question' => 'Which join returns only rows with matching values in both tables?', 'option_a' => 'INNER JOIN', 'option_b' => 'LEFT JOIN', 'option_c' => 'CROSS JOIN', 'option_d' => 'FULL JOIN', 'correct_answer' => 'A'],
                ['question' => 'Which command changes existing rows?', 'option_a' => 'INSERT', 'option_b' => 'CREATE', 'option_c' => 'UPDATE', 'option_d' => 'GRANT', 'correct_answer' => 'C'],
                ['question' => 'What does an index primarily improve?', 'option_a' => 'Read lookup speed', 'option_b' => 'Password strength', 'option_c' => 'Disk capacity', 'option_d' => 'Column names', 'correct_answer' => 'A'],
            ];
        }

        if (str_contains($topic, 'linear regression')) {
            return [
                ['question' => 'What does linear regression predict?', 'option_a' => 'A continuous value', 'option_b' => 'Only image labels', 'option_c' => 'A database key', 'option_d' => 'A network route', 'correct_answer' => 'A'],
                ['question' => 'What is the dependent variable in a regression model?', 'option_a' => 'The value being predicted', 'option_b' => 'The training algorithm', 'option_c' => 'The learning rate', 'option_d' => 'The data type', 'correct_answer' => 'A'],
                ['question' => 'Which metric measures average squared prediction error?', 'option_a' => 'Accuracy', 'option_b' => 'Mean squared error', 'option_c' => 'Recall', 'option_d' => 'Support', 'correct_answer' => 'B'],
                ['question' => 'Why split data into training and test sets?', 'option_a' => 'To evaluate generalization', 'option_b' => 'To duplicate records', 'option_c' => 'To remove labels', 'option_d' => 'To avoid features', 'correct_answer' => 'A'],
            ];
        }

        return [
            ['question' => "Which statement best describes {$topicName}?", 'option_a' => 'It applies a defined concept in the subject area', 'option_b' => 'It is unrelated to the subject', 'option_c' => 'It removes the need for practice', 'option_d' => 'It only applies to hardware', 'correct_answer' => 'A'],
            ['question' => 'What is the best first step when learning a difficult topic?', 'option_a' => 'Identify the core concept', 'option_b' => 'Skip the explanation', 'option_c' => 'Memorize without examples', 'option_d' => 'Avoid reviewing mistakes', 'correct_answer' => 'A'],
            ['question' => 'Which activity most directly checks understanding?', 'option_a' => 'Retrieval practice', 'option_b' => 'Ignoring feedback', 'option_c' => 'Reading only the title', 'option_d' => 'Skipping exercises', 'correct_answer' => 'A'],
            ['question' => 'Why review incorrect quiz answers?', 'option_a' => 'To identify and correct misconceptions', 'option_b' => 'To hide weak areas', 'option_c' => 'To reduce study time to zero', 'option_d' => 'To change the score', 'correct_answer' => 'A'],
        ];
    }

    private function createQuizAttempts(User $student, array $quizzes): void
    {
        foreach ($quizzes as $quiz) {
            $questions = $quiz->questions()->orderBy('question_order')->get();
            if ($questions->isEmpty()) continue;
            
            $numAttempts = rand(1, 3);
            
            for ($attemptNum = 0; $attemptNum < $numAttempts; $attemptNum++) {
                $startedAt = Carbon::now()->subDays(rand(1, 30))->subHours(rand(0, 5));
                $answers = [];
                $score = 0;
                
                foreach ($questions as $question) {
                    $isCorrect = rand(0, 100) < 70; // 70% chance of correct
                    $selectedAnswer = $isCorrect ? $question->correct_answer : $this->getWrongAnswer($question->correct_answer);
                    
                    if ($isCorrect) $score++;
                    
                    $answers[] = [
                        'question_id' => $question->id,
                        'selected_answer' => $selectedAnswer,
                        'is_correct' => $isCorrect,
                    ];
                }
                
                $percentage = round(($score / $questions->count()) * 100, 2);
                $completedAt = $startedAt->copy()->addMinutes(rand(10, 45));
                
                $attempt = QuizAttempt::create([
                    'user_id' => $student->id,
                    'quiz_id' => $quiz->id,
                    'score' => $score,
                    'total_questions' => $questions->count(),
                    'percentage' => $percentage,
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                ]);
                
                foreach ($answers as $answer) {
                    QuizAnswer::create([
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $answer['question_id'],
                        'selected_answer' => $answer['selected_answer'],
                        'is_correct' => $answer['is_correct'],
                    ]);
                }
            }
        }
    }

    private function getWrongAnswer(string $correctAnswer): string
    {
        $options = ['A', 'B', 'C', 'D'];
        $wrong = array_diff($options, [$correctAnswer]);
        return $wrong[array_rand($wrong)];
    }

    private function createResources(): array
    {
        $resourceData = [
            [
                'title' => 'Database Normalization: A Complete Guide',
                'description' => 'Comprehensive tutorial covering 1NF through 5NF with practical examples.',
                'resource_type' => 'tutorial',
                'url' => 'https://www.guru99.com/database-normalization.html',
                'difficulty' => 'beginner',
            ],
            [
                'title' => 'SQL Performance Tuning Best Practices',
                'description' => 'Advanced techniques for optimizing SQL queries, indexing strategies, and execution plans.',
                'resource_type' => 'article',
                'url' => 'https://use-the-index-luke.com/',
                'difficulty' => 'advanced',
            ],
            [
                'title' => 'REST API Design Guidelines',
                'description' => 'Microsoft REST API guidelines for designing consistent, maintainable APIs.',
                'resource_type' => 'documentation',
                'url' => 'https://github.com/microsoft/api-guidelines/blob/vNext/Guidelines.md',
                'difficulty' => 'intermediate',
            ],
            [
                'title' => 'Vue 3 Composition API Tutorial',
                'description' => 'Step-by-step guide to Vue 3 Composition API with practical examples.',
                'resource_type' => 'tutorial',
                'url' => 'https://vuejs.org/guide/extras/composition-api-faq.html',
                'difficulty' => 'beginner',
            ],
            [
                'title' => 'Laravel 11.x Documentation',
                'description' => 'Official Laravel framework documentation for version 11.',
                'resource_type' => 'documentation',
                'url' => 'https://laravel.com/docs/11.x',
                'difficulty' => 'intermediate',
            ],
            [
                'title' => 'Computer Networking: A Top-Down Approach',
                'description' => 'Classic textbook covering network protocols, routing, and transport layer.',
                'resource_type' => 'ebook',
                'url' => 'https://www.pearson.com/en-us/subject-catalog/p/computer-networking-a-top-down-approach/P200000003500',
                'difficulty' => 'intermediate',
            ],
            [
                'title' => 'Subnetting Made Easy',
                'description' => 'Video tutorial explaining IPv4 subnetting with practice problems.',
                'resource_type' => 'video',
                'url' => 'https://www.youtube.com/watch?v=s_NttVqh1WQ',
                'difficulty' => 'beginner',
            ],
            [
                'title' => 'Dynamic Programming Patterns',
                'description' => 'Comprehensive guide to recognizing and solving dynamic programming problems.',
                'resource_type' => 'article',
                'url' => 'https://leetcode.com/discuss/general-discussion/458695/dynamic-programming-patterns',
                'difficulty' => 'advanced',
            ],
            [
                'title' => 'Operating System Concepts (Silberschatz)',
                'description' => 'Standard textbook covering processes, memory, file systems, and distributed systems.',
                'resource_type' => 'ebook',
                'url' => 'https://www.wiley.com/en-us/Operating+System+Concepts%2C+10th+Edition-p-9781119456339',
                'difficulty' => 'intermediate',
            ],
            [
                'title' => 'Clean Architecture by Robert Martin',
                'description' => 'Essential reading on software architecture principles and design patterns.',
                'resource_type' => 'ebook',
                'url' => 'https://www.oreilly.com/library/view/clean-architecture-a/9780134494272/',
                'difficulty' => 'advanced',
            ],
            [
                'title' => 'Machine Learning Crash Course (Google)',
                'description' => 'Free course covering ML fundamentals with TensorFlow exercises.',
                'resource_type' => 'tutorial',
                'url' => 'https://developers.google.com/machine-learning/crash-course',
                'difficulty' => 'beginner',
            ],
            [
                'title' => 'OWASP Top 10 Web Application Security Risks',
                'description' => 'Industry standard for the most critical web application security risks.',
                'resource_type' => 'documentation',
                'url' => 'https://owasp.org/www-project-top-ten/',
                'difficulty' => 'intermediate',
            ],
            [
                'title' => 'Cryptography I (Stanford/Coursera)',
                'description' => 'Introduction to cryptographic primitives and protocols.',
                'resource_type' => 'video',
                'url' => 'https://www.coursera.org/learn/crypto',
                'difficulty' => 'advanced',
            ],
            [
                'title' => 'Design Patterns: Elements of Reusable Object-Oriented Software',
                'description' => 'The classic Gang of Four book on software design patterns.',
                'resource_type' => 'ebook',
                'url' => 'https://www.oreilly.com/library/view/design-patterns-elements/9780201633610/',
                'difficulty' => 'advanced',
            ],
            [
                'title' => 'Git and GitHub Complete Guide',
                'description' => 'Comprehensive tutorial on version control with Git and collaboration on GitHub.',
                'resource_type' => 'tutorial',
                'url' => 'https://git-scm.com/doc',
                'difficulty' => 'beginner',
            ],
        ];

        $resources = [];
        foreach ($resourceData as $data) {
            $resources[] = Resource::create([
                ...$data,
                'is_active' => true,
            ]);
        }

        return $resources;
    }

    private function attachResourcesToSubjects(array $resources, array $students): void
    {
        foreach ($students as $student) {
            $subjects = $student->subjects()->get();
            if ($subjects->isEmpty()) continue;
            
            foreach ($resources as $resource) {
                if (rand(0, 1)) {
                    $subject = $subjects->random();
                    $resource->subjects()->syncWithoutDetaching([$subject->id]);
                }
            }
        }
    }

    private function attachResourcesToTopics(array $resources, array $topics): void
    {
        if (empty($topics)) return;
        
        foreach ($resources as $resource) {
            $matchingTopics = collect($topics)->filter(function ($topic) use ($resource) {
                return stripos($topic->name, $resource->title) !== false || 
                       stripos($resource->title, $topic->name) !== false ||
                       rand(0, 2) === 0; // 33% chance to attach anyway
            });
            
            if ($matchingTopics->isNotEmpty()) {
                $attachCount = min(rand(1, 3), $matchingTopics->count());
                $selected = $matchingTopics->random($attachCount);
                $resource->topics()->syncWithoutDetaching($selected->pluck('id')->toArray());
            }
        }
    }

    private function createNotifications(User $student, array $tasks): void
    {
        $now = Carbon::now();
        $types = ['task_reminder', 'deadline_reminder', 'study_session_reminder', 'recommendation', 'system'];
        
        // Task deadline reminders
        $upcomingTasks = collect($tasks)->where('deadline', '>', $now)->where('status', '!=', 'completed')->take(5);
        foreach ($upcomingTasks as $task) {
            Notification::create([
                'user_id' => $student->id,
                'type' => 'deadline_reminder',
                'title' => 'Upcoming Deadline: ' . $task->title,
                'message' => "Your task \"{$task->title}\" is due on {$task->deadline->format('M d, Y \a\t g:i A')}.",
                'scheduled_at' => $task->deadline->copy()->subHours(24),
                'delivery_status' => 'pending',
            ]);
        }
        
        // Study session reminders
        $sessions = $student->studySessions()->where('scheduled_date', '>=', $now->format('Y-m-d'))->take(5)->get();
        foreach ($sessions as $session) {
            Notification::create([
                'user_id' => $student->id,
                'type' => 'study_session_reminder',
                'title' => 'Study Session Reminder',
                'message' => "You have a study session for {$session->subject->name} scheduled for {$session->scheduled_date} at {$session->start_time}.",
                'scheduled_at' => Carbon::parse($session->scheduled_date->format('Y-m-d') . ' ' . $session->start_time)->subMinutes(30),
                'delivery_status' => 'pending',
            ]);
        }
        
        // General notifications
        $generalNotifications = [
            ['type' => 'system', 'title' => 'Welcome to Study Planner!', 'message' => 'Get started by adding your subjects and creating your first study plan.'],
            ['type' => 'recommendation', 'title' => 'New Resource Recommendation', 'message' => 'Based on your recent quiz performance, we recommend reviewing our materials on Database Normalization.'],
            ['type' => 'task_reminder', 'title' => 'Weekly Planning Reminder', 'message' => 'Don\'t forget to plan your study sessions for the upcoming week.'],
        ];
        
        foreach ($generalNotifications as $notif) {
            Notification::create([
                'user_id' => $student->id,
                ...$notif,
                'scheduled_at' => $now->copy()->subDays(rand(1, 7)),
                'delivery_status' => 'sent',
                'sent_at' => $now->copy()->subDays(rand(1, 7)),
                'read_at' => rand(0, 1) ? $now->copy()->subDays(rand(0, 6)) : null,
            ]);
        }
    }

    private function createRecommendationViews(array $students, array $resources): void
    {
        foreach ($students as $student) {
            $studentResources = $student->subjects()
                ->with('resources')
                ->get()
                ->flatMap->resources
                ->unique('id');
            
            if ($studentResources->isEmpty()) continue;
            
            $viewCount = rand(3, 8);
            $selected = $studentResources->random(min($viewCount, $studentResources->count()));
            
            foreach ($selected as $resource) {
                RecommendationView::create([
                    'user_id' => $student->id,
                    'resource_id' => $resource->id,
                    'topic_id' => $resource->topics()->inRandomOrder()->first()?->id,
                    'viewed_at' => Carbon::now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }
}