<?php

use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

  // Public read-only routes with higher rate limit (300 req/min)
  // These are used heavily on initial page load
  Route::middleware(['throttle:api.public'])->group(function () {
    Route::get('/sliders', [\App\Http\Controllers\API\SliderController::class, 'index']);
    Route::get('/site-features', [\App\Http\Controllers\API\SiteFeatureController::class, 'index']);
    Route::get('/promo-banners', [\App\Http\Controllers\API\PromoBannerController::class, 'index']);
    Route::get('/site-settings', [\App\Http\Controllers\API\SiteSettingController::class, 'index']);
    Route::get('/nav-links/{group}', [\App\Http\Controllers\API\NavLinkController::class, 'byGroup']);

    Route::prefix('products')->group(function () {
      Route::get('/', [\App\Http\Controllers\API\ProductController::class, 'index']);
      Route::get('/search', [\App\Http\Controllers\API\ProductController::class, 'index']);
      Route::get('/autocomplete', [\App\Http\Controllers\API\ProductController::class, 'autocomplete']);
      Route::get('/{product}/similar', [\App\Http\Controllers\API\ProductController::class, 'similar']);
      Route::get('/{product}/reviews', [\App\Http\Controllers\API\ProductReviewController::class, 'index']);
      Route::get('/{product}', [\App\Http\Controllers\API\ProductController::class, 'show']);
    });

    Route::prefix('categories')->group(function () {
      Route::get('/', [\App\Http\Controllers\API\CategoryController::class, 'index']);
      Route::get('/search', [\App\Http\Controllers\API\CategoryController::class, 'index']);
      Route::get('/hierarchical', [\App\Http\Controllers\API\CategoryController::class, 'hierarchical']);
      Route::get('/top-subcategories', [\App\Http\Controllers\API\CategoryController::class, 'topSubcategories']);
      Route::get('/{category}', [\App\Http\Controllers\API\CategoryController::class, 'show']);
    });

    // Public order tracking - no authentication required
    Route::post('/orders/track', [\App\Http\Controllers\API\OrderTrackingController::class, 'track']);
  });

  Route::post('/newsletter/subscribe', [\App\Http\Controllers\API\NewsletterController::class, 'subscribe']);

  Route::get('/faqs', [\App\Http\Controllers\API\FaqController::class, 'index']);

  Route::prefix('tax-classes')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\TaxClassController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\API\TaxClassController::class, 'index']);
    Route::get('/{taxClass}', [\App\Http\Controllers\API\TaxClassController::class, 'show']);
  });

  Route::prefix('manufacturers')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\ManufacturerController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\API\ManufacturerController::class, 'index']);
    Route::get('/{manufacturer}', [\App\Http\Controllers\API\ManufacturerController::class, 'show']);
  });

  Route::prefix('vendors')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\VendorController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\API\VendorController::class, 'index']);
    Route::get('/{vendor}', [\App\Http\Controllers\API\VendorController::class, 'show']);
  });

  Route::prefix('options')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\OptionController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\API\OptionController::class, 'index']);
    Route::get('/{option}', [\App\Http\Controllers\API\OptionController::class, 'show']);
  });

  Route::prefix('option-values')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\OptionValueController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\API\OptionValueController::class, 'index']);
    Route::get('/{optionValue}', [\App\Http\Controllers\API\OptionValueController::class, 'show']);
  });

  Route::prefix('customer-groups')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\CustomerGroupController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\API\CustomerGroupController::class, 'index']);
    Route::get('/{customerGroup}', [\App\Http\Controllers\API\CustomerGroupController::class, 'show']);
  });

  Route::prefix('listings')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\ListingController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\API\ListingController::class, 'index']);
    Route::get('/{listing}', [\App\Http\Controllers\API\ListingController::class, 'show']);
  });
});

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
  // Product reviews - accessible to authenticated customers only (not super admin)
  Route::middleware(['block.super.admin'])->post('/products/{product}/reviews', [\App\Http\Controllers\API\ProductReviewController::class, 'store']);

  // Product CRUD - admin only
  Route::middleware(['admin'])->prefix('products')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\ProductController::class, 'store']);
    Route::put('/{product}', [\App\Http\Controllers\API\ProductController::class, 'update']);
    Route::delete('/{product}', [\App\Http\Controllers\API\ProductController::class, 'destroy']);
  });


  // Admin-only routes for managing tax classes
  Route::middleware(['admin'])->prefix('tax-classes')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\TaxClassController::class, 'store']);
    Route::put('/{taxClass}', [\App\Http\Controllers\API\TaxClassController::class, 'update']);
    Route::delete('/{taxClass}', [\App\Http\Controllers\API\TaxClassController::class, 'destroy']);
  });

  // Admin-only routes for managing manufacturers
  Route::middleware(['admin'])->prefix('manufacturers')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\ManufacturerController::class, 'store']);
    Route::put('/{manufacturer}', [\App\Http\Controllers\API\ManufacturerController::class, 'update']);
    Route::delete('/{manufacturer}', [\App\Http\Controllers\API\ManufacturerController::class, 'destroy']);
  });

  // Admin-only routes for managing vendors
  Route::middleware(['admin'])->prefix('vendors')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\VendorController::class, 'store']);
    Route::put('/{vendor}', [\App\Http\Controllers\API\VendorController::class, 'update']);
    Route::delete('/{vendor}', [\App\Http\Controllers\API\VendorController::class, 'destroy']);
  });

  // Admin-only routes for managing product options
  Route::middleware(['admin'])->prefix('options')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\OptionController::class, 'store']);
    Route::put('/{option}', [\App\Http\Controllers\API\OptionController::class, 'update']);
    Route::delete('/{option}', [\App\Http\Controllers\API\OptionController::class, 'destroy']);
  });

  // Admin-only routes for managing option values
  Route::middleware(['admin'])->prefix('option-values')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\OptionValueController::class, 'store']);
    Route::put('/{optionValue}', [\App\Http\Controllers\API\OptionValueController::class, 'update']);
    Route::delete('/{optionValue}', [\App\Http\Controllers\API\OptionValueController::class, 'destroy']);
  });

  // Admin-only routes for managing customer groups
  Route::middleware(['admin'])->prefix('customer-groups')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\CustomerGroupController::class, 'store']);
    Route::put('/{customerGroup}', [\App\Http\Controllers\API\CustomerGroupController::class, 'update']);
    Route::delete('/{customerGroup}', [\App\Http\Controllers\API\CustomerGroupController::class, 'destroy']);
  });

  // Admin-only routes for managing listings
  Route::middleware(['admin'])->prefix('listings')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\ListingController::class, 'store']);
    Route::put('/{listing}', [\App\Http\Controllers\API\ListingController::class, 'update']);
    Route::delete('/{listing}', [\App\Http\Controllers\API\ListingController::class, 'destroy']);
  });
});

