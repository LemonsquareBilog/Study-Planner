<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            Vite::useHotFile(storage_path('framework/vite-production.hot'));
        }

        Request::macro('isStudent', fn () => $this->user()?->role === 'student');
    }
}
