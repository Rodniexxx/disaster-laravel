<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        // Use the MANGNO-styled pagination views everywhere.
        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');
    }

    /**
     * Rate limiters used by the Unity integration API.
     */
    private function configureRateLimiting(): void
    {
        // Authentication/registration attempts are limited per client IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->ip()));

        RateLimiter::for('api-login', fn (Request $request) => Limit::perMinute(20)
            ->by($request->ip()));
    }
}
