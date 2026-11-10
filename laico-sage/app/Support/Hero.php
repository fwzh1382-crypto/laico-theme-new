<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\ThemeOptions;

/**
 * Resolves Hero background image & copy for the 6 main pages
 * (Home, Services, Projects, Journal, About, Contact) and inner pages.
 *
 * Strictly pulls the Hero image from each WordPress Page's Featured Image
 * (`get_post_thumbnail_id`), falling back to theme defaults only if unset.
 */
final class Hero
{
    /**
     * Default configuration for each page's Hero section.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            'home' => [
                'fallback_image' => 'images/hero-main.webp',
                'width' => 1080,
                'height' => 900,
                'kicker_fa' => (string) ThemeOptions::get('home_kicker_fa', 'معماری داخلی • سیستم آشپزخانه لوکس'),
                'kicker_en' => self::englishOption('home_kicker_en', 'Interior architecture • Luxury kitchen system'),
                'title_fa' => (string) ThemeOptions::get('home_title_fa', 'لایکو دیزاین، فراتر از طراحی'),
                'title_en' => self::englishOption('home_title_en', 'Laico Design, beyond design'),
                'lede_fa' => (string) ThemeOptions::get('home_lede_fa', 'لایکو دیزاین؛ طراحی و اجرای آشپزخانه، پذیرایی و اتاق، حمام و فضاهای کافه-رستوران با زبان مینیمال و متریال صادق.'),
                'lede_en' => self::englishOption('home_lede_en', 'Laico Design plans and builds kitchens, living rooms, baths, and café spaces in a minimal language, with honest materials.'),
                'target' => 'collections',
                'is_page_variant' => false,
                'hint' => true,
            ],
            'services' => [
                'fallback_image' => 'images/hero-services.webp',
                'width' => 1376,
                'height' => 768,
                'kicker_fa' => 'خدمات',
                'kicker_en' => 'Services',
                'title_fa' => 'خدمات طراحی لایکودیزاین',
                'title_en' => 'Laico Design Services',
                'lede_fa' => 'طراحی فضاهای کافه و رستوران، تفریحی، درمانی، باشگاهی و مسکونی با یک تیم یکپارچه.',
                'lede_en' => 'Cafés, restaurants, leisure, clinical and sports spaces — designed and built by one team.',
                'target' => 'svc-list',
                'is_page_variant' => true,
                'hint' => true,
            ],
            'projects' => [
                'fallback_image' => 'images/hero-projects.webp',
                'width' => 1376,
                'height' => 768,
                'kicker_fa' => 'آرشیو پروژه‌ها',
                'kicker_en' => 'Project archive',
                'title_fa' => 'همه پروژه‌ها، یک استاندارد ساخت',
                'title_en' => 'All projects, one standard of making',
                'lede_fa' => 'مجموعهٔ کامل پروژه‌های اجراشدهٔ لایکو دیزاین در فضاهای مسکونی، تجاری و کافه-رستوران.',
                'lede_en' => 'The complete archive of Laico Design projects in residential, commercial and café-restaurant spaces.',
                'target' => 'prj-list',
                'is_page_variant' => true,
                'hint' => true,
            ],
            'journal' => [
                'fallback_image' => 'images/hero-journal.webp',
                'width' => 1376,
                'height' => 768,
                'kicker_fa' => 'مجله لایکو',
                'kicker_en' => 'Laico Journal',
                'title_fa' => 'یادداشت‌های ساخت',
                'title_en' => 'Notes on making',
                'lede_fa' => 'دربارهٔ متریال، نور، پلان و جزئیاتی که یک فضا را ماندگار می‌کنند.',
                'lede_en' => 'On materials, light, planning and the details that make a space last.',
                'target' => 'journal',
                'is_page_variant' => true,
                'hint' => true,
            ],
            'about' => [
                'fallback_image' => 'images/hero-about.webp',
                'width' => 1376,
                'height' => 768,
                'kicker_fa' => 'درباره ما',
                'kicker_en' => 'About us',
                'title_fa' => 'درباره لایکودیزاین، از ایده تا اجرا',
                'title_en' => 'About Laico Design, from idea to build',
                'lede_fa' => 'استودیوی معماری داخلی و سازندهٔ سیستم آشپزخانه؛ طراحی و اجرا در یک تیم.',
                'lede_en' => 'An interior architecture studio and kitchen-system maker; design and build in one team.',
                'target' => 'about-story',
                'is_page_variant' => true,
                'hint' => true,
            ],
            'contact' => [
                'fallback_image' => 'images/hero-contact.webp',
                'width' => 1376,
                'height' => 768,
                'kicker_fa' => 'تماس با ما',
                'kicker_en' => 'Contact',
                'title_fa' => 'بیایید دربارهٔ فضای شما صحبت کنیم',
                'title_en' => "Let's talk about your space",
                'lede_fa' => 'برای مشاوره، بازدید شو روم یا شروع پروژه با ما در ارتباط باشید.',
                'lede_en' => 'Get in touch for a consultation, a showroom visit or to start a project.',
                'target' => 'contact-map',
                'is_page_variant' => true,
                'hint' => false,
            ],
        ];
    }

    /**
     * Read an English text option from Theme Settings. A field that was saved empty falls back
     * to the built-in English copy, so the English version never shows the Persian text instead.
     */
    private static function englishOption(string $key, string $builtin): string
    {
        $value = trim((string) ThemeOptions::get($key, $builtin));

        return $value !== '' ? $value : $builtin;
    }

