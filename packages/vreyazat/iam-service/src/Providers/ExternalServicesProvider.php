<?php

namespace IamApi\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

class ExternalServicesProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register SMS Service (Kavenegar only)
        $this->app->singleton('sms', function ($app) {
            return app('kavenegar');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure HTTP client defaults for external services
        Http::macro('sms', static function () {
            return Http::timeout(30)
                ->retry(3, 1000)
                ->withHeaders([
                    'User-Agent' => 'IAM-SERVICE/1.0',
                    'Accept' => 'application/json',
                ]);
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'sms', // Kavenegar SMS service
        ];
    }
}
