<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Centralized, Type-Safe Theme Options Repository (`laico_theme_options`).
 *
 * Powers all 6 tabs of the Laico Theme Settings Panel:
 * 1. General, Branding & Contact (`general`)
 * 2. Home Sections & Counters (`sections`)
 * 3. Performance & Optimization (`optimization`)
 * 4. SEO, Verification & JSON-LD Schema (`seo`)
 * 5. Consultation Forms & Notifications (`forms`)
 * 6. Patch Updater & Signature Security (`patch`)
 */
final class ThemeOptions
{
    public const OPTION_KEY = 'laico_theme_options';

    /** Marks that the one-time "bilingual by default" switch of 1.5.5 has run on this site. */
    public const BILINGUAL_FLAG = 'laico_bilingual_default_v155';

    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /**
     * Complete default settings for all tabs.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            // --- Tab 1: General, Branding & Contact ---
            'brand_color' => '#0b1d33',
            'logo_url' => '',
            'favicon_url' => '',
            'default_language' => 'fa',
            'enable_english' => true,
            'enable_lang_switcher' => true,
            'phone_primary' => '۰۹۱۲۰۸۹۸۹۳۸',
            'phone_primary_tel' => '+989120898938',
            'phone_secondary' => '',
            'email' => 'info@laicodesign.com',
            'address_fa' => 'مازندران، آمل، دریای ۲۰، لایکودیزاین',
            'address_en' => 'Mazandaran, Amol, Darya 20, Laico Design',
            'hours_fa' => 'شنبه تا پنجشنبه، ۱۰ تا ۱۹',
            'hours_en' => 'Saturday to Thursday, 10:00 to 19:00',
            'whatsapp_url' => 'https://wa.me/989120898938',
            'telegram_url' => 'https://t.me/Laico_design',
            'instagram_url' => 'https://www.instagram.com/Laico_design/',
            'youtube_url' => '',
            'map_link_url' => 'https://maps.google.com/?q=36.4781646,52.3449366',
            'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2548.1376192675475!2d52.3449366!3d36.4781646!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3f8f97ad81a3b509%3A0x4970e7d40866b421!2sLaico_design!5e1!3m2!1sen!2s!4v1793627903188!5m2!1sen!2s',
            'map_lat' => '36.4781646',
            'map_lng' => '52.3449366',
            'footer_tagline_fa' => 'لایکودیزاین مجموعه‌ی معماری و طراحی داخلی',
            'footer_tagline_en' => 'Laico Design — architecture and interior design',
            'footer_copy_fa' => 'تمامی حقوق این سایت محفوظ می‌باشد.',
            'footer_copy_en' => 'All rights reserved.',
            'footer_credit_fa' => 'طراحی و اجرا آمل نوین',
            'footer_credit_en' => 'Designed and built by Amol Novin',

            // --- Optional explicit Page ID overrides (used by `Hero::findSectionPageId`) ---
            'services_page_id' => 0,
            'projects_page_id' => 0,
            'journal_page_id' => 0,
            'about_page_id' => 0,
            'contact_page_id' => 0,

            // --- Tab 2: Home Sections & Studio Stats ---
            'sec_home_collections' => true,
            'sec_home_projects' => true,
            'sec_home_about' => true,
            'sec_home_craft' => true,
            'sec_home_journal' => true,
            'sec_home_cta' => true,
            'home_kicker_fa' => 'معماری داخلی • سیستم آشپزخانه لوکس',
            'home_kicker_en' => 'Interior architecture • Luxury kitchen system',
            'home_title_fa' => 'لایکو دیزاین، فراتر از طراحی',
            'home_title_en' => 'Laico Design, beyond design',
            'home_lede_fa' => 'لایکو دیزاین؛ طراحی و اجرای آشپزخانه، پذیرایی و اتاق، حمام و فضاهای کافه-رستوران با زبان مینیمال و متریال صادق.',
            'home_lede_en' => 'Laico Design plans and builds kitchens, living rooms, baths, and café spaces in a minimal language, with honest materials.',
            'stat_1_num' => '+120',
            'stat_1_fa' => 'پروژه اجراشده',
            'stat_1_en' => 'Completed projects',
            'stat_2_num' => '14',
            'stat_2_fa' => 'سال تجربه',
            'stat_2_en' => 'Years of practice',
            'stat_3_num' => '9',
            'stat_3_fa' => 'برند اروپایی',
            'stat_3_en' => 'European brands',

            // --- Tab 3: Performance & Optimization ---
            'opt_preload_fonts' => true,
            'opt_preload_hero' => true,
            'opt_zero_fouc_lang' => true,
            'opt_defer_scripts' => true,
            'opt_lazy_images' => true,
            'opt_disable_emojis' => true,
            'opt_disable_embeds' => true,
            'opt_dequeue_jquery_migrate' => true,
            'opt_remove_dashicons_guest' => true,
            'opt_disable_xmlrpc' => true,
            'opt_remove_wp_version' => true,
            'opt_disable_self_pingbacks' => true,
            'opt_instant_page_prefetch' => true,
            'opt_page_loader' => true,
            'opt_webp_uploads' => true,

            // --- Tab 4: SEO & Schema ---
            'seo_enable_builtin' => true,
            'seo_schema_org' => true,
            'seo_redirect_legacy_html' => true,
            'seo_noindex_search' => true,
            'seo_noindex_author' => true,
            'seo_title_separator' => '—',
            'seo_default_og_image' => '',
            'seo_home_description_fa' => 'لایکو دیزاین؛ طراحی و اجرای آشپزخانه، پذیرایی و اتاق، حمام و فضاهای کافه-رستوران با زبان مینیمال و متریال صادق.',
            'seo_home_description_en' => 'Laico Design — interior architecture and bespoke handleless kitchen systems in Amol & Tehran.',
            'seo_google_verification' => '',
            'seo_bing_verification' => '',
            'seo_head_code' => '',
            'seo_footer_code' => '',

            // --- Tab 5: Consultation Forms & Notifications ---
            'form_enable_email' => true,
            'form_store_ip' => true,
            'form_notify_email' => '',
            'form_rate_limit_max' => 5,
            'form_telegram_bot_token' => '',
            'form_telegram_chat_id' => '',
            'form_success_title_fa' => 'درخواست شما ثبت شد.',
            'form_success_title_en' => 'Your request is in.',
            'form_success_body_fa' => 'تیم طراحی ظرف ۴۸ ساعت کاری برای جلسه اول با شما تماس می‌گیرد.',
            'form_success_body_en' => 'The design team will call within 48 working hours to arrange the first meeting.',

            // --- Tab 5b: IPPanel Core SMS Notifications (Admin & Customer) ---
            'sms_ippanel_token' => '',
            'sms_admin_enable' => false,
            'sms_admin_phones' => '',
            'sms_admin_mode' => 'pattern',
            'sms_admin_pattern_code' => '',
            'sms_admin_pattern_vars' => "name={name}\nphone={phone}\ntype={project_type}\npage={source_page}",
            'sms_admin_text_template' => "درخواست مشاوره جدید در لایکودیزاین (#{id})\nنام: {name}\nتلفن: {phone_local}\nبخش: {project_type}\nصفحه: {source_page}",
            'sms_customer_enable' => false,
            'sms_customer_mode' => 'pattern',
            'sms_customer_pattern_code' => '',
            'sms_customer_pattern_vars' => "name={name}\nid={id}",
            'sms_customer_text_template' => '{name} عزیز، درخواست مشاوره شما در لایکودیزاین با کد پیگیری #{id} ثبت شد. همکاران ما به‌زودی با شما تماس می‌گیرند.',

            // --- Tab 6: Patch Updater ---
            'patch_auto_backup' => true,
            'patch_require_signature' => false,
            'patch_manifest_url' => 'https://github.com/amolnovin/laico-theme/raw/main/releases/manifest.json',
            'patch_public_key' => '',
            'patch_hmac_secret' => '',
        ];
    }

    /**
     * Get all options merged with defaults.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $saved = get_option(self::OPTION_KEY, []);
        if (! is_array($saved)) {
            $saved = [];
        }

        self::$cache = array_merge(self::defaults(), $saved);
        return self::$cache;
    }

    /**
     * 1.5.5 — the site is bilingual (FA / EN) by default again.
     *
     * Settings saved by 1.4.1 – 1.5.4 contain `enable_english = false` simply because that was the default
     * back then, so the new default alone would not bring the language switcher back. This turns the English
     * section on once; afterwards the checkbox in Theme Settings decides and is never overridden again.
     */
    public static function ensureBilingualDefault(): void
    {
        if (get_option(self::BILINGUAL_FLAG) === '1') {
            return;
        }

        $saved = get_option(self::OPTION_KEY, []);
        if (is_array($saved) && array_key_exists('enable_english', $saved) && empty($saved['enable_english'])) {
            $saved['enable_english'] = true;
            $saved['enable_lang_switcher'] = true;
            update_option(self::OPTION_KEY, $saved, true);
            self::$cache = null;
        }

        update_option(self::BILINGUAL_FLAG, '1', true);
    }

