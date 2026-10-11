<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Providers\AppServiceProvider;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('type="module"', false);
        $response->assertSee('id="app"', false);
    }

    public function test_production_loads_bundled_assets_even_when_a_development_hot_file_exists(): void
    {
        $this->app['env'] = 'production';
        $this->app->getProvider(AppServiceProvider::class)->boot();

        $this->get('/login')
            ->assertOk()
            ->assertSee('/build/assets/', false)
            ->assertDontSee('@vite/client', false);
    }
}
