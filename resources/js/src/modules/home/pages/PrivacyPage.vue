<script setup lang="ts">
import { Shield } from 'lucide-vue-next'
import Breadcrumb from '@shared/components/Breadcrumb.vue'

const lastUpdated = '۱ اردیبهشت ۱۴۰۴'
</script>

<template>
  <div class="min-h-screen bg-secondary-50" dir="rtl">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
      <Breadcrumb :items="[{ title: 'حریم خصوصی' }]" />

      <!-- Hero -->
      <div class="text-center py-10">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-100 rounded-2xl mb-4">
          <Shield class="w-8 h-8 text-emerald-600" />
        </div>
        <h1 class="text-3xl font-bold text-secondary-800 mb-3">سیاست حریم خصوصی</h1>
        <p class="text-secondary-500 text-sm">آخرین به‌روزرسانی: {{ lastUpdated }}</p>
      </div>

      <!-- Content -->
      <div class="bg-white rounded-2xl shadow-sm border border-secondary-100 p-8 space-y-8 text-secondary-700 leading-8">

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۱. مقدمه</h2>
          <p>
            فروشگاه اتکا («ما»، «سایت») به حفظ حریم خصوصی کاربران خود اهمیت بسیاری می‌دهد. این سیاست
            توضیح می‌دهد که چه اطلاعاتی از شما جمع‌آوری می‌شود، چگونه از آن استفاده می‌کنیم و چگونه از
            آن محافظت می‌کنیم. با استفاده از سایت، شما با مفاد این سیاست موافقت می‌کنید.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۲. اطلاعاتی که جمع‌آوری می‌کنیم</h2>
          <p class="mb-3">ما اطلاعات زیر را از شما جمع‌آوری می‌کنیم:</p>
          <ul class="space-y-2 list-disc list-inside mr-2 text-secondary-600">
            <li><strong>اطلاعات هویتی:</strong> نام، نام خانوادگی، شماره موبایل، آدرس ایمیل</li>
            <li><strong>اطلاعات آدرس:</strong> استان، شهر، آدرس پستی، کد پستی</li>
            <li><strong>اطلاعات مالی:</strong> اطلاعات پرداخت (از طریق درگاه‌های امن بانکی)</li>
            <li><strong>اطلاعات فعالیت:</strong> تاریخچه سفارشات، محصولات مورد علاقه، سبد خرید</li>
            <li><strong>اطلاعات فنی:</strong> آدرس IP، نوع مرورگر، داده‌های کوکی</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۳. نحوه استفاده از اطلاعات</h2>
          <p class="mb-3">اطلاعات جمع‌آوری‌شده برای اهداف زیر استفاده می‌شود:</p>
          <ul class="space-y-2 list-disc list-inside mr-2 text-secondary-600">
            <li>پردازش و ارسال سفارشات</li>
            <li>مدیریت حساب کاربری شما</li>
            <li>ارائه پشتیبانی و پاسخ به استعلامات</li>
            <li>ارسال اعلان‌های مربوط به سفارش (پیامک / ایمیل)</li>
            <li>بهبود خدمات و تجربه خرید</li>
            <li>پیشنهادات شخصی‌سازی‌شده (با رضایت کاربر)</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۴. اشتراک‌گذاری اطلاعات</h2>
          <p class="mb-3">
            ما اطلاعات شخصی شما را به هیچ شخص ثالثی برای اهداف بازاریابی نمی‌فروشیم. اطلاعات شما تنها در
            موارد زیر به اشتراک گذاشته می‌شود:
          </p>
          <ul class="space-y-2 list-disc list-inside mr-2 text-secondary-600">
            <li><strong>شرکت‌های حمل‌ونقل:</strong> برای ارسال سفارشات</li>
            <li><strong>درگاه‌های پرداخت:</strong> برای پردازش تراکنش‌های مالی</li>
            <li><strong>الزامات قانونی:</strong> در صورت درخواست مراجع قضایی</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۵. امنیت اطلاعات</h2>
          <p>
            از پروتکل HTTPS برای رمزنگاری تمام ارتباطات استفاده می‌کنیم. اطلاعات پرداخت شما مستقیماً از
            طریق درگاه‌های بانکی معتبر و دارای تأییدیه بانک مرکزی پردازش می‌شود و هیچ‌گونه اطلاعات کارت
            بانکی روی سرورهای ما ذخیره نمی‌شود.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۶. کوکی‌ها</h2>
          <p>
            سایت از کوکی‌ها برای بهبود تجربه کاربری، نگهداری سبد خرید، و تحلیل آمار بازدید استفاده می‌کند.
            می‌توانید از طریق تنظیمات مرورگر خود کوکی‌ها را مدیریت یا غیرفعال کنید، هرچند این کار ممکن
            است برخی از قابلیت‌های سایت را محدود کند.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۷. حقوق کاربران</h2>
          <p class="mb-3">شما حق دارید:</p>
          <ul class="space-y-2 list-disc list-inside mr-2 text-secondary-600">
            <li>به اطلاعات ذخیره‌شده خود دسترسی داشته باشید</li>
            <li>درخواست اصلاح اطلاعات نادرست را بدهید</li>
            <li>درخواست حذف اطلاعات خود را بدهید</li>
            <li>در هر زمان از دریافت ایمیل‌های تبلیغاتی انصراف دهید</li>
          </ul>
          <p class="mt-3">برای اعمال این حقوق با پشتیبانی ما تماس بگیرید.</p>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۸. تغییرات در این سیاست</h2>
          <p>
            ما ممکن است این سیاست را به‌روز کنیم. تغییرات مهم از طریق اعلانیه در سایت یا ایمیل به شما
            اطلاع داده خواهد شد. ادامه استفاده از سایت پس از انتشار تغییرات به منزله قبول آن‌هاست.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۹. تماس با ما</h2>
          <p>
            برای هرگونه سوال در مورد این سیاست حریم خصوصی می‌توانید از طریق ایمیل
            <a href="mailto:privacy@etka.ir" class="text-primary-600 hover:underline">privacy@etka.ir</a>
            یا شماره تلفن پشتیبانی با ما در تماس باشید.
          </p>
        </section>

      </div>
    </div>
  </div>
</template>
