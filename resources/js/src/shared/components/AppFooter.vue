<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { Phone, Mail, MapPin, Instagram, Send, MessageCircle } from 'lucide-vue-next'
import { useSiteSettings } from '@shared/composables/useSiteSettings'
import { useNavLinks } from '@shared/composables/useNavLinks'

const { settings } = useSiteSettings()
const { links: quickLinks } = useNavLinks('footer_quick')
const { links: serviceLinks } = useNavLinks('footer_service')
</script>

<template>
  <footer class="bg-secondary-800 text-white">
    <!-- Main Footer -->
    <div class="container mx-auto px-4 py-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
        <!-- About -->
        <div class="lg:col-span-2">
          <div class="flex items-center gap-2 mb-4">
            <RouterLink to="/" class="flex items-center gap-2 min-w-0">
              <img
                :src="settings.logo || '/images/Logo/tejarat-logo.jpg'"
                :alt="settings.logo_alt || settings.site_name || 'لوگو فروشگاه'"
                class="h-9 w-auto object-contain flex-shrink-0"
              />
            </RouterLink>
            <div>
              <span class="text-xl font-bold">{{ settings.site_name || 'اتکا' }}</span>
              <span class="block text-xs text-secondary-400">{{ settings.site_description || 'تجارت الکترونیک و فناوری' }}</span>
            </div>
          </div>
          <p class="text-secondary-400 text-sm leading-relaxed mb-4">
            {{ settings.site_about || 'شرکت تجارت الکترونیک و فناوری اتکا با بیش از ۱۰ سال تجربه در ارائه بهترین محصولات با کیفیت و قیمت مناسب، همواره در تلاش است تا بهترین تجربه خرید آنلاین را برای شما فراهم کند.' }}
          </p>
          <!-- Social Links -->
          <div class="flex items-center gap-3">
            <a
              v-if="settings.social_instagram"
              :href="settings.social_instagram"
              target="_blank"
              rel="noopener noreferrer"
              class="w-10 h-10 bg-secondary-700 hover:bg-primary-600 rounded-lg flex items-center justify-center transition"
              aria-label="اینستاگرام"
            >
              <Instagram class="w-5 h-5" />
            </a>
            <a
              v-if="settings.social_telegram"
              :href="settings.social_telegram"
              target="_blank"
              rel="noopener noreferrer"
              class="w-10 h-10 bg-secondary-700 hover:bg-primary-600 rounded-lg flex items-center justify-center transition"
              aria-label="تلگرام"
            >
              <Send class="w-5 h-5" />
            </a>
            <a
              v-if="settings.social_whatsapp"
              :href="settings.social_whatsapp"
              target="_blank"
              rel="noopener noreferrer"
              class="w-10 h-10 bg-secondary-700 hover:bg-primary-600 rounded-lg flex items-center justify-center transition"
              aria-label="واتساپ"
            >
              <MessageCircle class="w-5 h-5" />
            </a>
          </div>
        </div>
        
        <!-- Quick Links -->
        <div>
          <h3 class="text-lg font-bold mb-4 text-white">دسترسی سریع</h3>
          <ul class="space-y-3 text-sm">
            <template v-if="quickLinks.length > 0">
              <li v-for="link in quickLinks" :key="link.id">
                <component
                  :is="link.url.startsWith('http') ? 'a' : RouterLink"
                  v-bind="link.url.startsWith('http') ? { href: link.url, target: link.target } : { to: link.url }"
                  class="text-secondary-400 hover:text-primary-400 transition"
                >
                  {{ link.label }}
                </component>
              </li>
            </template>
            <template v-else>
              <li><RouterLink to="/products" class="text-secondary-400 hover:text-primary-400 transition">محصولات</RouterLink></li>
              <li><RouterLink to="/categories" class="text-secondary-400 hover:text-primary-400 transition">دسته‌بندی‌ها</RouterLink></li>
              <li><RouterLink to="/brands" class="text-secondary-400 hover:text-primary-400 transition">برندها</RouterLink></li>
              <li><RouterLink to="/discounts" class="text-secondary-400 hover:text-primary-400 transition">تخفیف‌ها</RouterLink></li>
            </template>
          </ul>
        </div>
        
        <!-- Customer Service -->
        <div>
          <h3 class="text-lg font-bold mb-4 text-white">خدمات مشتریان</h3>
          <ul class="space-y-3 text-sm">
            <template v-if="serviceLinks.length > 0">
              <li v-for="link in serviceLinks" :key="link.id">
                <component
                  :is="link.url.startsWith('http') ? 'a' : RouterLink"
                  v-bind="link.url.startsWith('http') ? { href: link.url, target: link.target } : { to: link.url }"
                  class="text-secondary-400 hover:text-primary-400 transition"
                >
                  {{ link.label }}
                </component>
              </li>
            </template>
            <template v-else>
              <li><RouterLink to="/user/orders" class="text-secondary-400 hover:text-primary-400 transition">پیگیری سفارش</RouterLink></li>
              <li><RouterLink to="/user/addresses" class="text-secondary-400 hover:text-primary-400 transition">آدرس‌های من</RouterLink></li>
              <li><RouterLink to="/user/wishlist" class="text-secondary-400 hover:text-primary-400 transition">لیست علاقه‌مندی‌ها</RouterLink></li>
              <li><a href="/faq" class="text-secondary-400 hover:text-primary-400 transition">سوالات متداول</a></li>
              <li><a href="/terms" class="text-secondary-400 hover:text-primary-400 transition">قوانین و مقررات</a></li>
            </template>
          </ul>
        </div>
        
        <!-- Contact -->
        <div>
          <h3 class="text-lg font-bold mb-4 text-white">تماس با ما</h3>
          <ul class="space-y-3 text-sm">
            <li v-if="settings.address" class="flex items-center gap-2 text-secondary-400">
              <MapPin class="w-4 h-4 text-primary-500 flex-shrink-0" />
              <span>{{ settings.address }}</span>
            </li>
            <li v-if="settings.phone" class="flex items-center gap-2 text-secondary-400">
              <Phone class="w-4 h-4 text-primary-500 flex-shrink-0" />
              <a :href="settings.phone_link || `tel:${settings.phone}`" class="hover:text-primary-400 transition">
                {{ settings.phone }}
              </a>
            </li>
            <li v-if="settings.email" class="flex items-center gap-2 text-secondary-400">
              <Mail class="w-4 h-4 text-primary-500 flex-shrink-0" />
              <a :href="`mailto:${settings.email}`" class="hover:text-primary-400 transition">
                {{ settings.email }}
              </a>
            </li>
          </ul>
          
          <!-- Trust Badges -->
          <div class="mt-6 flex gap-2">
            <div class="w-16 h-16 bg-secondary-700 rounded-lg flex items-center justify-center">
              <span class="text-xs text-center text-secondary-400">نماد<br/>اعتماد</span>
            </div>
            <div class="w-16 h-16 bg-secondary-700 rounded-lg flex items-center justify-center">
              <span class="text-xs text-center text-secondary-400">ساماندهی</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Bottom Bar -->
    <div class="border-t border-secondary-700">
      <div class="container mx-auto px-4 py-4">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-secondary-400">
          <p>© {{ new Date().getFullYear() }} {{ settings.copyright_text || 'شرکت تجارت الکترونیک و فناوری اتکا. تمامی حقوق محفوظ است.' }}</p>
          <div class="flex items-center gap-4">
            <span>طراحی و توسعه با ❤️</span>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>
