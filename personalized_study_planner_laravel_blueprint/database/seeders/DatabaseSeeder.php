<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\StudyPreference;
use App\Models\Subject;
use App\Models\SystemSetting;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name'=>'System Administrator',
            'email'=>'admin@studyplanner.test',
            'password'=>Hash::make('AdminPassword123!'),
            'role'=>'admin',
            'is_active'=>true,
        ]);

        $student = User::create([
            'name'=>'Demo Student',
            'email'=>'student@studyplanner.test',
            'password'=>Hash::make('StudentPassword123!'),
            'role'=>'student',
            'course'=>'Bachelor of Science in Information Technology',
            'year_level'=>4,
            'is_active'=>true,
        ]);

        StudyPreference::create([
            'user_id'=>$student->id,
            'max_daily_minutes'=>180,
            'session_duration_minutes'=>50,
            'break_duration_minutes'=>10,
            'preferred_start_time'=>'18:00',
            'preferred_end_time'=>'22:00',
        ]);

        $database = Subject::create(['user_id'=>$student->id,'name'=>'Database Management','description'=>'Database concepts and SQL fundamentals.','difficulty'=>4,'color'=>'blue','is_active'=>true]);
        $web = Subject::create(['user_id'=>$student->id,'name'=>'Web Development','description'=>'Modern web application development.','difficulty'=>4,'color'=>'green','is_active'=>true]);
        $networking = Subject::create(['user_id'=>$student->id,'name'=>'Computer Networking','description'=>'Networking concepts and protocols.','difficulty'=>3,'color'=>'purple','is_active'=>true]);

        $normalization = Topic::create(['subject_id'=>$database->id,'name'=>'Normalization','description'=>'Database normalization concepts.','difficulty'=>4,'is_active'=>true]);
        $sql = Topic::create(['subject_id'=>$database->id,'name'=>'SQL Queries','description'=>'SQL querying fundamentals.','difficulty'=>3,'is_active'=>true]);
        $api = Topic::create(['subject_id'=>$web->id,'name'=>'REST APIs','description'=>'REST API architecture and HTTP.','difficulty'=>4,'is_active'=>true]);
        $frontend = Topic::create(['subject_id'=>$web->id,'name'=>'Frontend Development','description'=>'Frontend application development.','difficulty'=>3,'is_active'=>true]);
        $network = Topic::create(['subject_id'=>$networking->id,'name'=>'Network Fundamentals','description'=>'Basic networking concepts.','difficulty'=>3,'is_active'=>true]);

        Resource::create(['title'=>'Database Normalization Guide','description'=>'Introductory material for database normalization.','resource_type'=>'tutorial','url'=>'https://example.com/database-normalization','difficulty'=>'beginner','is_active'=>true])->topics()->attach($normalization);
        Resource::create(['title'=>'SQL Query Fundamentals','description'=>'Review material for fundamental SQL queries.','resource_type'=>'article','url'=>'https://example.com/sql-fundamentals','difficulty'=>'beginner','is_active'=>true])->topics()->attach($sql);
        Resource::create(['title'=>'REST API Fundamentals','description'=>'Introduction to REST APIs and HTTP methods.','resource_type'=>'tutorial','url'=>'https://example.com/rest-api','difficulty'=>'intermediate','is_active'=>true])->topics()->attach($api);

        foreach ([
            ['key'=>'weak_topic_threshold','value'=>'60','type'=>'integer','description'=>'Quiz percentage below which a topic is considered weak.'],
            ['key'=>'review_topic_threshold','value'=>'79','type'=>'integer','description'=>'Maximum percentage considered in need of review.'],
            ['key'=>'deadline_reminder_hours','value'=>'24','type'=>'integer','description'=>'Hours before a deadline to send a reminder.'],
            ['key'=>'study_session_reminder_minutes','value'=>'30','type'=>'integer','description'=>'Minutes before a study session to send a reminder.'],
        ] as $setting) {
            SystemSetting::create($setting);
        }
    }
}
