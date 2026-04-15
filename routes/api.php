<?php

use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
  Route::get('/sliders', [\App\Http\Controllers\API\SliderController::class, 'index']);

  Route::prefix('products')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\ProductController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\API\ProductController::class, 'index']);
    Route::get('/{product}', [\App\Http\Controllers\API\ProductController::class, 'show']);
  });

  Route::prefix('categories')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\CategoryController::class, 'index']);
    Route::get('/search', [\App\Http\Controllers\API\CategoryController::class, 'index']);
    Route::get('/{category}', [\App\Http\Controllers\API\CategoryController::class, 'show']);
  });

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
  Route::prefix('products')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\ProductController::class, 'store']);
    Route::put('/{product}', [\App\Http\Controllers\API\ProductController::class, 'update']);
    Route::delete('/{product}', [\App\Http\Controllers\API\ProductController::class, 'destroy']);
  });

  Route::prefix('categories')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\CategoryController::class, 'store']);
    Route::put('/{category}', [\App\Http\Controllers\API\CategoryController::class, 'update']);
    Route::delete('/{category}', [\App\Http\Controllers\API\CategoryController::class, 'destroy']);
  });

  Route::prefix('tax-classes')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\TaxClassController::class, 'store']);
    Route::put('/{taxClass}', [\App\Http\Controllers\API\TaxClassController::class, 'update']);
    Route::delete('/{taxClass}', [\App\Http\Controllers\API\TaxClassController::class, 'destroy']);
  });

  Route::prefix('manufacturers')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\ManufacturerController::class, 'store']);
    Route::put('/{manufacturer}', [\App\Http\Controllers\API\ManufacturerController::class, 'update']);
    Route::delete('/{manufacturer}', [\App\Http\Controllers\API\ManufacturerController::class, 'destroy']);
  });

  Route::prefix('vendors')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\VendorController::class, 'store']);
    Route::put('/{vendor}', [\App\Http\Controllers\API\VendorController::class, 'update']);
    Route::delete('/{vendor}', [\App\Http\Controllers\API\VendorController::class, 'destroy']);
  });

  Route::prefix('options')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\OptionController::class, 'store']);
    Route::put('/{option}', [\App\Http\Controllers\API\OptionController::class, 'update']);
    Route::delete('/{option}', [\App\Http\Controllers\API\OptionController::class, 'destroy']);
  });

  Route::prefix('option-values')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\OptionValueController::class, 'store']);
    Route::put('/{optionValue}', [\App\Http\Controllers\API\OptionValueController::class, 'update']);
    Route::delete('/{optionValue}', [\App\Http\Controllers\API\OptionValueController::class, 'destroy']);
  });

  Route::prefix('customer-groups')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\CustomerGroupController::class, 'store']);
    Route::put('/{customerGroup}', [\App\Http\Controllers\API\CustomerGroupController::class, 'update']);
    Route::delete('/{customerGroup}', [\App\Http\Controllers\API\CustomerGroupController::class, 'destroy']);
  });

  Route::prefix('listings')->group(function () {
    Route::post('/', [\App\Http\Controllers\API\ListingController::class, 'store']);
    Route::put('/{listing}', [\App\Http\Controllers\API\ListingController::class, 'update']);
    Route::delete('/{listing}', [\App\Http\Controllers\API\ListingController::class, 'destroy']);
  });
});

// User Panel Routes (authenticated users)
Route::middleware(['auth:api'])->prefix('v1/user')->name('user.')->group(function () {
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
});
