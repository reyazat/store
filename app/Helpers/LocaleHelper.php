<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class LocaleHelper
{
    /**
     * Get current locale.
     *
     * @return string
     */
    public static function current(): string
    {
        return App::getLocale();
    }

    /**
     * Get supported locales.
     *
     * @return array
     */
    public static function supported(): array
    {
        return config('app.supported_locales', ['fa', 'en', 'ar', 'fr']);
    }

    /**
     * Check if locale is supported.
     *
     * @param string $locale
     * @return bool
     */
    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::supported(), true);
    }

    /**
     * Get locale from request.
     *
     * @return string
     */
    public static function fromRequest(): string
    {
        return request()->attributes->get('locale', self::current());
    }

    /**
     * Get fallback locale.
     *
     * @return string
     */
    public static function fallback(): string
    {
        return config('app.fallback_locale', 'fa');
    }

    /**
     * Get locale direction (RTL or LTR).
     *
     * @param string|null $locale
     * @return string
     */
    public static function direction(?string $locale = null): string
    {
        $locale = $locale ?? self::current();

        $rtlLocales = ['fa', 'ar'];

        return in_array($locale, $rtlLocales) ? 'rtl' : 'ltr';
    }

    /**
     * Check if locale is RTL.
     *
     * @param string|null $locale
     * @return bool
     */
    public static function isRtl(?string $locale = null): bool
    {
        return self::direction($locale) === 'rtl';
    }

    /**
     * Get locale name.
     *
     * @param string|null $locale
     * @return string
     */
    public static function name(?string $locale = null): string
    {
        $locale = $locale ?? self::current();

        $names = [
            'fa' => 'فارسی',
            'en' => 'English',
            'ar' => 'العربية',
            'fr' => 'Français',
        ];

        return $names[$locale] ?? $locale;
    }

    /**
     * Get all locales with their names.
     *
     * @return array
     */
    public static function all(): array
    {
        $locales = [];

        foreach (self::supported() as $locale) {
            $locales[$locale] = [
                'code' => $locale,
                'name' => self::name($locale),
                'direction' => self::direction($locale),
                'is_rtl' => self::isRtl($locale),
            ];
        }

        return $locales;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     * @return bool
     */
    public static function set(string $locale): bool
    {
        if (!self::isSupported($locale)) {
            return false;
        }

        App::setLocale($locale);
        request()->attributes->set('locale', $locale);

        return true;
    }
}
