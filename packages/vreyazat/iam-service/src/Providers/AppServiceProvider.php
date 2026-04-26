<?php

namespace IamApi\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Application-level configurations can be added here
        // Rate limiting is handled by CaptchaOrThrottle middleware
    }
}
