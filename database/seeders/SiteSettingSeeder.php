<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'site_name',         'value' => 'اتکا',                                                   'group' => 'general', 'type' => 'text'],
            ['key' => 'site_description',  'value' => 'تجارت الکترونیک و فناوری',                               'group' => 'general', 'type' => 'text'],
            ['key' => 'site_about',        'value' => 'شرکت تجارت الکترونیک و فناوری اتکا با بیش از ۱۰ سال تجربه در ارائه بهترین محصولات با کیفیت و قیمت مناسب، همواره در تلاش است تا بهترین تجربه خرید آنلاین را برای شما فراهم کند.', 'group' => 'general', 'type' => 'text'],
            ['key' => 'copyright_text',    'value' => 'شرکت تجارت الکترونیک و فناوری اتکا. تمامی حقوق محفوظ است.', 'group' => 'general', 'type' => 'text'],
            ['key' => 'logo',              'value' => null,                                                      'group' => 'identity', 'type' => 'file'],
            ['key' => 'logo_alt',          'value' => 'لوگو فروشگاه',                                           'group' => 'identity', 'type' => 'text'],
            ['key' => 'favicon',           'value' => null,                                                      'group' => 'identity', 'type' => 'file'],
            ['key' => 'support_hours',     'value' => 'پشتیبانی ۲۴ ساعته',                                     'group' => 'contact',  'type' => 'text'],
            ['key' => 'phone',             'value' => '۰۲۱-۱۲۳۴۵۶۷۸',                                         'group' => 'contact',  'type' => 'text'],
            ['key' => 'phone_link',        'value' => 'tel:02112345678',                                        'group' => 'contact',  'type' => 'text'],
            ['key' => 'email',             'value' => 'info@atka.ir',                                           'group' => 'contact',  'type' => 'text'],
            ['key' => 'address',           'value' => 'تهران، خیابان ولیعصر',                                   'group' => 'contact',  'type' => 'text'],
            ['key' => 'social_instagram',  'value' => '',                                                       'group' => 'social',   'type' => 'text'],
            ['key' => 'social_telegram',   'value' => '',                                                       'group' => 'social',   'type' => 'text'],
            ['key' => 'social_whatsapp',   'value' => '',                                                       'group' => 'social',   'type' => 'text'],
        ];

        foreach ($defaults as $setting) {
            SiteSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
