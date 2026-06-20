# 📋 گزارش نواقص و کارهای باقی‌مانده

**تاریخ:** 2026-06-20  
**وضعیت:** ✅ StoreCouponController ایجاد شد - نواقص جدید شناسایی شد

---

## ✅ کارهای انجام‌شده

### 1. CouponPolicy ایجاد شد
**فایل:** `app/Policies/CouponPolicy.php`

- ✅ Platform-level coupons فقط توسط Super Admin قابل مدیریت
- ✅ Store-level coupons توسط Store Owner قابل مدیریت
- ✅ Ownership check برای همه عملیات

### 2. StoreCouponController ایجاد شد
**فایل:** `app/Http/Controllers/API/Store/StoreCouponController.php`

- ✅ فقط کوپن‌های فروشگاه خود را نمایش می‌دهد
- ✅ مشابه `StoreReviewController` پیاده‌سازی شد
- ✅ از `CouponPolicy` برای authorization استفاده می‌کند
- ✅ Ownership check در همه متدها

### 3. AdminCouponController اصلاح شد
**فایل:** `app/Http/Controllers/API/Admin/CouponController.php`

- ✅ فقط platform-level coupons (`organization_id = null`) را مدیریت می‌کند
- ✅ Authorization با `CouponPolicy` اضافه شد
- ✅ جلوگیری از دسترسی به store-level coupons

### 4. CouponPolicy در AppServiceProvider ثبت شد
**فایل:** `app/Providers/AppServiceProvider.php`

```php
Gate::policy(\App\Models\Coupon::class, \App\Policies\CouponPolicy::class);
```

---

## 🚨 نواقص شناسایی‌شده جدید

### ❌ ISSUE #1: Super Admin Access به Store Panel بدون Organization Context

**شدت:** 🔴 بحرانی

