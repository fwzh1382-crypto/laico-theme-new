<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\PostTypes\ProjectPostType;
use App\Support\Hero;
use App\Support\Jalali;
use App\Support\ViteAssets;
use Roots\Acorn\View\Composer;

/**
 * View Composer for Projects Archive (`archive-laico_project`, `template-projects`)
 * and Single Project Detail (`single-laico_project`).
 */
final class Projects extends Composer
{
    /** @var array<int, string> */
    protected static $views = [
        'archive-laico_project',
        'template-projects',
        'partials.projects-archive',
        'single-laico_project',
    ];

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        if (is_singular(ProjectPostType::POST_TYPE)) {
            $post = get_queried_object();
            if ($post instanceof \WP_Post) {
                return [
                    'project' => self::formatSingleProject($post),
                    'relatedProjects' => self::queryRelatedProjects($post),
                ];
            }
        }

        $initialFilters = [
            'category' => 'all',
            'space' => 'all',
            'city' => 'all',
        ];
        if (is_tax(ProjectPostType::TAX_CATEGORY)) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $initialFilters['category'] = $term->slug;
            }
        } elseif (is_tax(ProjectPostType::TAX_SPACE)) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $initialFilters['space'] = $term->slug;
            }
        } elseif (is_tax(ProjectPostType::TAX_CITY)) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $initialFilters['city'] = $term->slug;
            }
        }

        if (! empty($_GET['category']) && is_string($_GET['category'])) {
            $reqCat = sanitize_title(wp_unslash($_GET['category']));
            if ($reqCat !== '') {
                $initialFilters['category'] = $reqCat;
            }
        }
        if (! empty($_GET['space']) && is_string($_GET['space'])) {
            $reqSpace = sanitize_title(wp_unslash($_GET['space']));
            if ($reqSpace !== '') {
                $initialFilters['space'] = $reqSpace;
            }
        }
        if (! empty($_GET['city']) && is_string($_GET['city'])) {
            $reqCity = sanitize_title(wp_unslash($_GET['city']));
            if ($reqCity !== '') {
                $initialFilters['city'] = $reqCity;
            }
        }

        return [
            'hero' => Hero::resolve('projects'),
            'projectsList' => self::queryAllProjects(),
            'filterTerms' => self::collectFilterTerms(),
            'initialFilters' => $initialFilters,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function queryAllProjects(): array
    {
        $posts = get_posts([
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        ]);

        return array_map([self::class, 'formatProjectCard'], $posts);
    }

    /**
     * Format a `laico_project` post for archive cards & home mosaic tiles.
     *
     * @return array<string, mixed>
     */
    public static function formatProjectCard(\WP_Post $post): array
    {
        $id = (int) $post->ID;
        $titleFa = get_the_title($id);
        $titleEn = trim((string) get_post_meta($id, '_laico_title_en', true)) ?: $titleFa;

        $catTerm = self::firstTerm($id, ProjectPostType::TAX_CATEGORY);
        $spaceTerm = self::firstTerm($id, ProjectPostType::TAX_SPACE);
        $cityTerm = self::firstTerm($id, ProjectPostType::TAX_CITY);
        $allCats = self::allTermSlugs($id, ProjectPostType::TAX_CATEGORY);
        $allSpaces = self::allTermSlugs($id, ProjectPostType::TAX_SPACE);

        $year = trim((string) get_post_meta($id, '_laico_year', true)) ?: '1404';
        $yearEn = trim((string) get_post_meta($id, '_laico_year_en', true)) ?: Jalali::approximateGregorianYear($year);
        $area = (int) get_post_meta($id, '_laico_area', true);
        $sizeBucket = ProjectPostType::sizeBucket($area);

        $placeFa = trim((string) get_post_meta($id, '_laico_location_fa', true)) ?: $cityTerm['fa'];
        $placeEn = trim((string) get_post_meta($id, '_laico_location_en', true)) ?: $cityTerm['en'];

        $ledeFa = has_excerpt($id) ? get_the_excerpt($id) : wp_trim_words(wp_strip_all_tags($post->post_content), 22, '…');
        $ledeEn = trim((string) get_post_meta($id, '_laico_lede_en', true)) ?: $ledeFa;

        $image = self::resolveCoverImage($id, $titleFa);

        return [
            'id' => $id,
            'slug' => $post->post_name,
            'url' => (string) get_permalink($id),
            'title_fa' => $titleFa,
            'title_en' => $titleEn,
            'lede_fa' => $ledeFa,
            'lede_en' => $ledeEn,
            'category_slug' => $catTerm['slug'] ?: 'residential',
            'category_slugs' => ! empty($allCats) ? implode(' ', $allCats) : ($catTerm['slug'] ?: 'residential'),
            'category_fa' => $catTerm['fa'] ?: 'مسکونی',
            'category_en' => $catTerm['en'] ?: 'Residential',
            'space_slug' => $spaceTerm['slug'] ?: 'kitchen',
            'space_slugs' => ! empty($allSpaces) ? implode(' ', $allSpaces) : ($spaceTerm['slug'] ?: 'kitchen'),
            'space_fa' => $spaceTerm['fa'] ?: 'طراحی داخلی',
            'space_en' => $spaceTerm['en'] ?: 'Interior',
            'city_slug' => $cityTerm['slug'] ?: 'amol',
            'city_fa' => $cityTerm['fa'] ?: 'آمل',
            'city_en' => $cityTerm['en'] ?: 'Amol',
            'place_fa' => $placeFa,
            'place_en' => $placeEn,
            'year' => $year,
            'year_fa' => Jalali::toPersianDigits($year),
            'year_en' => $yearEn,
            'area' => $area,
            'area_fa' => $area > 0 ? Jalali::toPersianDigits($area) . ' متر مربع' : '',
            'area_en' => $area > 0 ? $area . ' m²' : '',
            'size_bucket' => $sizeBucket,
            'image' => $image,
        ];
    }

    /**
     * Format full single-project detail payload matching `project-*.html`.
     *
     * @return array<string, mixed>
     */
    public static function formatSingleProject(\WP_Post $post): array
    {
        $card = self::formatProjectCard($post);
        $id = (int) $post->ID;

        $clientFa = trim((string) get_post_meta($id, '_laico_client_fa', true)) ?: 'کارفرمای خصوصی';
        $clientEn = trim((string) get_post_meta($id, '_laico_client_en', true)) ?: 'Private Client';
        $durationFa = trim((string) get_post_meta($id, '_laico_duration_fa', true)) ?: '۶ ماه';
        $durationEn = trim((string) get_post_meta($id, '_laico_duration_en', true)) ?: '6 months';

        $contentFa = apply_filters('the_content', $post->post_content);
        $rawEn = trim((string) get_post_meta($id, '_laico_content_en', true));
        $contentEn = $rawEn !== '' ? wpautop($rawEn) : $contentFa;

        // Resolve gallery attachments (or fallback direct URLs) at full original dimensions
        $gallery = [];
        $idsCsv = (string) get_post_meta($id, '_laico_gallery_ids', true);
        $attIds = array_values(array_filter(array_map('intval', explode(',', $idsCsv))));
        foreach ($attIds as $attId) {
            $orig = Hero::resolveOriginalAttachment($attId);
            if ($orig !== null && $orig['url'] !== '') {
                $gallery[] = [
                    'url' => $orig['url'],
                    'full_url' => $orig['url'],
                    'width' => (int) ($orig['width'] ?: 1200),
                    'height' => (int) ($orig['height'] ?: 900),
                    'srcset' => '',
                    'alt_fa' => $orig['alt'] !== '' ? $orig['alt'] : $card['title_fa'],
                    'alt_en' => $card['title_en'],
                ];
            }
        }

        if (empty($gallery)) {
            $rawUrls = (string) get_post_meta($id, '_laico_gallery_urls', true);
            foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawUrls) ?: [])) as $u) {
                $gallery[] = [
                    'url' => $u,
                    'full_url' => $u,
                    'width' => 1200,
                    'height' => 900,
                    'srcset' => '',
                    'alt_fa' => $card['title_fa'],
                    'alt_en' => $card['title_en'],
                ];
            }
        }

        // Resolve project sketches (hand drawings, plans, details): the lightbox opens the untouched original,
        // the Media Library caption becomes the label under each sketch ("فارسی | English" for both languages).
        // A project without sketches gets an empty list and the section is not rendered at all.
        $sketches = [];
        $sketchSized = 0;
        $sketchPortrait = 0;
        $sketchIdsCsv = (string) get_post_meta($id, '_laico_sketch_ids', true);
        $sketchIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $sketchIdsCsv)))));
        foreach ($sketchIds as $sketchId) {
            $orig = Hero::resolveOriginalAttachment($sketchId);
            if ($orig !== null && $orig['url'] !== '') {
                [$captionFa, $captionEn] = self::splitBilingualCaption((string) wp_get_attachment_caption($sketchId));
                if ($orig['width'] > 0 && $orig['height'] > 0) {
                    $sketchSized++;
                    if ($orig['height'] >= $orig['width'] * 1.15) {
                        $sketchPortrait++;
                    }
                }
                $sketches[] = [
                    'id' => $sketchId,
                    'url' => $orig['url'],
                    'full_url' => $orig['url'],
                    'width' => (int) ($orig['width'] ?: 1200),
                    'height' => (int) ($orig['height'] ?: 900),
                    'alt_fa' => $orig['alt'] !== '' ? $orig['alt'] : $card['title_fa'],
                    'alt_en' => $card['title_en'],
                    'caption_fa' => $captionFa,
                    'caption_en' => $captionEn,
                ];
            }
        }

        if (empty($sketches)) {
            $rawSketchUrls = (string) get_post_meta($id, '_laico_sketch_urls', true);
            foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawSketchUrls) ?: [])) as $u) {
                $sketches[] = [
                    'id' => 0,
                    'url' => $u,
                    'full_url' => $u,
                    'width' => 1200,
                    'height' => 900,
                    'alt_fa' => $card['title_fa'],
                    'alt_en' => $card['title_en'],
                    'caption_fa' => '',
                    'caption_en' => '',
                ];
            }
        }

        // Upright sheets (A-series drawings) use the four-column "portrait" grid; a long set starts with two rows
        // and a "show all" button. In the grid a lighter sub-size is enough — never smaller than the cell needs.
        $sketchCount = count($sketches);
        $sketchLayout = ($sketchSized > 0 && $sketchPortrait * 2 > $sketchSized) ? 'portrait' : 'landscape';
        $sketchCollapsible = $sketchCount > ($sketchLayout === 'portrait' ? 8 : 6);
        $sketchThumbMin = self::sketchThumbMinWidth($sketchLayout, $sketchCount);
        foreach ($sketches as $sIdx => $sketch) {
            $thumb = $sketch['id'] > 0 ? self::attachmentSubSize((int) $sketch['id'], $sketchThumbMin) : null;
            if ($thumb !== null) {
                $sketches[$sIdx]['url'] = $thumb['url'];
                $sketches[$sIdx]['width'] = $thumb['width'];
                $sketches[$sIdx]['height'] = $thumb['height'];
            }
        }

        $videoUrl = trim((string) get_post_meta($id, '_laico_video_url', true));
        $videoPoster = trim((string) get_post_meta($id, '_laico_video_poster', true)) ?: $card['image']['url'];

        return array_merge($card, [
            'client_fa' => $clientFa,
            'client_en' => $clientEn,
            'duration_fa' => $durationFa,
            'duration_en' => $durationEn,
            'content_fa' => $contentFa,
            'content_en' => $contentEn,
            'gallery' => $gallery,
            'sketches' => $sketches,
            'sketch_layout' => $sketchLayout,
            'sketch_collapsible' => $sketchCollapsible,
            'video_url' => $videoUrl,
            'video_poster' => $videoPoster,
        ]);
    }

    /**
     * Narrowest image (in pixels) that still looks crisp in one cell of the sketch grid: 1.5 × the widest cell,
     * i.e. sharper than needed on ordinary screens and still clean on high-density ones.
     * Mirrors the column counts of `.pd-sketches` / `.pd-sketches--portrait` in the stylesheet.
     */
    public static function sketchThumbMinWidth(string $layout, int $count): int
    {
        if ($layout === 'portrait') {
            $cellWidth = $count >= 4 ? 300 : ($count === 3 ? 400 : ($count === 2 ? 420 : 520));
        } else {
            $cellWidth = $count === 1 ? 860 : (($count === 2 || $count === 4) ? 610 : 400);
        }

        return (int) round($cellWidth * 1.5);
    }

    /**
     * Smallest uncropped sub-size of an attachment that is at least `$minWidth` pixels wide
     * (WordPress sub-sizes such as `medium_large`, or the ready-made thumbnail of a bundled sketch).
     * Returns null when the original itself has to be used.
     *
     * @return array{url: string, width: int, height: int}|null
     */
    public static function attachmentSubSize(int $attachmentId, int $minWidth): ?array
    {
        $meta = wp_get_attachment_metadata($attachmentId);
        if (! is_array($meta) || empty($meta['sizes']) || ! is_array($meta['sizes'])) {
            return null;
        }

        $fullWidth = (int) ($meta['width'] ?? 0);
        $fullHeight = (int) ($meta['height'] ?? 0);
        $fullUrl = (string) (wp_get_attachment_url($attachmentId) ?: '');
        $slash = strrpos($fullUrl, '/');
        if ($fullWidth <= 0 || $fullHeight <= 0 || $slash === false) {
            return null;
        }

        $ratio = $fullWidth / $fullHeight;
        $best = null;
        foreach ($meta['sizes'] as $size) {
            if (! is_array($size)) {
                continue;
            }

            $width = (int) ($size['width'] ?? 0);
            $height = (int) ($size['height'] ?? 0);
            $file = (string) ($size['file'] ?? '');
            if ($file === '' || $height <= 0 || $width < $minWidth || $width >= $fullWidth) {
                continue;
            }
            // Cropped sizes (square thumbnails, …) would cut the drawing.
            if (abs($width / $height - $ratio) > $ratio * 0.02) {
                continue;
            }
            if ($best === null || $width < $best['width']) {
                $best = [
                    'url' => substr($fullUrl, 0, $slash + 1) . $file,
                    'width' => $width,
                    'height' => $height,
                ];
            }
        }

        return $best;
    }

    /**
     * Split an optional "فارسی | English" caption into its two languages.
     * Without a separator the same text is used for both.
     *
     * @return array{0: string, 1: string}
     */
    public static function splitBilingualCaption(string $caption): array
    {
        $caption = trim(wp_strip_all_tags($caption));
        if ($caption === '') {
            return ['', ''];
        }

        $parts = array_map('trim', explode('|', $caption, 2));
        $fa = $parts[0];
        $en = $parts[1] ?? '';

        if ($fa === '') {
            $fa = $en;
        }

        return [$fa, $en !== '' ? $en : $fa];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function queryRelatedProjects(\WP_Post $current, int $limit = 2): array
    {
        $catTerm = self::firstTerm($current->ID, ProjectPostType::TAX_CATEGORY);

        $args = [
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => [$current->ID],
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if ($catTerm['slug'] !== '') {
            $args['tax_query'] = [[
                'taxonomy' => ProjectPostType::TAX_CATEGORY,
                'field' => 'slug',
                'terms' => [$catTerm['slug']],
            ]];
        }

        $related = get_posts($args);
        if (count($related) < $limit) {
            $exclude = array_merge([$current->ID], array_map(static fn (\WP_Post $p): int => (int) $p->ID, $related));
            $more = get_posts([
                'post_type' => ProjectPostType::POST_TYPE,
                'post_status' => 'publish',
                'posts_per_page' => $limit - count($related),
                'post__not_in' => $exclude,
            ]);
            $related = array_merge($related, $more);
        }

        return array_map([self::class, 'formatProjectCard'], array_slice($related, 0, $limit));
    }

    /**
     * Collect dynamic taxonomy terms & years for the Projects filter bar (`projects.blade.php`).
     *
     * @return array<string, array<int, array<string, string>>>
     */
    public static function collectFilterTerms(): array
    {
        $mapTerms = static function (string $tax, array $preferredSlugs = [], bool $hideEmpty = true): array {
            $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => $hideEmpty]);
            if (is_wp_error($terms) || ! is_array($terms)) {
                return [];
            }
            $out = [];
            foreach ($terms as $t) {
                $en = trim((string) get_term_meta($t->term_id, '_laico_name_en', true)) ?: ucfirst($t->slug);
                $out[] = [
                    'slug' => $t->slug,
                    'fa' => $t->name,
                    'en' => $en,
                ];
            }
            if (! empty($preferredSlugs)) {
                $rank = array_flip($preferredSlugs);
                usort($out, static function (array $a, array $b) use ($rank): int {
                    $ra = $rank[$a['slug']] ?? 999;
                    $rb = $rank[$b['slug']] ?? 999;
                    return $ra <=> $rb;
                });
            }
            return $out;
        };

        // Distinct years across published projects (ascending like projects.html: 1402, 1403, 1404)
        global $wpdb;
        $yearsRaw = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = 'publish' AND pm.meta_value <> ''
             ORDER BY pm.meta_value ASC",
            '_laico_year',
            ProjectPostType::POST_TYPE
        ));

        $years = [];
        foreach (($yearsRaw ?: ['1402', '1403', '1404']) as $yr) {
            $yrStr = (string) $yr;
            $years[] = [
                'value' => $yrStr,
                'fa' => Jalali::toPersianDigits($yrStr),
                'en' => Jalali::approximateGregorianYear($yrStr),
            ];
        }

        return [
            'categories' => $mapTerms(ProjectPostType::TAX_CATEGORY, ['residential', 'commercial'], true),
            'spaces' => $mapTerms(ProjectPostType::TAX_SPACE, ['kitchen', 'living', 'bath', 'office', 'hall', 'architecture', 'educational', 'sports'], false),
            'cities' => $mapTerms(ProjectPostType::TAX_CITY, ['amol', 'tehran', 'mazandaran', 'firouzkuh'], true),
            'years' => $years,
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function allTermSlugs(int $postId, string $taxonomy): array
    {
        $terms = get_the_terms($postId, $taxonomy);
        if (! is_array($terms)) {
            return [];
        }
        $slugs = [];
        foreach ($terms as $t) {
            if ($t instanceof \WP_Term && $t->slug !== '') {
                $slugs[] = $t->slug;
            }
        }
        return array_values(array_unique($slugs));
    }

    /**
     * @return array{slug: string, fa: string, en: string}
     */
    private static function firstTerm(int $postId, string $taxonomy): array
    {
        $terms = get_the_terms($postId, $taxonomy);
        if (is_array($terms) && ! empty($terms[0]) && $terms[0] instanceof \WP_Term) {
            $t = $terms[0];
            $en = trim((string) get_term_meta($t->term_id, '_laico_name_en', true)) ?: ucfirst($t->slug);
            return ['slug' => $t->slug, 'fa' => $t->name, 'en' => $en];
        }
        return ['slug' => '', 'fa' => '', 'en' => ''];
    }

    /**
     * @return array{url: string, srcset: string, width: int, height: int}
     */
    private static function resolveCoverImage(int $postId, string $fallbackAlt): array
    {
        $thumbId = (int) get_post_thumbnail_id($postId);
        if ($thumbId > 0) {
            $orig = Hero::resolveOriginalAttachment($thumbId);
            if ($orig !== null && $orig['url'] !== '') {
                return [
                    'url' => $orig['url'],
                    'srcset' => '',
                    'width' => (int) ($orig['width'] ?: 1200),
                    'height' => (int) ($orig['height'] ?: 900),
                ];
            }
        }

        return [
            'url' => ViteAssets::assetUrl('images/projects-card.webp'),
            'srcset' => '',
            'width' => 1200,
            'height' => 900,
        ];
    }
}