    /**
     * Whether English language support & FA/EN switcher are enabled in Theme Settings.
     */
    public static function isEnglishEnabled(): bool
    {
        $all = self::all();
        return ! empty($all['enable_english']);
    }

    /**
     * Retrieve a single option key (with compatibility aliases).
     */
    public static function get(string $key, mixed $fallback = null): mixed
    {
        $aliases = [
            'default_lang' => 'default_language',
            'enable_bilingual' => 'enable_english',
            'enable_lang_switcher' => 'enable_english',
            'phone_raw' => 'phone_primary_tel',
            'phone_display_fa' => 'phone_primary',
            'address_full_fa' => 'address_fa',
            'address_full_en' => 'address_en',
            'work_hours_fa' => 'hours_fa',
            'work_hours_en' => 'hours_en',
            'social_whatsapp' => 'whatsapp_url',
            'social_telegram' => 'telegram_url',
            'social_instagram' => 'instagram_url',
            'social_youtube' => 'youtube_url',
            'seo_default_description' => 'seo_home_description_fa',
            'seo_head_scripts' => 'seo_head_code',
            'seo_footer_scripts' => 'seo_footer_code',
            'enable_builtin_seo' => 'seo_enable_builtin',
            'enable_schema_jsonld' => 'seo_schema_org',
            'redirect_legacy_html' => 'seo_redirect_legacy_html',
        ];

        $resolvedKey = $aliases[$key] ?? $key;
        $all = self::all();

        if (array_key_exists($resolvedKey, $all)) {
            return $all[$resolvedKey];
        }

        return $fallback;
    }

