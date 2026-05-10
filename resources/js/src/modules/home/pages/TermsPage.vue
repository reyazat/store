<script setup lang="ts">
import { FileText } from 'lucide-vue-next'
import Breadcrumb from '@shared/components/Breadcrumb.vue'

const lastUpdated = '۱ اردیبهشت ۱۴۰۴'
</script>

<template>
  <div class="min-h-screen bg-secondary-50" dir="rtl">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
      <Breadcrumb :items="[{ title: 'قوانین و مقررات' }]" />

      <!-- Hero -->
      <div class="text-center py-10">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-amber-100 rounded-2xl mb-4">
          <FileText class="w-8 h-8 text-amber-600" />
        </div>
        <h1 class="text-3xl font-bold text-secondary-800 mb-3">قوانین و مقررات استفاده از سایت</h1>
        <p class="text-secondary-500 text-sm">آخرین به‌روزرسانی: {{ lastUpdated }}</p>
      </div>

      <!-- Content -->
      <div class="bg-white rounded-2xl shadow-sm border border-secondary-100 p-8 space-y-8 text-secondary-700 leading-8">

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۱. پذیرش شرایط</h2>
          <p>
            با ثبت‌نام، ورود یا استفاده از سایت فروشگاهی اتکا، شما صریحاً موافقت خود را با تمام شرایط
            و قوانین مندرج در این صفحه اعلام می‌کنید. در صورت عدم موافقت، لطفاً از استفاده از سایت
            خودداری کنید.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۲. ثبت‌نام و حساب کاربری</h2>
          <ul class="space-y-2 list-disc list-inside mr-2 text-secondary-600">
            <li>برای خرید از سایت، ثبت‌نام با شماره موبایل معتبر ایرانی الزامی است.</li>
            <li>مسئولیت حفاظت از رمز عبور حساب کاربری بر عهده کاربر است.</li>
            <li>هر شماره موبایل تنها می‌تواند یک حساب کاربری داشته باشد.</li>
            <li>اطلاعات وارد‌شده باید واقعی و دقیق باشند؛ اطلاعات نادرست می‌تواند منجر به لغو سفارش شود.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۳. سفارش‌گذاری و قیمت‌ها</h2>
          <ul class="space-y-2 list-disc list-inside mr-2 text-secondary-600">
            <li>قیمت‌ها به تومان بوده و شامل مالیات بر ارزش‌افزوده می‌باشند.</li>
            <li>ثبت سفارش به منزله تأیید تهیه کالا نیست؛ سفارش پس از تأیید موجودی و تکمیل پرداخت قطعی می‌شود.</li>
            <li>در صورت اشتباه در قیمت‌گذاری، حق رد سفارش را برای خود محفوظ می‌داریم و مبلغ پرداختی استرداد خواهد شد.</li>
            <li>کدهای تخفیف تنها یک بار و تا سقف مشخص قابل استفاده هستند.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۴. پرداخت</h2>
          <ul class="space-y-2 list-disc list-inside mr-2 text-secondary-600">
            <li>پرداخت از طریق درگاه‌های بانکی امن و دارای تأییدیه بانک مرکزی انجام می‌شود.</li>
            <li>در صورت کسر وجه از حساب و عدم ثبت سفارش، مبلغ ظرف ۷۲ ساعت کاری به حساب شما برمی‌گردد.</li>
            <li>هرگونه تقلب یا استفاده غیرمجاز از اطلاعات پرداخت قابل پیگرد قانونی است.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۵. ارسال و تحویل</h2>
          <ul class="space-y-2 list-disc list-inside mr-2 text-secondary-600">
            <li>زمان ارسال بسته به آدرس تحویل و روش انتخابی متفاوت است.</li>
            <li>هزینه ارسال بر اساس وزن، ابعاد بسته و مقصد محاسبه می‌شود.</li>
            <li>مسئولیت صحت آدرس وارد‌شده بر عهده خریدار است.</li>
            <li>کالا پس از تحویل به مشتری در حکم تأیید صحت محصول است مگر ظرف ۷ روز اطلاع داده شود.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۶. مرجوعی و استرداد</h2>
          <ul class="space-y-2 list-disc list-inside mr-2 text-secondary-600">
            <li>مرجوعی کالا تا ۷ روز پس از دریافت (مطابق قانون حمایت از مصرف‌کننده) امکان‌پذیر است.</li>
            <li>کالا باید در بسته‌بندی اصلی، بدون استفاده و دارای تمام لوازم جانبی باشد.</li>
            <li>هزینه ارسال مرجوعی در صورت خرابی یا اشتباه فروشنده به عهده ماست؛ در غیر این صورت به عهده خریدار است.</li>
            <li>استرداد وجه ظرف ۳ تا ۷ روز کاری پس از دریافت کالای مرجوعی انجام می‌شود.</li>
            <li>کالاهای دیجیتال، لوازم بهداشتی و خوراکی‌ها قابل مرجوعی نیستند.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۷. ضمانت و گارانتی</h2>
          <p>
            محصولاتی که دارای گارانتی هستند، مشخصات آن در صفحه محصول درج شده است. گارانتی محصولات
            بر اساس شرایط و ضوابط سازنده یا وارد‌کننده مربوطه اعمال می‌شود.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۸. مالکیت معنوی</h2>
          <p>
            تمام محتوا، تصاویر، لوگو، طراحی و کدهای سایت متعلق به فروشگاه اتکا بوده و استفاده
            تجاری از آن‌ها بدون اجازه کتبی ممنوع است.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۹. محدودیت مسئولیت</h2>
          <p>
            فروشگاه اتکا در قبال خسارات ناشی از قطعی اینترنت، نقص فنی موقت سایت، یا تغییرات قیمتی
            خارج از کنترل (مانند نوسانات ارزی) مسئولیتی ندارد.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۱۰. قانون حاکم</h2>
          <p>
            این قرارداد تابع قوانین جمهوری اسلامی ایران است. هرگونه اختلاف ابتدا از طریق مذاکره و
            در صورت لزوم از طریق مراجع قضایی ذی‌صلاح ایران حل‌وفصل خواهد شد.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-bold text-secondary-800 mb-3">۱۱. تماس با ما</h2>
          <p>
            برای هرگونه سوال یا ابهام درباره این قوانین با پشتیبانی ما از طریق ایمیل
            <a href="mailto:support@etka.ir" class="text-primary-600 hover:underline">support@etka.ir</a>
            یا شماره تلفن درج‌شده در سایت تماس بگیرید.
          </p>
        </section>

      </div>
    </div>
  </div>
</template>