// Public payment methods list (for checkout display)
Route::get('/v1/payment/methods', [\App\Http\Controllers\API\User\PaymentController::class, 'methods']);


// Zarinpal callback — must be accessible without auth (redirected from Zarinpal)
Route::get('/v1/payment/zarinpal/callback', [\App\Http\Controllers\API\User\PaymentController::class, 'zarinpalCallback']);

// User Panel Routes (authenticated users only — super admin is blocked)
Route::middleware(['auth:api', 'block.super.admin'])->prefix('v1/user')->name('user.')->group(function () {
  // Profile
  Route::get('/profile', [\App\Http\Controllers\API\User\ProfileController::class, 'show']);
  Route::put('/profile', [\App\Http\Controllers\API\User\ProfileController::class, 'update']);
  Route::post('/profile/change-password', [\App\Http\Controllers\API\User\ProfileController::class, 'changePassword']);
  
  // Cart
  Route::get('/cart', [\App\Http\Controllers\API\User\CartController::class, 'index']);
  Route::post('/cart', [\App\Http\Controllers\API\User\CartController::class, 'store']);
  Route::put('/cart/{cartItem}', [\App\Http\Controllers\API\User\CartController::class, 'update']);
  Route::delete('/cart/{cartItem}', [\App\Http\Controllers\API\User\CartController::class, 'destroy']);
  Route::post('/cart/clear', [\App\Http\Controllers\API\User\CartController::class, 'clear']);
  
  // Orders
  Route::get('/orders', [\App\Http\Controllers\API\User\OrderController::class, 'index']);
  Route::get('/orders/{order}', [\App\Http\Controllers\API\User\OrderController::class, 'show']);
  Route::post('/orders', [\App\Http\Controllers\API\User\OrderController::class, 'store']);
  
  // Addresses
  Route::get('/addresses', [\App\Http\Controllers\API\User\AddressController::class, 'index']);
  Route::post('/addresses', [\App\Http\Controllers\API\User\AddressController::class, 'store']);
  Route::put('/addresses/{address}', [\App\Http\Controllers\API\User\AddressController::class, 'update']);
  Route::delete('/addresses/{address}', [\App\Http\Controllers\API\User\AddressController::class, 'destroy']);
  
  // Wishlist
  Route::get('/wishlist', [\App\Http\Controllers\API\User\WishlistController::class, 'index']);
  Route::post('/wishlist', [\App\Http\Controllers\API\User\WishlistController::class, 'store']);
  Route::delete('/wishlist/{wishlist}', [\App\Http\Controllers\API\User\WishlistController::class, 'destroy']);

  // Payments
  Route::post('/payment/initiate', [\App\Http\Controllers\API\User\PaymentController::class, 'initiate']);
  Route::post('/payment/transactions/{transaction}/receipt', [\App\Http\Controllers\API\User\PaymentController::class, 'uploadReceipt']);
});

