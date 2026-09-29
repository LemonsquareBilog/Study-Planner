<?php

namespace Tests\Unit;

use App\Models\Resource;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommends_resources_matching_weak_topics(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['user_id' => $user->id]);
        $topic = Topic::factory()->create(['subject_id' => $subject->id, 'mastery_percentage' => 30.0]); // weak topic

        $resource = Resource::create([
            'title' => 'Weak Topic Video',
            'url' => 'https://example.com/video',
            'type' => 'video',
            'description' => 'Helpful tutorial',
        ]);
        $resource->topics()->attach($topic->id);

        $service = new RecommendationService();
        $recommendations = $service->getRecommendations($user, 5);

        $this->assertNotEmpty($recommendations);
        $this->assertEquals($resource->id, $recommendations->first()->id);
        $this->assertNotEmpty($recommendations->first()->recommendation_reason);
    }
}