    /**
     * Find the WordPress Page ID associated with a main section
     * (`home`, `services`, `projects`, `journal`, `about`, `contact`, etc.).
     */
    public static function findSectionPageId(string $section): int
    {
        if ($section === 'home') {
            $frontId = (int) get_option('page_on_front');
            if ($frontId > 0) {
                return $frontId;
            }
            if (is_front_page() && is_singular('page')) {
                return (int) get_queried_object_id();
            }
            return 0;
        }

        if ($section === 'journal') {
            $postsPage = (int) get_option('page_for_posts');
            if ($postsPage > 0) {
                return $postsPage;
            }
        }

        $optionKey = $section . '_page_id';
        $configured = (int) ThemeOptions::get($optionKey, 0);
        if ($configured > 0 && get_post_status($configured) === 'publish') {
            return $configured;
        }

        // Check if current queried object is already a matching page
        if (is_singular('page')) {
            $queriedId = (int) get_queried_object_id();
            if ($queriedId > 0) {
                $slug = (string) get_post_field('post_name', $queriedId);
                $tpl = (string) get_page_template_slug($queriedId);
                if ($slug === $section || str_contains($tpl, "template-{$section}")) {
                    return $queriedId;
                }
            }
        }

        // Lookup by Blade template name
        $templates = [
            "template-{$section}.blade.php",
            "views/template-{$section}.blade.php",
            "resources/views/template-{$section}.blade.php",
        ];
        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => '_wp_page_template',
                    'value' => $templates,
                    'compare' => 'IN',
                ],
            ],
        ]);
        if (! empty($pages[0])) {
            return (int) $pages[0];
        }

        // Lookup by slug (e.g. 'services', 'projects', 'journal', 'about', 'contact')
        $bySlug = get_page_by_path($section);
        if ($bySlug instanceof \WP_Post && $bySlug->post_status === 'publish') {
            return (int) $bySlug->ID;
        }

        return 0;
    }

    /**
     * Resolve an attachment ID to its untouched original full-resolution URL and dimensions
     * (bypassing any WordPress `-scaled` copy or intermediate sub-sizes so rendered quality
     * always matches original upload dimensions).
     *
     * @return array{url: string, width: int, height: int, alt: string}|null
     */
    public static function resolveOriginalAttachment(int $attachmentId): ?array
    {
        if ($attachmentId <= 0) {
            return null;
        }

        $full = wp_get_attachment_image_src($attachmentId, 'full');
        if (! is_array($full) || empty($full[0])) {
            return null;
        }

        $url = (string) $full[0];
        $width = (int) ($full[1] ?? 0);
        $height = (int) ($full[2] ?? 0);

        if (function_exists('wp_get_original_image_url')) {
            $origUrl = (string) (wp_get_original_image_url($attachmentId) ?: '');
            if ($origUrl !== '' && $origUrl !== $url) {
                $url = $origUrl;
                if (function_exists('wp_get_original_image_path')) {
                    $origPath = wp_get_original_image_path($attachmentId);
                    if (is_string($origPath) && is_file($origPath)) {
                        $dims = @getimagesize($origPath);
                        if (is_array($dims) && ! empty($dims[0]) && ! empty($dims[1])) {
                            $width = (int) $dims[0];
                            $height = (int) $dims[1];
                        }
                    }
                }
            }
        }

        $alt = trim((string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true));

        return [
            'url' => $url,
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
        ];
    }

    /**
     * Resolve full Hero payload (Featured Image URL, srcset, sizes, dimensions, alt, and bilingual texts).
     *
     * @return array<string, mixed>
     */
    public static function resolve(string $context, ?int $explicitPageId = null): array
    {
        $allDefaults = self::defaults();
        $cfg = $allDefaults[$context] ?? $allDefaults['services'];

        $pageId = $explicitPageId && $explicitPageId > 0
            ? $explicitPageId
            : self::findSectionPageId($context);

        $imageUrl = laico_asset((string) $cfg['fallback_image']);
        $width = (int) $cfg['width'];
        $height = (int) $cfg['height'];
        $srcset = '';
        $sizes = '100vw';
        $alt = '';
        $fromFeaturedImage = false;

        // 1. Primary source: Featured Image (تصویر شاخص برگه) at untouched original dimensions
        if ($pageId > 0 && has_post_thumbnail($pageId)) {
            $thumbId = (int) get_post_thumbnail_id($pageId);
            $orig = self::resolveOriginalAttachment($thumbId);
            if ($orig !== null && $orig['url'] !== '') {
                $imageUrl = $orig['url'];
                $width = $orig['width'] > 0 ? $orig['width'] : $width;
                $height = $orig['height'] > 0 ? $orig['height'] : $height;
                $alt = $orig['alt'];
                $fromFeaturedImage = true;
            }
        } elseif ($context === 'home') {
            // Optional fallback image set in Theme Settings if Front Page has no Featured Image yet
            $customFallback = (string) ThemeOptions::get('home_hero_image', '');
            if ($customFallback !== '') {
                $imageUrl = $customFallback;
            }
        }

        // 2. Allow per-page Hero text overrides via Page Meta Box (optional)
        $kickerFa = (string) $cfg['kicker_fa'];
        $kickerEn = (string) $cfg['kicker_en'];
        $titleFa = (string) $cfg['title_fa'];
        $titleEn = (string) $cfg['title_en'];
        $ledeFa = (string) $cfg['lede_fa'];
        $ledeEn = (string) $cfg['lede_en'];

        if ($pageId > 0) {
            $metaKickerFa = trim((string) get_post_meta($pageId, '_laico_hero_kicker_fa', true));
            $metaKickerEn = trim((string) get_post_meta($pageId, '_laico_hero_kicker_en', true));
            $metaTitleFa = trim((string) get_post_meta($pageId, '_laico_hero_title_fa', true));
            $metaTitleEn = trim((string) get_post_meta($pageId, '_laico_hero_title_en', true));
            $metaLedeFa = trim((string) get_post_meta($pageId, '_laico_hero_lede_fa', true));
            $metaLedeEn = trim((string) get_post_meta($pageId, '_laico_hero_lede_en', true));

            if ($metaKickerFa !== '') {
                $kickerFa = $metaKickerFa;
            }
            if ($metaKickerEn !== '') {
                $kickerEn = $metaKickerEn;
            }
            if ($metaTitleFa !== '') {
                $titleFa = $metaTitleFa;
            }
            if ($metaTitleEn !== '') {
                $titleEn = $metaTitleEn;
            }
            if ($metaLedeFa !== '') {
                $ledeFa = $metaLedeFa;
            }
            if ($metaLedeEn !== '') {
                $ledeEn = $metaLedeEn;
            }
        }

        return [
            'page_id' => $pageId,
            'from_featured_image' => $fromFeaturedImage,
            'url' => $imageUrl,
            'image_url' => $imageUrl,
            'srcset' => $srcset,
            'sizes' => $sizes,
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
            'kicker_fa' => $kickerFa,
            'kicker_en' => $kickerEn,
            'title_fa' => $titleFa,
            'title_en' => $titleEn,
            'lede_fa' => $ledeFa,
            'lede_en' => $ledeEn,
            'target' => (string) $cfg['target'],
            'is_page_variant' => (bool) $cfg['is_page_variant'],
            'hint' => (bool) $cfg['hint'],
        ];
    }
}