// Admin Panel Routes (admin users only)
Route::middleware(['auth:api', 'admin'])->prefix('v1/admin')->name('admin.')->group(function () {
  // Dashboard
  Route::get('/dashboard', [\App\Http\Controllers\API\Admin\DashboardController::class, 'index']);
  
  // Orders Management
  Route::get('/orders', [\App\Http\Controllers\API\Admin\OrderController::class, 'index']);
  Route::get('/orders/{order}', [\App\Http\Controllers\API\Admin\OrderController::class, 'show']);
  Route::patch('/orders/{order}/status', [\App\Http\Controllers\API\Admin\OrderController::class, 'updateStatus']);
  Route::patch('/orders/{order}/payment-status', [\App\Http\Controllers\API\Admin\OrderController::class, 'updatePaymentStatus']);
  Route::patch('/orders/{order}/notes', [\App\Http\Controllers\API\Admin\OrderController::class, 'updateNotes']);
  Route::patch('/orders/{order}/shipping-code', [\App\Http\Controllers\API\Admin\OrderController::class, 'setShippingCode']);
  Route::get('/orders/{order}/histories', [\App\Http\Controllers\API\Admin\OrderController::class, 'histories']);
  Route::post('/orders/{order}/send-notification', [\App\Http\Controllers\API\Admin\OrderController::class, 'sendNotification']);
  
  // Coupons Management
  Route::get('/coupons', [\App\Http\Controllers\API\Admin\CouponController::class, 'index']);
  Route::post('/coupons', [\App\Http\Controllers\API\Admin\CouponController::class, 'store']);
  Route::get('/coupons/{coupon}', [\App\Http\Controllers\API\Admin\CouponController::class, 'show']);
  Route::put('/coupons/{coupon}', [\App\Http\Controllers\API\Admin\CouponController::class, 'update']);
  Route::delete('/coupons/{coupon}', [\App\Http\Controllers\API\Admin\CouponController::class, 'destroy']);

  // Sliders Management
  Route::get('/sliders', [\App\Http\Controllers\API\Admin\SliderController::class, 'index']);
  Route::post('/sliders', [\App\Http\Controllers\API\Admin\SliderController::class, 'store']);
  Route::get('/sliders/{slider}', [\App\Http\Controllers\API\Admin\SliderController::class, 'show']);
  Route::post('/sliders/{slider}', [\App\Http\Controllers\API\Admin\SliderController::class, 'update']);
  Route::delete('/sliders/{slider}', [\App\Http\Controllers\API\Admin\SliderController::class, 'destroy']);
  Route::post('/sliders-reorder', [\App\Http\Controllers\API\Admin\SliderController::class, 'reorder']);

  // Site Features Management
  Route::get('/site-features', [\App\Http\Controllers\API\Admin\SiteFeatureController::class, 'index']);
  Route::post('/site-features', [\App\Http\Controllers\API\Admin\SiteFeatureController::class, 'store']);
  Route::get('/site-features/{siteFeature}', [\App\Http\Controllers\API\Admin\SiteFeatureController::class, 'show']);
  Route::put('/site-features/{siteFeature}', [\App\Http\Controllers\API\Admin\SiteFeatureController::class, 'update']);
  Route::delete('/site-features/{siteFeature}', [\App\Http\Controllers\API\Admin\SiteFeatureController::class, 'destroy']);

  // Promo Banners Management
  Route::get('/promo-banners', [\App\Http\Controllers\API\Admin\PromoBannerController::class, 'index']);
  Route::post('/promo-banners', [\App\Http\Controllers\API\Admin\PromoBannerController::class, 'store']);
  Route::get('/promo-banners/{promoBanner}', [\App\Http\Controllers\API\Admin\PromoBannerController::class, 'show']);
  Route::post('/promo-banners/{promoBanner}', [\App\Http\Controllers\API\Admin\PromoBannerController::class, 'update']);
  Route::delete('/promo-banners/{promoBanner}', [\App\Http\Controllers\API\Admin\PromoBannerController::class, 'destroy']);

  // Site Settings Management
  Route::get('/site-settings', [\App\Http\Controllers\API\Admin\SiteSettingController::class, 'index']);
  Route::put('/site-settings', [\App\Http\Controllers\API\Admin\SiteSettingController::class, 'update']);
  Route::post('/site-settings/upload-file', [\App\Http\Controllers\API\Admin\SiteSettingController::class, 'uploadFile']);

  // Nav Links Management
  Route::get('/nav-links', [\App\Http\Controllers\API\Admin\NavLinkController::class, 'index']);
  Route::post('/nav-links', [\App\Http\Controllers\API\Admin\NavLinkController::class, 'store']);
  Route::get('/nav-links/{navLink}', [\App\Http\Controllers\API\Admin\NavLinkController::class, 'show']);
  Route::put('/nav-links/{navLink}', [\App\Http\Controllers\API\Admin\NavLinkController::class, 'update']);
  Route::delete('/nav-links/{navLink}', [\App\Http\Controllers\API\Admin\NavLinkController::class, 'destroy']);

  // Card-to-Card Receipt Confirmation
  Route::post('/transactions/{transaction}/confirm-receipt', [\App\Http\Controllers\API\Admin\AdminPaymentController::class, 'confirmReceipt']);
  Route::post('/transactions/{transaction}/reject-receipt', [\App\Http\Controllers\API\Admin\AdminPaymentController::class, 'rejectReceipt']);

  // Payment Methods Management
  Route::get('/payment-methods', [\App\Http\Controllers\API\Admin\PaymentMethodController::class, 'index']);
  Route::get('/payment-methods/{paymentMethod}', [\App\Http\Controllers\API\Admin\PaymentMethodController::class, 'show']);
  Route::put('/payment-methods/{paymentMethod}', [\App\Http\Controllers\API\Admin\PaymentMethodController::class, 'update']);

  // Order Statuses Management
  Route::get('/order-statuses', [\App\Http\Controllers\API\Admin\OrderStatusController::class, 'index']);
  Route::post('/order-statuses', [\App\Http\Controllers\API\Admin\OrderStatusController::class, 'store']);
  Route::put('/order-statuses/{orderStatus}', [\App\Http\Controllers\API\Admin\OrderStatusController::class, 'update']);
  Route::delete('/order-statuses/{orderStatus}', [\App\Http\Controllers\API\Admin\OrderStatusController::class, 'destroy']);

  // Reviews Management
  Route::get('/reviews', [\App\Http\Controllers\API\Admin\ProductReviewController::class, 'index']);
  Route::post('/reviews/{review}/approve', [\App\Http\Controllers\API\Admin\ProductReviewController::class, 'approve']);
  Route::post('/reviews/{review}/reject', [\App\Http\Controllers\API\Admin\ProductReviewController::class, 'reject']);
  Route::delete('/reviews/{review}', [\App\Http\Controllers\API\Admin\ProductReviewController::class, 'destroy']);

  // Products Admin — detail with ALL variants and available options
  Route::get('/products/available-options', [\App\Http\Controllers\API\Admin\AdminProductController::class, 'availableOptions']);
  Route::get('/products/{product}', [\App\Http\Controllers\API\Admin\AdminProductController::class, 'show']);

  // Categories Management
  Route::prefix('categories')->group(function () {
    Route::post('/reorder', [\App\Http\Controllers\API\CategoryController::class, 'reorder']);
    Route::post('/', [\App\Http\Controllers\API\CategoryController::class, 'store']);
    Route::put('/{category}', [\App\Http\Controllers\API\CategoryController::class, 'update']);
    Route::delete('/{category}', [\App\Http\Controllers\API\CategoryController::class, 'destroy']);
  });

  // FAQs Management
  Route::get('/faqs', [\App\Http\Controllers\API\Admin\FaqController::class, 'index']);
  Route::post('/faqs', [\App\Http\Controllers\API\Admin\FaqController::class, 'store']);
  Route::get('/faqs/{faq}', [\App\Http\Controllers\API\Admin\FaqController::class, 'show']);
  Route::put('/faqs/{faq}', [\App\Http\Controllers\API\Admin\FaqController::class, 'update']);
  Route::delete('/faqs/{faq}', [\App\Http\Controllers\API\Admin\FaqController::class, 'destroy']);

  // IAM User Management
  Route::get('/users', [\App\Http\Controllers\API\Admin\UserController::class, 'index']);
  Route::get('/users/{id}', [\App\Http\Controllers\API\Admin\UserController::class, 'show']);
  Route::post('/users/{id}/lock', [\App\Http\Controllers\API\Admin\UserController::class, 'lock']);
  Route::post('/users/{id}/unlock', [\App\Http\Controllers\API\Admin\UserController::class, 'unlock']);
});