    /**
     * Sanitize and update a subset (or full set) of theme options.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function update(array $input): array
    {
        $defaults = self::defaults();
        $clean = self::all();

        $urlFields = [
            'logo_url', 'favicon_url', 'whatsapp_url', 'telegram_url',
            'instagram_url', 'youtube_url', 'map_link_url', 'map_embed_url',
            'seo_default_og_image', 'patch_manifest_url',
        ];

        $textareaFields = [
            'home_lede_fa', 'home_lede_en', 'seo_home_description_fa',
            'seo_home_description_en', 'form_success_body_fa', 'form_success_body_en',
            'sms_admin_pattern_vars', 'sms_admin_text_template',
            'sms_customer_pattern_vars', 'sms_customer_text_template',
        ];

        $richInlineFields = [
            'footer_tagline_fa', 'footer_tagline_en',
            'footer_copy_fa', 'footer_copy_en',
            'footer_credit_fa', 'footer_credit_en',
        ];

        $codeFields = ['seo_head_code', 'seo_footer_code'];

        foreach ($input as $key => $rawVal) {
            if (! array_key_exists($key, $defaults)) {
                continue;
            }

            $defaultType = gettype($defaults[$key]);

            if ($defaultType === 'boolean') {
                $clean[$key] = (bool) $rawVal && $rawVal !== '0' && $rawVal !== 'false';
                continue;
            }

            if ($defaultType === 'integer') {
                $clean[$key] = max(0, (int) $rawVal);
                continue;
            }

            if ($key === 'default_language') {
                $clean[$key] = in_array($rawVal, ['fa', 'en'], true) ? (string) $rawVal : 'fa';
            } elseif (in_array($key, ['sms_admin_mode', 'sms_customer_mode'], true)) {
                $clean[$key] = in_array($rawVal, ['pattern', 'text'], true) ? (string) $rawVal : 'pattern';
            } elseif ($key === 'brand_color') {
                $clean[$key] = sanitize_hex_color((string) $rawVal) ?: '#0b1d33';
            } elseif (in_array($key, ['email', 'form_notify_email'], true)) {
                $trimmed = trim((string) $rawVal);
                $clean[$key] = $trimmed === '' ? '' : sanitize_email($trimmed);
            } elseif (in_array($key, $urlFields, true)) {
                $trimmed = trim((string) $rawVal);
                $clean[$key] = ($trimmed === '' || $trimmed === '#') ? '' : esc_url_raw($trimmed);
            } elseif (in_array($key, $richInlineFields, true)) {
                $clean[$key] = trim(wp_kses((string) $rawVal, \App\Support\Bilingual::allowedInlineHtml()));
            } elseif (in_array($key, $textareaFields, true)) {
                $clean[$key] = sanitize_textarea_field((string) $rawVal);
            } elseif (in_array($key, $codeFields, true)) {
                if (current_user_can('unfiltered_html')) {
                    $clean[$key] = (string) $rawVal;
                }
            } else {
                $clean[$key] = sanitize_text_field((string) $rawVal);
            }
        }

        if (array_key_exists('sms_ippanel_token', $input) && trim((string) $clean['sms_ippanel_token']) !== '') {
            update_option(IppanelSmsService::tokenOptionName(), trim((string) $clean['sms_ippanel_token']));
        }

        if (array_key_exists('enable_english', $input)) {
            $clean['enable_lang_switcher'] = (bool) $clean['enable_english'];
        } elseif (array_key_exists('enable_lang_switcher', $input)) {
            $clean['enable_english'] = (bool) $clean['enable_lang_switcher'];
        }

        if (! $clean['enable_english']) {
            $clean['default_language'] = 'fa';
        }

        if ($clean['form_rate_limit_max'] < 1) {
            $clean['form_rate_limit_max'] = 5;
        }

        update_option(self::OPTION_KEY, $clean, true);
        self::$cache = $clean;

        return $clean;
    }

    /**
     * Alias for `update()`.
     *
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    public static function save(array $raw): array
    {
        return self::update($raw);
    }

    /**
     * Reset all theme options back to factory defaults.
     */
    public static function reset(): void
    {
        $defaults = self::defaults();
        update_option(self::OPTION_KEY, $defaults, true);
        self::$cache = $defaults;
    }

    public static function flushCache(): void
    {
        self::$cache = null;
    }
}
