<?php

declare(strict_types=1);

namespace App\Services;

use App\PostTypes\ProjectPostType;

/**
 * Ready-made sketch sets shipped inside the theme (`resources/sketches/<slug>/set.json`).
 *
 * A set is a folder of drawing sheets (full-size WebP + a ready thumbnail each) with FA / EN captions.
 * On the project edit screen a set is copied into the Media Library once and its sheets are added to
 * the "project sketches" list of that project — the project still has to be saved by the editor.
 * Nothing is attached to an existing project automatically.
 */
final class BundledSketches
{
    public const AJAX_ACTION = 'laico_import_sketch_set';

    /** Sub-size name the ready-made thumbnail is registered as in the attachment metadata. */
    public const THUMB_SIZE = 'medium_large';

    /** @var array<string, array{slug: string, title_fa: string, title_en: string, items: array<int, array<string, mixed>>}>|null */
    private static ?array $sets = null;

    public function register(): void
    {
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'handleAjaxImport']);
    }

    public static function baseDir(): string
    {
        return LAICO_THEME_DIR . '/resources/sketches';
    }

    /**
     * All valid sets found in the theme, keyed by slug. Items whose file is missing are dropped.
     *
     * @return array<string, array{slug: string, title_fa: string, title_en: string, items: array<int, array<string, mixed>>}>
     */
    public static function sets(): array
    {
        if (self::$sets !== null) {
            return self::$sets;
        }

        $sets = [];
        $base = self::baseDir();
        $entries = is_dir($base) ? (scandir($base) ?: []) : [];

        foreach ($entries as $slug) {
            if (preg_match('/^[a-z0-9][a-z0-9_-]*$/', (string) $slug) !== 1) {
                continue;
            }

            $dir = $base . '/' . $slug;
            $manifest = $dir . '/set.json';
            if (! is_file($manifest)) {
                continue;
            }

            $data = json_decode((string) file_get_contents($manifest), true);
            if (! is_array($data) || empty($data['items']) || ! is_array($data['items'])) {
                continue;
            }

            $items = [];
            foreach ($data['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $file = basename((string) ($item['file'] ?? ''));
                if ($file === '' || ! is_file($dir . '/' . $file)) {
                    continue;
                }

                $thumb = basename((string) ($item['thumb'] ?? ''));
                $thumbWidth = max(0, (int) ($item['thumb_width'] ?? 0));
                $thumbHeight = max(0, (int) ($item['thumb_height'] ?? 0));
                if ($thumb === '' || $thumb === $file || $thumbWidth === 0 || $thumbHeight === 0 || ! is_file($dir . '/' . $thumb)) {
                    $thumb = '';
                }

                $items[] = [
                    'file' => $file,
                    'width' => max(0, (int) ($item['width'] ?? 0)),
                    'height' => max(0, (int) ($item['height'] ?? 0)),
                    'thumb' => $thumb,
                    'thumb_width' => $thumbWidth,
                    'thumb_height' => $thumbHeight,
                    'fa' => trim((string) ($item['fa'] ?? '')),
                    'en' => trim((string) ($item['en'] ?? '')),
                ];
            }

            if ($items === []) {
                continue;
            }

            $titleFa = trim((string) ($data['title_fa'] ?? '')) ?: (string) $slug;
            $sets[(string) $slug] = [
                'slug' => (string) $slug,
                'title_fa' => $titleFa,
                'title_en' => trim((string) ($data['title_en'] ?? '')) ?: $titleFa,
                'items' => $items,
            ];
        }

        ksort($sets);
        self::$sets = $sets;

        return $sets;
    }

    /**
     * @return array{slug: string, title_fa: string, title_en: string, items: array<int, array<string, mixed>>}|null
     */
    public static function get(string $slug): ?array
    {
        return self::sets()[$slug] ?? null;
    }

    /**
     * Copy a set into the Media Library (each sheet only once) and return its attachment IDs in set order.
     *
     * @return array<int, int>
     */
    public static function import(string $slug, int $parentPostId = 0): array
    {
        $set = self::get($slug);
        if ($set === null) {
            return [];
        }

        $ids = [];
        foreach ($set['items'] as $index => $item) {
            $attachmentId = self::ensureAttachment($set, $item, (int) $index, $parentPostId);
            if ($attachmentId > 0) {
                $ids[] = $attachmentId;
            }
        }

        return $ids;
    }

    /**
     * Import a set and append its sheets to the sketches of a project (no duplicates, existing order kept).
     * Used by the demo importer; the edit screen adds the sheets to the form instead and saves with the post.
     *
     * @return int Number of sheets of the set that are now attached to the project.
     */
    public static function attachToProject(int $postId, string $slug): int
    {
        if ($postId <= 0) {
            return 0;
        }

        $ids = self::import($slug, $postId);
        if ($ids === []) {
            return 0;
        }

        $current = array_filter(
            array_map('intval', explode(',', (string) get_post_meta($postId, '_laico_sketch_ids', true))),
            static fn (int $id): bool => $id > 0
        );
        $merged = array_values(array_unique(array_merge($current, $ids)));
        update_post_meta($postId, '_laico_sketch_ids', implode(',', $merged));

        return count($ids);
    }

    /**
     * Small preview URL for the edit screen: the first real sub-size that exists, else the file itself
     * (imported sheets only have the ready-made thumbnail, no 150 px `thumbnail`).
     */
    public static function previewUrl(int $attachmentId): string
    {
        foreach (['thumbnail', 'medium', self::THUMB_SIZE] as $size) {
            $src = wp_get_attachment_image_src($attachmentId, $size);
            if (is_array($src) && ! empty($src[0]) && ! empty($src[3])) {
                return (string) $src[0];
            }
        }

        return (string) (wp_get_attachment_url($attachmentId) ?: '');
    }

    /**
     * admin-ajax: import a set for the project being edited and return the attachment IDs with preview URLs.
     */
    public function handleAjaxImport(): void
    {
        check_ajax_referer(self::AJAX_ACTION, 'nonce');

        $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $slug = isset($_POST['set']) ? sanitize_key(wp_unslash((string) $_POST['set'])) : '';

        if (
            $postId <= 0
            || get_post_type($postId) !== ProjectPostType::POST_TYPE
            || ! current_user_can('upload_files')
            || ! current_user_can('edit_post', $postId)
        ) {
            wp_send_json_error(['message' => 'اجازهٔ افزودن رسانه به این پروژه را ندارید.']);
        }

        $set = self::get($slug);
        if ($set === null) {
            wp_send_json_error(['message' => 'این مجموعهٔ اسکیس در قالب پیدا نشد.']);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $ids = self::import($slug, $postId);
        if ($ids === []) {
            wp_send_json_error(['message' => 'کپی فایل‌ها در کتابخانهٔ رسانه انجام نشد. دسترسی نوشتن پوشهٔ wp-content/uploads را بررسی کنید.']);
        }

        $items = [];
        foreach ($ids as $attachmentId) {
            $items[] = [
                'id' => $attachmentId,
                'thumb' => self::previewUrl($attachmentId),
                'title' => html_entity_decode(get_the_title($attachmentId), ENT_QUOTES, 'UTF-8'),
            ];
        }

        wp_send_json_success([
            'items' => $items,
            'total' => count($set['items']),
        ]);
    }

    /**
     * Find the Media Library copy of one sheet or create it.
     *
     * @param array{slug: string, title_fa: string, title_en: string, items: array<int, array<string, mixed>>} $set
     * @param array<string, mixed> $item
     */
    private static function ensureAttachment(array $set, array $item, int $index, int $parentPostId): int
    {
        $sourceDir = self::baseDir() . '/' . $set['slug'];
        $source = $sourceDir . '/' . $item['file'];
        // Same key format as the demo importer (`laico_asset_` + md5 of the path below `resources/`).
        $assetKey = 'laico_asset_' . md5('sketches/' . $set['slug'] . '/' . $item['file']);

        $existing = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'no_found_rows' => true,
            'meta_key' => '_laico_source_asset',
            'meta_value' => $assetKey,
        ]);

        if (! empty($existing[0])) {
            $existingId = (int) $existing[0];
            self::restoreMissingFiles($existingId, $sourceDir, $item);

            return $existingId;
        }

        $uploads = wp_upload_dir();
        if (! empty($uploads['error']) || empty($uploads['path']) || ! wp_mkdir_p((string) $uploads['path'])) {
            return 0;
        }

        $targetDir = trailingslashit((string) $uploads['path']);
        $fileName = wp_unique_filename((string) $uploads['path'], (string) $item['file']);
        $target = $targetDir . $fileName;

        if (! self::copyFile($source, $target)) {
            return 0;
        }

        $fileType = wp_check_filetype($fileName, null);
        $mime = (string) ($fileType['type'] ?: 'image/webp');

        $width = (int) $item['width'];
        $height = (int) $item['height'];
        if ($width <= 0 || $height <= 0) {
            $dims = @getimagesize($target);
            $width = is_array($dims) ? (int) $dims[0] : 0;
            $height = is_array($dims) ? (int) $dims[1] : 0;
        }

        // The thumbnail is ready-made (drawn for thin CAD lines), so no image library is needed on the server.
        $sizes = [];
        $thumbTarget = '';
        if ($item['thumb'] !== '') {
            $thumbName = pathinfo($fileName, PATHINFO_FILENAME) . '-' . (int) $item['thumb_width'] . 'x' . (int) $item['thumb_height']
                . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
            $thumbTarget = $targetDir . $thumbName;
            if (self::copyFile($sourceDir . '/' . $item['thumb'], $thumbTarget)) {
                $sizes[self::THUMB_SIZE] = [
                    'file' => $thumbName,
                    'width' => (int) $item['thumb_width'],
                    'height' => (int) $item['thumb_height'],
                    'mime-type' => $mime,
                    'filesize' => (int) @filesize($thumbTarget),
                ];
            } else {
                $thumbTarget = '';
            }
        }

        $captionFa = (string) $item['fa'];
        $captionEn = (string) $item['en'];
        $title = $captionFa !== '' ? $captionFa : ($captionEn !== '' ? $captionEn : $set['title_fa'] . ' ' . ($index + 1));
        // "فارسی | English" — the caption format the single-project page splits into both languages.
        $caption = $captionFa;
        if ($captionEn !== '' && $captionEn !== $captionFa) {
            $caption = $captionFa !== '' ? $captionFa . ' | ' . $captionEn : $captionEn;
        }

        $attachmentId = wp_insert_attachment([
            'guid' => trailingslashit((string) $uploads['url']) . $fileName,
            'post_mime_type' => $mime,
            'post_title' => wp_slash($title),
            'post_name' => sanitize_title(pathinfo($fileName, PATHINFO_FILENAME)),
            'post_excerpt' => wp_slash($caption),
            'post_content' => '',
            'post_status' => 'inherit',
            'meta_input' => [
                '_laico_source_asset' => $assetKey,
                '_wp_attachment_image_alt' => wp_slash($title),
            ],
        ], $target, max(0, $parentPostId), true);

        if (is_wp_error($attachmentId) || ! $attachmentId) {
            @unlink($target);
            if ($thumbTarget !== '') {
                @unlink($thumbTarget);
            }

            return 0;
        }

        $attachmentId = (int) $attachmentId;
        $relativeFile = (string) get_post_meta($attachmentId, '_wp_attached_file', true);

        wp_update_attachment_metadata($attachmentId, [
            'width' => $width,
            'height' => $height,
            'file' => $relativeFile !== '' ? $relativeFile : $fileName,
            'filesize' => (int) @filesize($target),
            'sizes' => $sizes,
        ]);

        return $attachmentId;
    }

    /**
     * An imported sheet whose files were removed from `uploads` (cleanup, partial site move) is copied again in place.
     *
     * @param array<string, mixed> $item
     */
    private static function restoreMissingFiles(int $attachmentId, string $sourceDir, array $item): void
    {
        $file = get_attached_file($attachmentId);
        if (! is_string($file) || $file === '') {
            return;
        }

        if (! is_file($file) && wp_mkdir_p(dirname($file))) {
            self::copyFile($sourceDir . '/' . $item['file'], $file);
        }

        $meta = wp_get_attachment_metadata($attachmentId);
        $thumbName = is_array($meta) ? (string) ($meta['sizes'][self::THUMB_SIZE]['file'] ?? '') : '';
        if ($item['thumb'] !== '' && $thumbName !== '' && basename($thumbName) === $thumbName) {
            $thumbFile = dirname($file) . '/' . $thumbName;
            if (! is_file($thumbFile) && is_dir(dirname($file))) {
                self::copyFile($sourceDir . '/' . $item['thumb'], $thumbFile);
            }
        }
    }

    /**
     * Copy a file and give it the same permissions WordPress gives to uploads.
     */
    private static function copyFile(string $source, string $target): bool
    {
        if (! is_file($source) || ! @copy($source, $target)) {
            return false;
        }

        $stat = @stat(dirname($target));
        if (is_array($stat)) {
            @chmod($target, $stat['mode'] & 0000666);
        }

        return true;
    }
}
