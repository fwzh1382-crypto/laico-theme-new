<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\PostTypes\ProjectPostType;
use App\Services\ThemeOptions;
use App\Support\Hero;
use App\Support\ViteAssets;
use Roots\Acorn\View\Composer;

/**
 * Global View Composer shared with `layouts.app`, `sections.header`, `sections.footer`, and all views.
 */
final class App extends Composer
{
    /** @var array<int, string> */
    protected static $views = ['*'];

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $opt = ThemeOptions::all();
        $logoUrl = trim((string) ($opt['logo_url'] ?? ''));
        if ($logoUrl === '') {
            $logoUrl = ViteAssets::assetUrl('images/logo2.webp');
        }

        return [
            'siteName' => get_bloginfo('name', 'display') ?: 'لایکو دیزاین',
            'themeOpt' => $opt,
            'logoUrl' => $logoUrl,
            'currentSection' => self::detectCurrentSection(),
            'pageTitles' => self::resolveBilingualDocumentTitles(),
            'navUrls' => self::resolveCoreUrls(),
        ];
    }

    public static function detectCurrentSection(): string
    {
        if (is_front_page()) {
            return 'home';
        }
        if (is_post_type_archive(ProjectPostType::POST_TYPE)
            || is_singular(ProjectPostType::POST_TYPE)
            || is_tax([ProjectPostType::TAX_CATEGORY, ProjectPostType::TAX_SPACE, ProjectPostType::TAX_CITY])
            || is_page_template('template-projects.blade.php')
            || is_page('projects')) {
            return 'projects';
        }
        if (is_home() || is_singular('post') || is_category() || is_tag() || is_page('journal')) {
            return 'journal';
        }
        if (is_page_template('template-services.blade.php') || is_page('services')) {
            return 'services';
        }
        if (is_page_template('template-about.blade.php') || is_page('about')) {
            return 'about';
        }
        if (is_page_template('template-contact.blade.php') || is_page('contact')) {
            return 'contact';
        }
        if (is_singular('page')) {
            $post = get_queried_object();
            if ($post instanceof \WP_Post) {
                return (string) $post->post_name;
            }
        }
        return 'page';
    }

    /**
     * Resolve clean WordPress URLs for all core pages & subpages.
     *
     * @return array<string, string>
     */
    public static function resolveCoreUrls(): array
    {
        $slugs = [
            'services', 'projects', 'journal', 'about', 'contact',
            'kitchen', 'living', 'bath', 'cafe', 'studio',
            'systems', 'process', 'materials', 'showroom', 'privacy', 'cookies',
        ];

        $urls = [
            'home' => home_url('/'),
        ];

        foreach ($slugs as $slug) {
            if ($slug === 'projects') {
                $pageId = Hero::findSectionPageId('projects');
                $urls['projects'] = $pageId > 0
                    ? (string) get_permalink($pageId)
                    : ((string) get_post_type_archive_link(ProjectPostType::POST_TYPE) ?: home_url('/projects/'));
                continue;
            }

            if ($slug === 'journal') {
                $postsPageId = (int) get_option('page_for_posts');
                if ($postsPageId > 0) {
                    $urls['journal'] = (string) get_permalink($postsPageId);
                } else {
                    $jPage = get_page_by_path('journal', OBJECT, 'page');
                    $urls['journal'] = $jPage instanceof \WP_Post ? (string) get_permalink($jPage) : home_url('/journal/');
                }
                continue;
            }

            $page = get_page_by_path($slug, OBJECT, 'page');
            $urls[$slug] = $page instanceof \WP_Post ? (string) get_permalink($page) : home_url('/' . $slug . '/');
        }

        return $urls;
    }

    /**
     * Provide `data-title-fa` and `data-title-en` for client-side FA/EN title toggling.
     *
     * @return array{fa: string, en: string}
     */
    public static function resolveBilingualDocumentTitles(): array
    {
        if (is_front_page()) {
            return [
                'fa' => 'لایکو دیزاین — معماری داخلی و سیستم آشپزخانه',
                'en' => 'Laico Design — Interior Architecture & Kitchen Systems',
            ];
        }

        if (is_post_type_archive(ProjectPostType::POST_TYPE) || is_page_template('template-projects.blade.php') || is_page('projects')) {
            return [
                'fa' => 'همه پروژه‌ها، یک استاندارد ساخت — لایکو دیزاین',
                'en' => 'All Projects — Laico Design',
            ];
        }

        if (is_home() || is_page('journal')) {
            return [
                'fa' => 'یادداشت‌های ساخت — مجله لایکو',
                'en' => 'Notes on making — Laico Journal',
            ];
        }

        if (is_singular()) {
            $postId = (int) get_queried_object_id();
            $faTitle = get_the_title($postId);
            $enTitle = trim((string) get_post_meta($postId, '_laico_title_en', true));
            if ($enTitle === '' && is_singular('page')) {
                $enTitle = self::resolveEnglishPageTitle($postId);
            }
            if ($enTitle === '') {
                $enTitle = $faTitle;
            }
            $suffixFa = is_singular('post') ? ' — مجله لایکو' : ' — لایکو دیزاین';
            $suffixEn = is_singular('post') ? ' — Laico Journal' : ' — Laico Design';
            return [
                'fa' => $faTitle . $suffixFa,
                'en' => $enTitle . $suffixEn,
            ];
        }

        $docTitle = wp_get_document_title();
        return ['fa' => $docTitle, 'en' => $docTitle];
    }

    /**
     * English tab title of a Page: the English H1 typed in the page's Hero meta box, otherwise the
     * built-in English name of the standard Laico pages (matched by template or slug).
     * Returns an empty string when neither exists (the caller then keeps the Persian title).
     */
    private static function resolveEnglishPageTitle(int $pageId): string
    {
        $custom = trim((string) get_post_meta($pageId, '_laico_hero_title_en', true));
        if ($custom !== '') {
            return $custom;
        }

        $names = [
            'services' => 'Services',
            'about' => 'About',
            'contact' => 'Contact',
            'showroom' => 'Amol showroom',
            'kitchen' => 'Kitchen',
            'living' => 'Living',
            'bath' => 'Bath',
            'cafe' => 'Café & restaurant',
            'studio' => 'Studio',
            'systems' => 'Systems & materials',
            'process' => 'Process',
            'materials' => 'Materials',
            'privacy' => 'Privacy',
            'cookies' => 'Cookies',
        ];

        return $names[self::detectCurrentSection()] ?? '';
    }
}
