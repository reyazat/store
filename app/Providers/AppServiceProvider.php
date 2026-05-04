<?php

namespace App\Providers;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Eloquents\CategoryRepository;
use App\Repositories\Contracts\CustomerGroupRepositoryInterface;
use App\Repositories\Eloquents\CustomerGroupRepository;
use App\Repositories\Contracts\ManufacturerRepositoryInterface;
use App\Repositories\Eloquents\ManufacturerRepository;
use App\Repositories\Contracts\OptionRepositoryInterface;
use App\Repositories\Eloquents\OptionRepository;
use App\Repositories\Contracts\OptionValueRepositoryInterface;
use App\Repositories\Eloquents\OptionValueRepository;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquents\ProductRepository;
use App\Repositories\Contracts\TaxClassRepositoryInterface;
use App\Repositories\Eloquents\TaxClassRepository;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Repositories\Eloquents\VendorRepository;
use App\Repositories\Contracts\ListingRepositoryInterface;
use App\Repositories\Eloquents\ListingRepository;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Eloquents\CartRepository;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Eloquents\OrderRepository;
use App\Repositories\Contracts\SliderRepositoryInterface;
use App\Repositories\Eloquents\SliderRepository;
use App\Repositories\Contracts\SiteFeatureRepositoryInterface;
use App\Repositories\Eloquents\SiteFeatureRepository;
use App\Repositories\Contracts\PromoBannerRepositoryInterface;
use App\Repositories\Eloquents\PromoBannerRepository;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;
use App\Repositories\Eloquents\NewsletterSubscriberRepository;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Repositories\Eloquents\SiteSettingRepository;
use App\Repositories\Contracts\NavLinkRepositoryInterface;
use App\Repositories\Eloquents\NavLinkRepository;
use App\Repositories\Eloquents\UserRepository;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(CustomerGroupRepositoryInterface::class, CustomerGroupRepository::class);
        $this->app->bind(TaxClassRepositoryInterface::class, TaxClassRepository::class);
        $this->app->bind(ManufacturerRepositoryInterface::class, ManufacturerRepository::class);
        $this->app->bind(VendorRepositoryInterface::class, VendorRepository::class);
        $this->app->bind(OptionRepositoryInterface::class, OptionRepository::class);
        $this->app->bind(OptionValueRepositoryInterface::class, OptionValueRepository::class);
        $this->app->bind(ListingRepositoryInterface::class, ListingRepository::class);
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(SliderRepositoryInterface::class, SliderRepository::class);
        $this->app->bind(SiteFeatureRepositoryInterface::class, SiteFeatureRepository::class);
        $this->app->bind(PromoBannerRepositoryInterface::class, PromoBannerRepository::class);
        $this->app->bind(NewsletterSubscriberRepositoryInterface::class, NewsletterSubscriberRepository::class);
        $this->app->bind(SiteSettingRepositoryInterface::class, SiteSettingRepository::class);
        $this->app->bind(NavLinkRepositoryInterface::class, NavLinkRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override vendor's UserRepository to use App\Models\User with guardName() method
        // Must be in boot() to run after vendor's RepositoryServiceProvider
        $this->app->singleton(UserRepositoryInterface::class, function ($app) {
            return new UserRepository(new \App\Models\User());
        });

        // Configure rate limiters
        $this->configureRateLimiting();

        // Load IAM routes with 'api' prefix
        $this->loadIamRoutes();
    }

    /**
     * Load IAM service routes with proper API prefix.
     *
     * IAM service loads routes without 'api' prefix, so we manually
     * load them again with the correct prefix. The duplicate routes
     * without prefix will be ignored by Laravel's router.
     */
    protected function loadIamRoutes(): void
    {
        $iamRoutesPath = base_path('vendor/vreyazat/iam-service/src/Routes/api.php');

        if (file_exists($iamRoutesPath)) {
            Route::middleware('api')
                ->prefix('api')
                ->name('api.')
                ->group($iamRoutesPath);
        }
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // API rate limiter - 120 requests per minute per user/IP
        // Increased for e-commerce initial page load (multiple concurrent requests)
        RateLimiter::for('api', static function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Public API rate limiter - higher limit for read-only public routes
        // Used by homepage, product listings, etc. that make many concurrent requests
        RateLimiter::for('api.public', static function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        // Global rate limiter - stricter limit for unauthenticated users
        RateLimiter::for('global', static function (Request $request) {
            return $request->user()
                ? Limit::perMinute(200)->by($request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });

        // Sync rate limiter - very strict to prevent abuse of external API
        RateLimiter::for('sync', static function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Export rate limiter - prevent excessive file generation
        RateLimiter::for('export', static function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
