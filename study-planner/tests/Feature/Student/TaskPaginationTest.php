<?php

namespace Tests\Feature\Student;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tied_tasks_paginate_without_duplicates_and_filters_remain_owned(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create(['user_id' => $student->id, 'name' => 'Science', 'difficulty' => 2, 'color' => 'blue', 'is_active' => true]);
        for ($i = 0; $i < 45; $i++) {
            $student->tasks()->create(['subject_id' => $subject->id, 'title' => 'Assignment '.$i, 'task_type' => 'assignment', 'priority' => 'high', 'status' => 'pending', 'deadline' => '2026-12-01 12:00:00', 'estimated_minutes' => 30]);
        }
        $this->actingAs($student, 'sanctum');
        foreach (['deadline_asc', 'deadline_desc', 'priority', 'created'] as $sort) {
            $ids = [];
            foreach ([1 => 20, 2 => 20, 3 => 5] as $page => $count) {
                $response = $this->getJson('/api/student/tasks?sort='.$sort.'&page='.$page)->assertOk()->assertJsonCount($count, 'data');
                array_push($ids, ...array_column($response->json('data'), 'id'));
            }
            $this->assertCount(45, array_unique($ids));
        }
        $this->getJson('/api/student/tasks?search=Assignment%2044')->assertJsonCount(1, 'data');
        $this->getJson('/api/student/tasks?status=completed')->assertJsonCount(0, 'data');
        $this->getJson('/api/student/tasks?to=2026-12-02')->assertOk()->assertJsonCount(20, 'data');
        $this->getJson('/api/student/tasks?sort=invalid')->assertUnprocessable();
        $this->getJson('/api/student/tasks?from=2026-12-02&to=2026-12-01')->assertUnprocessable();
        $this->actingAs(User::factory()->create(['role' => 'student', 'is_active' => true]), 'sanctum');
        $this->getJson('/api/student/tasks')->assertJsonCount(0, 'data');
        $this->getJson('/api/admin/users')->assertForbidden();
    }
}
