<?php

namespace App\Providers;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Policies\CouponPolicy;
use App\Policies\ProductPolicy;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\CustomerGroupRepositoryInterface;
use App\Repositories\Contracts\FaqRepositoryInterface;
use App\Repositories\Contracts\ListingRepositoryInterface;
use App\Repositories\Contracts\ManufacturerRepositoryInterface;
use App\Repositories\Contracts\NavLinkRepositoryInterface;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;
use App\Repositories\Contracts\OptionRepositoryInterface;
use App\Repositories\Contracts\OptionValueRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProductReviewRepositoryInterface;
use App\Repositories\Contracts\PromoBannerRepositoryInterface;
use App\Repositories\Contracts\SiteFeatureRepositoryInterface;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;
use App\Repositories\Contracts\SliderRepositoryInterface;
use App\Repositories\Contracts\TaxClassRepositoryInterface;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Repositories\Eloquents\CartRepository;
use App\Repositories\Eloquents\CategoryRepository;
use App\Repositories\Eloquents\CustomerGroupRepository;
use App\Repositories\Eloquents\FaqRepository;
use App\Repositories\Eloquents\ListingRepository;
use App\Repositories\Eloquents\ManufacturerRepository;
use App\Repositories\Eloquents\NavLinkRepository;
use App\Repositories\Eloquents\NewsletterSubscriberRepository;
use App\Repositories\Eloquents\OptionRepository;
use App\Repositories\Eloquents\OptionValueRepository;
use App\Repositories\Eloquents\OrderRepository;
use App\Repositories\Eloquents\ProductRepository;
use App\Repositories\Eloquents\ProductReviewRepository;
use App\Repositories\Eloquents\PromoBannerRepository;
use App\Repositories\Eloquents\SiteFeatureRepository;
use App\Repositories\Eloquents\SiteSettingRepository;
use App\Repositories\Eloquents\SliderRepository;
use App\Repositories\Eloquents\TaxClassRepository;
use App\Repositories\Eloquents\UserRepository;
use App\Repositories\Eloquents\VendorRepository;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        $this->app->bind(FaqRepositoryInterface::class, FaqRepository::class);
        $this->app->bind(ProductReviewRepositoryInterface::class, ProductReviewRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override the IAM package's UserRepository binding to use App\Models\User.
        // This must be in boot() (not register()) to run AFTER the IAM package's
        // RepositoryServiceProvider which binds EloquentUserRepository → IamApi\Models\User.
        // Using bind() (not singleton) keeps it consistent with all other repositories.
        $this->app->bind(UserRepositoryInterface::class, function () {
            return new UserRepository(new User);
        });

        // Register policies
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);

        // Configure rate limiters
        $this->configureRateLimiting();

        // Load IAM routes with 'api' prefix
        $this->loadIamRoutes();
    }

    /**
     * Load IAM service routes under the correct 'api/' prefix with api middleware group.
     *
     * The IAM package's ServiceProvider normally loads its own routes without the 'api/'
     * prefix and without the api middleware group (bypassing rate limiting).
     * We disable that auto-loading via config('iam.disable_auto_routes') = true and
     * register the routes here with proper prefix and middleware instead.
     *
     * Result: routes are accessible at /api/v1/auth/... with full api middleware applied.
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
