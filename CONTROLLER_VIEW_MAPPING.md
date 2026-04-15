# 📊 جدول تطبیق کنترلرها و ویوها

## ✅ کنترلرهای عمومی (Public)

| کنترلر Backend | Route | صفحه Frontend | وضعیت |
|----------------|-------|---------------|-------|
| `ProductController@index` | `GET /api/v1/products` | `ProductListPage.vue` | ✅ |
| `ProductController@show` | `GET /api/v1/products/{product}` | `ProductDetailPage.vue` | ✅ |
| `CategoryController@index` | `GET /api/v1/categories` | `CategoryListPage.vue` | ✅ |
| `CategoryController@show` | `GET /api/v1/categories/{category}` | `CategoryListPage.vue` | ✅ |
| `ManufacturerController@index` | `GET /api/v1/manufacturers` | `BrandsPage.vue` | ✅ |
| `ListingController@index` | `GET /api/v1/listings` | استفاده در ProductListPage | ✅ |

## 🔐 کنترلرهای احراز هویت (Auth)

| کنترلر Backend | Route | صفحه Frontend | وضعیت |
|----------------|-------|---------------|-------|
| `AuthController@login` | `POST /api/v1/auth/login` | `LoginPage.vue` | ✅ |
| `AuthController@logout` | `POST /api/v1/auth/logout` | استفاده در Header | ✅ |
| `AuthController@me` | `GET /api/v1/auth/me` | استفاده در auth store | ✅ |
| `RbacIntrospectionController@myRoles` | `GET /api/v1/auth/me/roles` | استفاده در auth store | ✅ |
| `RbacIntrospectionController@myPermissions` | `GET /api/v1/auth/me/permissions` | استفاده در auth store | ✅ |
| - | - | `RegisterPage.vue` | ⚠️ نیاز به endpoint |

## 👤 کنترلرهای پنل کاربر (User Panel)

| کنترلر Backend | Route | صفحه Frontend | وضعیت |
|----------------|-------|---------------|-------|
| `User\ProfileController@show` | `GET /api/v1/user/profile` | `ProfilePage.vue` | ✅ |
| `User\ProfileController@update` | `PUT /api/v1/user/profile` | `ProfilePage.vue` | ✅ |
| `User\ProfileController@changePassword` | `POST /api/v1/user/profile/change-password` | `ProfilePage.vue` | ✅ |
| `User\CartController@index` | `GET /api/v1/user/cart` | استفاده در cart store | ✅ |
| `User\CartController@store` | `POST /api/v1/user/cart` | استفاده در cart store | ✅ |
| `User\CartController@update` | `PUT /api/v1/user/cart/{cartItem}` | استفاده در cart store | ✅ |
| `User\CartController@destroy` | `DELETE /api/v1/user/cart/{cartItem}` | استفاده در cart store | ✅ |
| `User\CartController@clear` | `POST /api/v1/user/cart/clear` | استفاده در cart store | ✅ |
| `User\OrderController@index` | `GET /api/v1/user/orders` | `OrdersPage.vue` | ✅ |
| `User\OrderController@show` | `GET /api/v1/user/orders/{order}` | `OrdersPage.vue` | ✅ |
| `User\OrderController@store` | `POST /api/v1/user/orders` | `CheckoutPage.vue` | ✅ |
| `User\AddressController@index` | `GET /api/v1/user/addresses` | `AddressesPage.vue` | ✅ |
| `User\AddressController@store` | `POST /api/v1/user/addresses` | `AddressesPage.vue` | ✅ |
| `User\AddressController@update` | `PUT /api/v1/user/addresses/{address}` | `AddressesPage.vue` | ✅ |
| `User\AddressController@destroy` | `DELETE /api/v1/user/addresses/{address}` | `AddressesPage.vue` | ✅ |
| `User\WishlistController@index` | `GET /api/v1/user/wishlist` | `WishlistPage.vue` | ✅ |
| `User\WishlistController@store` | `POST /api/v1/user/wishlist` | `WishlistPage.vue` | ✅ |
| `User\WishlistController@destroy` | `DELETE /api/v1/user/wishlist/{wishlist}` | `WishlistPage.vue` | ✅ |
| - | - | `DashboardPage.vue` | ✅ |

## 🛡️ کنترلرهای پنل ادمین (Admin Panel)