**مشکل:**  
Super Admin اکنون می‌تواند به `/v1/store/*` دسترسی داشته باشد (طبق رفع BAG #1)، اما همه Store Controllers فرض می‌کنند که `auth()->user()->organization_id` وجود دارد:

```php
// StoreDashboardController.php:21
$org = Organization::withCount('users')->findOrFail($owner->organization_id);
// ❌ Super Admin: organization_id = null → خطا!
```

**تاثیر:**
- Super Admin نمی‌تواند داشبورد فروشگاه‌ها را ببیند
- خطای 404 یا null pointer exception

**راه‌حل پیشنهادی:**

**گزینه 1: Organization ID از Route Parameter**
```php
// routes/api.php
Route::get('/store/{organization}/dashboard', ...);

// StoreDashboardController
public function index(Organization $organization): JsonResponse
{
    $user = auth()->user();
    
    // Super admin can view any store
    if (!$user->is_super_admin && $user->organization_id !== $organization->id) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    // ...
}
```

**گزینه 2: Middleware تعیین Organization Context**
```php
// SetStoreContext middleware
if ($user->is_super_admin) {
    // Get organization from query param or header
    $orgId = $request->query('organization_id') ?? $request->header('X-Organization-Id');
    $request->merge(['_organization_id' => $orgId]);
} else {
    $request->merge(['_organization_id' => $user->organization_id]);
}
```

**گزینه 3: Super Admin از Store Panel استفاده نکند**
- Super Admin فقط از `/v1/admin/organizations/{id}/overview` استفاده کند
- Store Panel فقط برای Store Owners باشد
- این ساده‌ترین راه‌حل است اما کمتر منعطف

---

### ⚠️ ISSUE #2: Store Controllers نیاز به Super Admin Handling دارند

**شدت:** 🟠 مهم

**فایل‌های تاثیرگذار:**
- `StoreDashboardController.php`
- `StoreProfileController.php`
- `StoreMemberController.php`
- `StoreOrderController.php`
- `StoreReviewController.php`
- `StoreCouponController.php`

**مشکل:**  
همه این controllers فرض می‌کنند:
```php
$organizationId = $user->organization_id; // ❌ برای Super Admin null است
```

**راه‌حل:**  
بسته به تصمیم معماری (گزینه 1، 2، یا 3 از ISSUE #1)

---

### ⚠️ ISSUE #3: CouponStoreRequest نیاز به Validation برای organization_id

**شدت:** 🟡 متوسط

**فایل:** `app/Http/Requests/Coupon/CouponStoreRequest.php`

**مشکل:**  
Request فعلی `organization_id` را validate نمی‌کند. اگر کاربر بخواهد `organization_id` را دستی ارسال کند، می‌تواند به فروشگاه دیگری کوپن اضافه کند.

**راه‌حل:**
```php
public function rules(): array
{
    return [
        // ... existing rules
        'organization_id' => ['prohibited'], // جلوگیری از ارسال دستی
    ];
}
```

یا در Controller:
```php
// Already fixed in StoreCouponController:
$data['organization_id'] = $organizationId; // Force set
```

---

### ⚠️ ISSUE #4: Review Policy وجود ندارد

**شدت:** 🟡 متوسط

**مشکل:**  
`StoreReviewController` ownership check دارد اما Policy ندارد. برای یکپارچگی بهتر است `ReviewPolicy` ایجاد شود.

**راه‌حل:**
```php
// app/Policies/ReviewPolicy.php
class ReviewPolicy
{
    public function approve(User $user, ProductReview $review): bool
    {
        if ($user->is_super_admin) return true;
        return $user->organization_id === $review->product->organization_id;
    }
    
    public function reject(User $user, ProductReview $review): bool
    {
        return $this->approve($user, $review);
    }
}
```

---

### ℹ️ ISSUE #5: Order Policy وجود ندارد

**شدت:** 🟡 متوسط

**مشکل:**  
`StoreOrderController` ownership check دارد اما Policy ندارد.

**یادداشت:**  
Orders پیچیده‌تر هستند چون یک order می‌تواند محصولات از چند فروشگاه داشته باشد. فعلاً با `whereHas` حل شده اما Policy می‌تواند کد را تمیزتر کند.

---

## 📊 خلاصه نواقص

| # | نقص | شدت | اولویت | وضعیت |
|---|-----|-----|--------|-------|
| 1 | Super Admin Access بدون Organization Context | 🔴 بحرانی | بالا | نیاز به تصمیم معماری |
| 2 | Store Controllers نیاز به Super Admin Handling | 🟠 مهم | بالا | وابسته به #1 |
| 3 | CouponStoreRequest validation | 🟡 متوسط | متوسط | ✅ رفع شد در Controller |
| 4 | Review Policy وجود ندارد | 🟡 متوسط | پایین | پیشنهادی |
| 5 | Order Policy وجود ندارد | 🟡 متوسط | پایین | پیشنهادی |

---

## 🎯 پیشنهاد نهایی

### تصمیم معماری مورد نیاز:

**سوال کلیدی:** Super Admin چگونه به داده‌های فروشگاه‌ها دسترسی پیدا کند؟

**گزینه پیشنهادی: گزینه 3 (ساده‌ترین)**

1. **Super Admin از Store Panel استفاده نکند**
   - Route `/v1/store/*` فقط برای Store Owners
   - Super Admin از `/v1/admin/organizations/{id}/*` استفاده کند

2. **ایجاد Admin Organization Routes:**
   ```php
   // routes/api.php - در super.admin middleware
   Route::prefix('organizations/{organization}')->group(function () {
       Route::get('/dashboard', [AdminOrganizationController::class, 'dashboard']);
       Route::get('/members', [AdminOrganizationController::class, 'members']);
       Route::get('/orders', [AdminOrganizationController::class, 'orders']);
       Route::get('/coupons', [AdminOrganizationController::class, 'coupons']);
       Route::get('/reviews', [AdminOrganizationController::class, 'reviews']);
   });
   ```

3. **مزایا:**
   - ✅ جداسازی واضح: Store Panel برای Owners، Admin Panel برای Super Admin
   - ✅ نیازی به تغییر Store Controllers نیست
   - ✅ URL structure واضح‌تر: `/admin/organizations/123/dashboard`
   - ✅ کمتر احتمال خطا

4. **معایب:**
   - ⚠️ نیاز به ایجاد `AdminOrganizationController` جدید
   - ⚠️ کد تکراری (اما می‌توان از Services استفاده کرد)

---

## ✅ فایل‌های ایجاد/اصلاح‌شده در این مرحله

1. ✅ `app/Policies/CouponPolicy.php` - ایجاد شد
2. ✅ `app/Http/Controllers/API/Store/StoreCouponController.php` - ایجاد شد
3. ✅ `app/Http/Controllers/API/Admin/CouponController.php` - اصلاح شد
4. ✅ `app/Providers/AppServiceProvider.php` - CouponPolicy ثبت شد

---

## 🔄 کارهای بعدی پیشنهادی

1. **تصمیم‌گیری درباره Super Admin Access Pattern** (ISSUE #1)
2. **پیاده‌سازی راه‌حل انتخابی** (ISSUE #2)
3. **ایجاد ReviewPolicy** (اختیاری - ISSUE #4)
4. **ایجاد OrderPolicy** (اختیاری - ISSUE #5)
5. **تست کامل سناریوهای دسترسی**

---

**تهیه‌کننده:** Cascade AI  
**وضعیت:** ✅ StoreCouponController کامل - نیاز به تصمیم معماری برای Super Admin Access
