<?php

namespace IamApi\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use IamApi\Http\Middleware\SecurityHeaders;
use IamApi\Http\Middleware\CaptchaOrThrottle;
use IamApi\Http\Middleware\CheckPasswordExpiry;
use IamApi\Http\Middleware\EnsureSuperAdmin;
use IamApi\Http\Middleware\EnsureOwnerOrSuperAdmin;
use IamApi\Http\Middleware\SetOrganizationContext;
use IamApi\Models\Organization;
use IamApi\Models\OrganizationInvitation;
use IamApi\Models\Department;
use IamApi\Models\User;
use IamApi\Repositories\RepositoryServiceProvider;

class IamApiServiceProvider extends ServiceProvider
{
    /**
     * Event listener mappings for the IAM package.
     */
    protected $listen = [
        \IamApi\Events\OrganizationCreated::class => [
            \IamApi\Listeners\CreateDefaultOrganizationRoles::class,
        ],
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration files
        $this->mergeConfigFrom(__DIR__ . '/../Config/iam.php',         'iam');
        $this->mergeConfigFrom(__DIR__ . '/../Config/jwt.php',         'jwt');
        $this->mergeConfigFrom(__DIR__ . '/../Config/permission.php',  'permission');
        $this->mergeConfigFrom(__DIR__ . '/../Config/activitylog.php', 'activitylog');
        $this->mergeConfigFrom(__DIR__ . '/../Config/security.php',    'security');
        $this->mergeConfigFrom(__DIR__ . '/../Config/captcha.php',     'captcha');

        // Merge Kavenegar config defaults (API key + template name)
        // The host app's config/kavenegar.php (published by kavenegar/laravel) takes precedence.
        $this->mergeConfigFrom(__DIR__ . '/../Config/kavenegar.php', 'kavenegar');

        // Register repository service provider
        $this->app->register(RepositoryServiceProvider::class);

        // Register external services provider (Kavenegar SMS facade binding, HTTP macros)
        $this->app->register(ExternalServicesProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register event listeners
        $this->registerEventListeners();
        
        // Configure Route Model Binding to use UUID
        $this->configureRouteModelBindings();
        
        // Load blade views with namespace "iam::" (e.g. iam::mail.otp)
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'iam');

        // Publish configuration files
        $this->publishes([
            __DIR__ . '/../Config/iam.php'         => config_path('iam.php'),
            __DIR__ . '/../Config/jwt.php'         => config_path('jwt.php'),
            __DIR__ . '/../Config/permission.php'  => config_path('permission.php'),
            __DIR__ . '/../Config/activitylog.php' => config_path('activitylog.php'),
            __DIR__ . '/../Config/security.php'    => config_path('security.php'),
            __DIR__ . '/../Config/captcha.php'     => config_path('captcha.php'),
            __DIR__ . '/../Config/kavenegar.php'   => config_path('kavenegar.php'),
        ], 'iam-config');

        // Publish email views (allows host app to override templates)
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/iam'),
        ], 'iam-views');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../Database/migrations' => database_path('migrations'),
        ], 'iam-migrations');

        // Publish seeders
        $this->publishes([
            __DIR__ . '/../Database/seeders' => database_path('seeders'),
        ], 'iam-seeders');

        // Load migrations automatically
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        // Register security headers middleware for API routes
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('api', SecurityHeaders::class);
        
        // Register custom middleware aliases
        $this->registerMiddlewareAliases($router);
    }
    
    /**
     * Register event listeners for the IAM package.
     */
    protected function registerEventListeners(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                \Event::listen($event, $listener);
            }
        }
    }
    
    /**
     * Register middleware aliases for the IAM package.
     */
    protected function registerMiddlewareAliases(Router $router): void
    {
        // CAPTCHA and throttle middleware
        $router->aliasMiddleware('captcha.throttle', CaptchaOrThrottle::class);
        
        // Password expiry check middleware
        $router->aliasMiddleware('password.expiry', CheckPasswordExpiry::class);
        
        // Super Admin only middleware
        $router->aliasMiddleware('super.admin', EnsureSuperAdmin::class);
        
        // Owner or Super Admin middleware
        $router->aliasMiddleware('owner.or.super', EnsureOwnerOrSuperAdmin::class);
        
        // Organization context middleware
        $router->aliasMiddleware('org.context', SetOrganizationContext::class);
    }
    
    /**
     * Configure Route Model Bindings to use UUID for IAM models.
     * 
     * This ensures that when models are resolved from route parameters,
     * they are looked up by their UUID column instead of the default ID.
     */
    protected function configureRouteModelBindings(): void
    {
        // Bind 'org' parameter to Organization model using UUID
        Route::bind('org', function (string $value) {
            return Organization::where('uuid', $value)->firstOrFail();
        });
        
        // Bind 'user' parameter to User model using UUID
        Route::bind('user', function (string $value) {
            return User::where('uuid', $value)->firstOrFail();
        });
        
        // Bind 'department' parameter to Department model using UUID
        Route::bind('department', function (string $value) {
            return Department::where('uuid', $value)->firstOrFail();
        });
        
        // Bind 'invite' parameter to OrganizationInvitation model using UUID
        Route::bind('invite', function (string $value) {
            return OrganizationInvitation::where('uuid', $value)->firstOrFail();
        });
    }
}