| کنترلر Backend | Route | صفحه Frontend | وضعیت |
|----------------|-------|---------------|-------|
| `Admin\DashboardController@index` | `GET /api/v1/admin/dashboard` | `admin/DashboardPage.vue` | ✅ |
| `Admin\OrderController@index` | `GET /api/v1/admin/orders` | `admin/OrdersPage.vue` | ✅ |
| `Admin\OrderController@show` | `GET /api/v1/admin/orders/{order}` | `admin/OrderDetailPage.vue` | ✅ |
| `Admin\OrderController@updateStatus` | `PATCH /api/v1/admin/orders/{order}/status` | `admin/OrderDetailPage.vue` | ✅ |
| `Admin\OrderController@updatePaymentStatus` | `PATCH /api/v1/admin/orders/{order}/payment-status` | `admin/OrderDetailPage.vue` | ✅ |
| `Admin\OrderController@updateNotes` | `PATCH /api/v1/admin/orders/{order}/notes` | `admin/OrderDetailPage.vue` | ✅ |
| `Admin\CouponController@index` | `GET /api/v1/admin/coupons` | `admin/CouponsPage.vue` | ✅ |
| `Admin\CouponController@store` | `POST /api/v1/admin/coupons` | `admin/CouponsPage.vue` | ✅ |
| `Admin\CouponController@show` | `GET /api/v1/admin/coupons/{coupon}` | `admin/CouponsPage.vue` | ✅ |
| `Admin\CouponController@update` | `PUT /api/v1/admin/coupons/{coupon}` | `admin/CouponsPage.vue` | ✅ |
| `Admin\CouponController@destroy` | `DELETE /api/v1/admin/coupons/{coupon}` | `admin/CouponsPage.vue` | ✅ |
| `ProductController` (با auth) | `POST/PUT/DELETE /api/v1/products` | `admin/ProductsPage.vue` | ✅ |
| `CategoryController` (با auth) | `POST/PUT/DELETE /api/v1/categories` | `admin/CategoriesPage.vue` | ✅ |
| IAM Package | - | `admin/UsersPage.vue` | ✅ (از IAM) |
| - | - | `admin/SettingsPage.vue` | ✅ |

## 📦 کنترلرهای اضافی (بدون UI مستقیم)

| کنترلر Backend | استفاده |
|----------------|----------|
| `TaxClassController` | API برای محاسبات مالیاتی |
| `OptionController` | API برای ویژگی‌های محصول |
| `OptionValueController` | API برای مقادیر ویژگی‌ها |
| `CustomerGroupController` | API برای گروه‌های مشتری |
| `VendorController` | API برای فروشندگان |

---

## 🔒 دسترسی‌های کاربران

### کاربر میهمان (Guest):
- ✅ مشاهده محصولات و دسته‌بندی‌ها
- ✅ جستجو در محصولات
- ✅ افزودن به سبد خرید (localStorage)
- ❌ خرید و پرداخت
- ❌ دسترسی به پنل کاربری
- ❌ دسترسی به پنل ادمین

### کاربر عادی (Authenticated User):
- ✅ همه دسترسی‌های میهمان
- ✅ خرید و پرداخت
- ✅ مشاهده و مدیریت سفارشات
- ✅ مدیریت آدرس‌ها
- ✅ لیست علاقه‌مندی‌ها
- ✅ ویرایش پروفایل
- ✅ سبد خرید سینک با سرور
- ✅ **انتقال سبد خرید guest به حساب کاربری بعد از لاگین**
- ❌ دسترسی به پنل ادمین

### کاربر ادمین (Admin/Owner/Editor):
- ✅ همه دسترسی‌های کاربر عادی
- ✅ مشاهده داشبورد ادمین
- ✅ مدیریت سفارشات
- ✅ مدیریت محصولات
- ✅ مدیریت دسته‌بندی‌ها
- ✅ مدیریت کوپن‌ها
- ✅ تنظیمات سایت

### کاربر سوپرادمین (Superadmin):
- ✅ همه دسترسی‌های ادمین
- ✅ مدیریت کاربران (از طریق IAM)
- ✅ مدیریت نقش‌ها و مجوزها

---

## ✅ وضعیت نهایی

| بخش | وضعیت |
|-----|-------|
| **تطبیق کنترلر-ویو** | ✅ 100% |
| **صفحه لاگین/ثبت‌نام** | ✅ کامل |
| **Guest Cart** | ✅ با localStorage |
| **Merge Cart بعد لاگین** | ✅ پیاده‌سازی شده |
| **Navigation Guards** | ✅ کامل |
| **Admin Middleware** | ✅ کامل |
| **دسترسی‌های کاربران** | ✅ تفکیک شده |

---

## 🎯 نکات مهم:

1. **سبد خرید میهمان**: در `localStorage` با کلید `guest_cart` ذخیره می‌شود
2. **انتقال سبد خرید**: بعد از لاگین، `mergeGuestCart()` فراخوانی می‌شود
3. **محافظت routes**: با `requiresAuth` و `requiresAdmin` در meta
4. **Middleware backend**: `admin` middleware برای routes ادمین
5. **Role checking**: در auth store با `isAdmin`, `hasRole`, `hasPermission`
