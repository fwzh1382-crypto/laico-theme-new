<?php

declare(strict_types=1);

namespace App\PostTypes;

use App\Services\BundledSketches;
use App\Services\ThemeOptions;
use App\Support\Jalali;

/**
 * Registers the `laico_project` Custom Post Type, its Taxonomies
 * (`project_category`, `project_space`, `project_city`), Bilingual Term Meta,
 * Rich Project Detail Meta Boxes (Specs, Gallery, Video, Bilingual Copy),
 * and Admin List Columns.
 */
final class ProjectPostType
{
    public const POST_TYPE = 'laico_project';
    public const TAX_CATEGORY = 'project_category';
    public const TAX_SPACE = 'project_space';
    public const TAX_CITY = 'project_city';

    public function register(): void
    {
        add_action('init', [$this, 'registerPostTypeAndTaxonomies']);
        add_action('init', [$this, 'seedDefaultTermsOnce'], 20);
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'saveProjectMeta'], 10, 2);

        foreach ([self::TAX_CATEGORY, self::TAX_SPACE, self::TAX_CITY] as $tax) {
            add_action("{$tax}_add_form_fields", [$this, 'renderAddTermEnglishField']);
            add_action("{$tax}_edit_form_fields", [$this, 'renderEditTermEnglishField'], 10, 2);
            add_action("created_{$tax}", [$this, 'saveTermEnglishField']);
            add_action("edited_{$tax}", [$this, 'saveTermEnglishField']);
        }

        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'filterAdminColumns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'renderAdminColumn'], 10, 2);
    }

    public function registerPostTypeAndTaxonomies(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'پروژه‌ها',
                'singular_name' => 'پروژه',
                'menu_name' => 'پروژه‌ها',
                'name_admin_bar' => 'پروژه',
                'add_new' => 'افزودن پروژه',
                'add_new_item' => 'افزودن پروژه جدید',
                'edit_item' => 'ویرایش پروژه',
                'new_item' => 'پروژه جدید',
                'view_item' => 'مشاهده پروژه',
                'search_items' => 'جستجوی پروژه‌ها',
                'not_found' => 'پروژه‌ای یافت نشد.',
                'not_found_in_trash' => 'پروژه‌ای در زباله‌دان یافت نشد.',
                'all_items' => 'همه پروژه‌ها',
                'featured_image' => 'تصویر اصلی پروژه (کاور)',
                'set_featured_image' => 'انتخاب تصویر اصلی پروژه',
                'remove_featured_image' => 'حذف تصویر اصلی پروژه',
            ],
            'public' => true,
            'has_archive' => 'projects',
            'rewrite' => [
                'slug' => 'project',
                'with_front' => false,
            ],
            'menu_icon' => 'dashicons-building',
            'menu_position' => 21,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'],
            'show_in_rest' => true,
        ]);

        register_taxonomy(self::TAX_CATEGORY, [self::POST_TYPE], [
            'labels' => [
                'name' => 'دسته‌بندی پروژه',
                'singular_name' => 'دسته‌بندی',
                'search_items' => 'جستجوی دسته‌بندی',
                'all_items' => 'همه دسته‌بندی‌ها',
                'edit_item' => 'ویرایش دسته‌بندی',
                'add_new_item' => 'افزودن دسته‌بندی جدید',
                'menu_name' => 'دسته‌بندی (مسکونی/تجاری)',
            ],
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'project-category', 'with_front' => false],
        ]);

        register_taxonomy(self::TAX_SPACE, [self::POST_TYPE], [
            'labels' => [
                'name' => 'نوع فضا',
                'singular_name' => 'فضا',
                'search_items' => 'جستجوی فضاها',
                'all_items' => 'همه فضاها',
                'edit_item' => 'ویرایش فضا',
                'add_new_item' => 'افزودن فضای جدید',
                'menu_name' => 'نوع فضا',
            ],
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'project-space', 'with_front' => false],
        ]);

        register_taxonomy(self::TAX_CITY, [self::POST_TYPE], [
            'labels' => [
                'name' => 'موقعیت / شهر',
                'singular_name' => 'شهر',
                'search_items' => 'جستجوی شهرها',
                'all_items' => 'همه شهرها',
                'edit_item' => 'ویرایش شهر',
                'add_new_item' => 'افزودن شهر جدید',
                'menu_name' => 'موقعیت / شهر',
            ],
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'project-city', 'with_front' => false],
        ]);
    }

    /**
     * Seed default categories, spaces, and cities once so filters work immediately.
     */
    public function seedDefaultTermsOnce(): void
    {
        if (get_option('laico_seeded_project_terms') === '1') {
            return;
        }

        $defaults = [
            self::TAX_CATEGORY => [
                'residential' => ['مسکونی', 'Residential'],
                'commercial' => ['تجاری', 'Commercial'],
            ],
            self::TAX_SPACE => [
                'kitchen' => ['آشپزخانه', 'Kitchen'],
                'living' => ['نشیمن و خواب', 'Living & bedroom'],
                'bath' => ['حمام', 'Bath'],
                'office' => ['اداری و درمانی', 'Office & clinic'],
                'hall' => ['کافه و رستوران', 'Café & restaurant'],
                'sports' => ['ورزشی', 'Sports'],
                'educational' => ['آموزشی', 'Educational'],
                'architecture' => ['معماری', 'Architecture'],
            ],
            self::TAX_CITY => [
                'amol' => ['آمل', 'Amol'],
                'tehran' => ['تهران', 'Tehran'],
                'mazandaran' => ['مازندران', 'Mazandaran'],
                'firouzkuh' => ['فیروزکوه', 'Firouzkuh'],
            ],
        ];

        foreach ($defaults as $tax => $terms) {
            if (! taxonomy_exists($tax)) {
                continue;
            }
            foreach ($terms as $slug => [$faName, $enName]) {
                $existing = get_term_by('slug', $slug, $tax);
                if (! $existing instanceof \WP_Term) {
                    $created = wp_insert_term($faName, $tax, ['slug' => $slug]);
                    if (! is_wp_error($created) && ! empty($created['term_id'])) {
                        update_term_meta((int) $created['term_id'], '_laico_name_en', $enName);
                    }
                }
            }
        }

        update_option('laico_seeded_project_terms', '1', true);
    }

    public function renderAddTermEnglishField(): void
    {
        if (! ThemeOptions::isEnglishEnabled()) {
            return;
        }
        ?>
        <div class="form-field">
          <label for="laico_name_en">نام انگلیسی (English Label)</label>
          <input type="text" name="laico_name_en" id="laico_name_en" value="" dir="ltr">
          <p class="description">نمایش در حالت زبان انگلیسی سایت (اختیاری).</p>
        </div>
        <?php
    }

    public function renderEditTermEnglishField(\WP_Term $term): void
    {
        if (! ThemeOptions::isEnglishEnabled()) {
            return;
        }
        $val = (string) get_term_meta($term->term_id, '_laico_name_en', true);
        ?>
        <tr class="form-field">
          <th scope="row"><label for="laico_name_en">نام انگلیسی (English Label)</label></th>
          <td>
            <input type="text" name="laico_name_en" id="laico_name_en" value="<?php echo esc_attr($val); ?>" dir="ltr">
            <p class="description">نمایش در حالت زبان انگلیسی سایت (اختیاری).</p>
          </td>
        </tr>
        <?php
    }

    public function saveTermEnglishField(int $termId): void
    {
        if (! current_user_can('manage_categories')) {
            return;
        }
        if (isset($_POST['laico_name_en'])) {
            update_term_meta($termId, '_laico_name_en', sanitize_text_field(wp_unslash((string) $_POST['laico_name_en'])));
        }
    }

    public function registerMetaBoxes(): void
    {
        add_meta_box(
            'laico_project_specs',
            'مشخصات فنی پروژه (کارت اطلاعات تک‌صفحه و فیلترها)',
            [$this, 'renderSpecsMetaBox'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'laico_project_media',
            'گالری تصاویر، اسکیس‌ها و ویدیوی پروژه',
            [$this, 'renderMediaMetaBox'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        if (ThemeOptions::isEnglishEnabled()) {
            add_meta_box(
                'laico_project_bilingual',
                'ترجمهٔ انگلیسی پروژه (اختیاری — دوزبانه)',
                [$this, 'renderBilingualMetaBox'],
                self::POST_TYPE,
                'normal',
                'default'
            );
        }
    }

    public function renderSpecsMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('laico_save_project_meta', '_laico_project_nonce');

        $clientFa = (string) get_post_meta($post->ID, '_laico_client_fa', true);
        $clientEn = (string) get_post_meta($post->ID, '_laico_client_en', true);
        $year = (string) get_post_meta($post->ID, '_laico_year', true);
        $yearEn = (string) get_post_meta($post->ID, '_laico_year_en', true);
        $durationFa = (string) get_post_meta($post->ID, '_laico_duration_fa', true);
        $durationEn = (string) get_post_meta($post->ID, '_laico_duration_en', true);
        $locationFa = (string) get_post_meta($post->ID, '_laico_location_fa', true);
        $locationEn = (string) get_post_meta($post->ID, '_laico_location_en', true);
        $area = (string) get_post_meta($post->ID, '_laico_area', true);
        $featuredHome = (bool) get_post_meta($post->ID, '_laico_featured_home', true);
        $mosaicSlot = (int) get_post_meta($post->ID, '_laico_mosaic_slot', true);
        $enEnabled = ThemeOptions::isEnglishEnabled();
        ?>
        <div class="laico-meta-grid laico-meta-grid--2col" dir="rtl">
          <div class="laico-meta-field">
            <label for="laico_client_fa"><strong>نام کارفرما (فارسی)</strong></label>
            <input type="text" id="laico_client_fa" name="laico_client_fa" value="<?php echo esc_attr($clientFa); ?>" class="widefat" placeholder="مثلاً: خانوادهٔ کریمی">
          </div>
          <?php if ($enEnabled) : ?>
          <div class="laico-meta-field">
            <label for="laico_client_en"><strong>نام کارفرما (انگلیسی — اختیاری)</strong></label>
            <input type="text" id="laico_client_en" name="laico_client_en" value="<?php echo esc_attr($clientEn); ?>" class="widefat" dir="ltr" placeholder="e.g. Karimi family">
          </div>
          <?php endif; ?>

          <div class="laico-meta-field">
            <label for="laico_year"><strong>سال انجام پروژه (شمسی)</strong></label>
            <input type="text" id="laico_year" name="laico_year" value="<?php echo esc_attr($year); ?>" class="widefat" dir="ltr" placeholder="1404">
          </div>
          <?php if ($enEnabled) : ?>
          <div class="laico-meta-field">
            <label for="laico_year_en"><strong>سال میلادی (اختیاری — در صورت خالی بودن خودکار محاسبه می‌شود)</strong></label>
            <input type="text" id="laico_year_en" name="laico_year_en" value="<?php echo esc_attr($yearEn); ?>" class="widefat" dir="ltr" placeholder="2025">
          </div>
          <?php endif; ?>

          <div class="laico-meta-field">
            <label for="laico_duration_fa"><strong>مدت زمان پروژه (فارسی)</strong></label>
            <input type="text" id="laico_duration_fa" name="laico_duration_fa" value="<?php echo esc_attr($durationFa); ?>" class="widefat" placeholder="مثلاً: ۸ ماه">
          </div>
          <?php if ($enEnabled) : ?>
          <div class="laico-meta-field">
            <label for="laico_duration_en"><strong>مدت زمان پروژه (انگلیسی — اختیاری)</strong></label>
            <input type="text" id="laico_duration_en" name="laico_duration_en" value="<?php echo esc_attr($durationEn); ?>" class="widefat" dir="ltr" placeholder="e.g. 8 months">
          </div>
          <?php endif; ?>

          <div class="laico-meta-field">
            <label for="laico_location_fa"><strong>مکان پروژه (فارسی)</strong></label>
            <input type="text" id="laico_location_fa" name="laico_location_fa" value="<?php echo esc_attr($locationFa); ?>" class="widefat" placeholder="مثلاً: تهران، سعادت‌آباد">
          </div>
          <?php if ($enEnabled) : ?>
          <div class="laico-meta-field">
            <label for="laico_location_en"><strong>مکان پروژه (انگلیسی — اختیاری)</strong></label>
            <input type="text" id="laico_location_en" name="laico_location_en" value="<?php echo esc_attr($locationEn); ?>" class="widefat" dir="ltr" placeholder="e.g. Saadat Abad, Tehran">
          </div>
          <?php endif; ?>

          <div class="laico-meta-field">
            <label for="laico_area"><strong>متراژ پروژه (به متر مربع — فقط عدد)</strong></label>
            <input type="number" id="laico_area" name="laico_area" min="0" step="1" value="<?php echo esc_attr($area); ?>" class="widefat" dir="ltr" placeholder="310">
            <p class="description">به‌صورت خودکار دستهٔ متراژ (کوچک زیر ۱۲۰ / متوسط ۱۲۰ تا ۳۰۰ / بزرگ بالای ۳۰۰ متر) را در فیلتر پروژه‌ها تعیین می‌کند.</p>
          </div>
          <div class="laico-meta-field">
            <label><strong>نمایش در موزاییک پروژه‌های منتخب صفحه اصلی</strong></label>
            <p style="margin:6px 0;">
              <label>
                <input type="checkbox" name="laico_featured_home" value="1" <?php checked($featuredHome); ?>>
                نمایش این پروژه در بخش «۰۲ — پروژه‌های منتخب» صفحه اصلی
              </label>
            </p>
            <label for="laico_mosaic_slot">جایگاه در چیدمان ۴تایی (۱ تا ۴):</label>
            <select id="laico_mosaic_slot" name="laico_mosaic_slot">
              <option value="0" <?php selected($mosaicSlot, 0); ?>>خودکار</option>
              <option value="1" <?php selected($mosaicSlot, 1); ?>>جایگاه ۱ (ردیف اول — چپ)</option>
              <option value="2" <?php selected($mosaicSlot, 2); ?>>جایگاه ۲ (ردیف اول — راست عریض)</option>
              <option value="3" <?php selected($mosaicSlot, 3); ?>>جایگاه ۳ (ردیف دوم — چپ عریض)</option>
              <option value="4" <?php selected($mosaicSlot, 4); ?>>جایگاه ۴ (ردیف دوم — راست)</option>
            </select>
          </div>
        </div>
        <?php
    }

    public function renderMediaMetaBox(\WP_Post $post): void
    {
        $galleryIdsRaw = (string) get_post_meta($post->ID, '_laico_gallery_ids', true);
        $galleryIds = array_values(array_filter(array_map('intval', explode(',', $galleryIdsRaw))));
        $galleryUrls = (string) get_post_meta($post->ID, '_laico_gallery_urls', true);
        $sketchIdsRaw = (string) get_post_meta($post->ID, '_laico_sketch_ids', true);
        $sketchIds = array_values(array_filter(array_map('intval', explode(',', $sketchIdsRaw))));
        $sketchUrls = (string) get_post_meta($post->ID, '_laico_sketch_urls', true);
        $videoUrl = (string) get_post_meta($post->ID, '_laico_video_url', true);
        $videoPoster = (string) get_post_meta($post->ID, '_laico_video_poster', true);
        ?>
        <div class="laico-meta-grid" dir="rtl">
          <div class="laico-meta-field">
            <label><strong>گالری تصاویر پروژه (نمایش با لایت‌باکس بزرگ‌نمایی)</strong></label>
            <input type="hidden" id="laico_gallery_ids" name="laico_gallery_ids" value="<?php echo esc_attr(implode(',', $galleryIds)); ?>">
            <div class="laico-gallery-preview" id="laico-gallery-preview">
              <?php foreach ($galleryIds as $attId) :
                  $thumb = wp_get_attachment_image_url($attId, 'thumbnail');
                  if (! $thumb) {
                      continue;
                  }
              ?>
                <div class="laico-gallery-thumb" data-id="<?php echo esc_attr((string) $attId); ?>">
                  <img src="<?php echo esc_url($thumb); ?>" alt="">
                  <button type="button" class="laico-remove-thumb" aria-label="حذف">×</button>
                </div>
              <?php endforeach; ?>
            </div>
            <p style="margin-top:10px;">
              <button type="button" class="button button-primary" id="laico-pick-gallery">افزودن تصاویر از کتابخانه رسانه</button>
              <button type="button" class="button" id="laico-clear-gallery">پاک‌سازی گالری</button>
            </p>
            <details style="margin-top:8px;">
              <summary style="cursor:pointer;color:#6d7e90;font-size:12px;">آدرس مستقیم تصاویر گالری (هر خط یک URL — اختیاری در صورت عدم استفاده از کتابخانه رسانه)</summary>
              <textarea name="laico_gallery_urls" rows="3" class="widefat" dir="ltr" style="margin-top:6px;" placeholder="https://.../img1.webp"><?php echo esc_textarea($galleryUrls); ?></textarea>
            </details>
          </div>

          <div class="laico-meta-field">
            <label><strong>اسکیس‌های پروژه (طرح دستی، پلان و دیتیل — بخش «اسکیس‌های پروژه» در تک‌صفحه، بین متن معرفی و گالری)</strong></label>
            <input type="hidden" id="laico_sketch_ids" name="laico_sketch_ids" value="<?php echo esc_attr(implode(',', $sketchIds)); ?>">
            <div class="laico-gallery-preview" id="laico-sketch-preview">
              <?php foreach ($sketchIds as $attId) :
                  $thumb = BundledSketches::previewUrl($attId);
                  if ($thumb === '') {
                      continue;
                  }
              ?>
                <div class="laico-gallery-thumb" data-id="<?php echo esc_attr((string) $attId); ?>" title="<?php echo esc_attr(get_the_title($attId)); ?>">
                  <img src="<?php echo esc_url($thumb); ?>" alt="">
                  <button type="button" class="laico-remove-thumb" aria-label="حذف">×</button>
                </div>
              <?php endforeach; ?>
            </div>
            <p style="margin-top:10px;">
              <button type="button" class="button button-primary" id="laico-pick-sketches">افزودن اسکیس از کتابخانه رسانه</button>
              <button type="button" class="button" id="laico-clear-sketches">پاک‌سازی اسکیس‌ها</button>
            </p>
            <p class="description">هر اسکیس کامل و بدون برش روی زمینهٔ سفید نمایش داده می‌شود و با کلیک، بزرگ (لایت‌باکس) باز می‌شود. اگر اسکیسی انتخاب نشود، این بخش در سایت دیده نمی‌شود. در مجموعه‌های پرتعداد ابتدا دو ردیف دیده می‌شود و بقیه با دکمهٔ «نمایش همهٔ اسکیس‌ها» باز می‌شوند. متن فیلد «زیرنویس» (Caption) هر تصویر در کتابخانهٔ رسانه، زیر همان اسکیس نوشته می‌شود؛ برای نسخهٔ انگلیسی، زیرنویس را به‌صورت «فارسی | English» بنویسید — مثال: <code>پلان مفهومی | Concept plan</code></p>
            <?php
            $sketchSets = current_user_can('upload_files') ? BundledSketches::sets() : [];
            if (! empty($sketchSets)) :
            ?>
              <div class="laico-sketch-set">
                <label for="laico_sketch_set"><strong>اسکیس‌های آمادهٔ همراه قالب</strong></label>
                <div class="laico-sketch-set-row">
                  <select id="laico_sketch_set">
                    <?php if (count($sketchSets) > 1) : ?>
                      <option value="">— انتخاب مجموعه —</option>
                    <?php endif; ?>
                    <?php foreach ($sketchSets as $sketchSet) : ?>
                      <option value="<?php echo esc_attr($sketchSet['slug']); ?>"><?php echo esc_html($sketchSet['title_fa'] . ' (' . Jalali::toPersianDigits(count($sketchSet['items'])) . ' برگ)'); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="button" class="button" id="laico-add-sketch-set"
                          data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
                          data-action="<?php echo esc_attr(BundledSketches::AJAX_ACTION); ?>"
                          data-nonce="<?php echo esc_attr(wp_create_nonce(BundledSketches::AJAX_ACTION)); ?>"
                          data-post="<?php echo esc_attr((string) $post->ID); ?>">افزودن این مجموعه به اسکیس‌های پروژه</button>
                  <span class="laico-sketch-set-status" id="laico-sketch-set-status" role="status" aria-live="polite"></span>
                </div>
                <p class="description">برگ‌های مجموعه یک‌بار در کتابخانهٔ رسانه کپی و به فهرست بالا اضافه می‌شوند؛ هر برگ را می‌توانید با × بردارید. برای ثبت روی سایت، پروژه را «به‌روزرسانی / انتشار» کنید.</p>
              </div>
            <?php endif; ?>
            <details style="margin-top:8px;">
              <summary style="cursor:pointer;color:#6d7e90;font-size:12px;">آدرس مستقیم اسکیس‌ها (هر خط یک URL — اختیاری در صورت عدم استفاده از کتابخانه رسانه)</summary>
              <textarea name="laico_sketch_urls" rows="3" class="widefat" dir="ltr" style="margin-top:6px;" placeholder="https://.../sketch-1.webp"><?php echo esc_textarea($sketchUrls); ?></textarea>
            </details>
          </div>

          <div class="laico-meta-field">
            <label for="laico_video_url"><strong>ویدیوی پروژه (فایل MP4 — در صورت خالی بودن بخش ویدیو نمایش داده نمی‌شود)</strong></label>
            <div class="laico-media-input-row">
              <input type="url" id="laico_video_url" name="laico_video_url" value="<?php echo esc_attr($videoUrl); ?>" class="widefat" dir="ltr" placeholder="https://.../tour.mp4">
              <button type="button" class="button laico-pick-media" data-target="#laico_video_url" data-type="video">انتخاب ویدیو از رسانه</button>
            </div>
          </div>

          <div class="laico-meta-field">
            <label for="laico_video_poster"><strong>پوستر ویدیو (اختیاری — پیش‌فرض: تصویر شاخص پروژه)</strong></label>
            <div class="laico-media-input-row">
              <input type="url" id="laico_video_poster" name="laico_video_poster" value="<?php echo esc_attr($videoPoster); ?>" class="widefat" dir="ltr" placeholder="https://.../poster.webp">
              <button type="button" class="button laico-pick-media" data-target="#laico_video_poster">انتخاب تصویر پوستر</button>
            </div>
          </div>
        </div>
        <?php
    }

    public function renderBilingualMetaBox(\WP_Post $post): void
    {
        $titleEn = (string) get_post_meta($post->ID, '_laico_title_en', true);
        $ledeEn = (string) get_post_meta($post->ID, '_laico_lede_en', true);
        $contentEn = (string) get_post_meta($post->ID, '_laico_content_en', true);
        ?>
        <div class="laico-meta-grid" dir="rtl">
          <p class="description">عنوان اصلی، چکیده (لید زیر عنوان) و محتوای ویرایشگر بالا برای زبان فارسی استفاده می‌شوند. در صورت تمایل به نمایش نسخهٔ انگلیسی هنگام کلیک کاربر روی دکمهٔ <code>EN</code>، فیلدهای زیر را پر کنید:</p>
          <div class="laico-meta-field">
            <label for="laico_title_en"><strong>عنوان انگلیسی پروژه (English Title)</strong></label>
            <input type="text" id="laico_title_en" name="laico_title_en" value="<?php echo esc_attr($titleEn); ?>" class="widefat" dir="ltr" placeholder="e.g. Penthouse Aftab">
          </div>
          <div class="laico-meta-field">
            <label for="laico_lede_en"><strong>خلاصهٔ بالای صفحه به انگلیسی (English Lede)</strong></label>
            <textarea id="laico_lede_en" name="laico_lede_en" rows="2" class="widefat" dir="ltr" placeholder="Short summary shown under the project H1..."><?php echo esc_textarea($ledeEn); ?></textarea>
          </div>
          <div class="laico-meta-field">
            <label for="laico_content_en"><strong>توضیحات دربارهٔ پروژه به انگلیسی (هر پاراگراف در یک خط خالی جدا)</strong></label>
            <textarea id="laico_content_en" name="laico_content_en" rows="5" class="widefat" dir="ltr" placeholder="English project story..."><?php echo esc_textarea($contentEn); ?></textarea>
          </div>
        </div>
        <?php
    }

    public function saveProjectMeta(int $postId, \WP_Post $post): void
    {
        if (! isset($_POST['_laico_project_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['_laico_project_nonce'])), 'laico_save_project_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (! current_user_can('edit_post', $postId)) {
            return;
        }

        $faTextFields = [
            'laico_client_fa' => '_laico_client_fa',
            'laico_duration_fa' => '_laico_duration_fa',
            'laico_location_fa' => '_laico_location_fa',
        ];
        foreach ($faTextFields as $formKey => $metaKey) {
            $val = isset($_POST[$formKey]) ? sanitize_text_field(wp_unslash((string) $_POST[$formKey])) : '';
            update_post_meta($postId, $metaKey, $val);
        }

        $enTextFields = [
            'laico_client_en' => '_laico_client_en',
            'laico_duration_en' => '_laico_duration_en',
            'laico_location_en' => '_laico_location_en',
            'laico_title_en' => '_laico_title_en',
        ];
        foreach ($enTextFields as $formKey => $metaKey) {
            if (array_key_exists($formKey, $_POST)) {
                update_post_meta($postId, $metaKey, sanitize_text_field(wp_unslash((string) $_POST[$formKey])));
            }
        }

        $yearRaw = isset($_POST['laico_year']) ? Jalali::toEnglishDigits(sanitize_text_field(wp_unslash((string) $_POST['laico_year']))) : '';
        $yearClean = preg_replace('/\D+/', '', $yearRaw) ?: '';
        update_post_meta($postId, '_laico_year', $yearClean);

        if (array_key_exists('laico_year_en', $_POST)) {
            $yearEnRaw = Jalali::toEnglishDigits(sanitize_text_field(wp_unslash((string) $_POST['laico_year_en'])));
            update_post_meta($postId, '_laico_year_en', preg_replace('/\D+/', '', $yearEnRaw) ?: '');
        }

        $area = isset($_POST['laico_area']) ? max(0, (int) Jalali::toEnglishDigits((string) wp_unslash($_POST['laico_area']))) : 0;
        update_post_meta($postId, '_laico_area', $area > 0 ? (string) $area : '');

        if (! empty($_POST['laico_featured_home'])) {
            update_post_meta($postId, '_laico_featured_home', '1');
        } else {
            delete_post_meta($postId, '_laico_featured_home');
        }
        $slot = isset($_POST['laico_mosaic_slot']) ? max(0, min(4, (int) $_POST['laico_mosaic_slot'])) : 0;
        update_post_meta($postId, '_laico_mosaic_slot', (string) $slot);

        // Gallery IDs & fallback URLs
        $rawIds = isset($_POST['laico_gallery_ids']) ? sanitize_text_field(wp_unslash((string) $_POST['laico_gallery_ids'])) : '';
        $cleanIds = array_values(array_filter(array_map('intval', explode(',', $rawIds)), static fn (int $id): bool => $id > 0));
        update_post_meta($postId, '_laico_gallery_ids', implode(',', $cleanIds));

        $rawUrls = isset($_POST['laico_gallery_urls']) ? (string) wp_unslash($_POST['laico_gallery_urls']) : '';
        $cleanUrls = array_values(array_filter(array_map(static fn (string $u): string => esc_url_raw(trim($u)), preg_split('/\r\n|\r|\n/', $rawUrls) ?: [])));
        update_post_meta($postId, '_laico_gallery_urls', implode("\n", $cleanUrls));

        // Sketch IDs & fallback URLs (only when the sketch fields were part of the submitted form)
        if (array_key_exists('laico_sketch_ids', $_POST)) {
            $rawSketchIds = sanitize_text_field(wp_unslash((string) $_POST['laico_sketch_ids']));
            $cleanSketchIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $rawSketchIds)), static fn (int $id): bool => $id > 0)));
            update_post_meta($postId, '_laico_sketch_ids', implode(',', $cleanSketchIds));
        }
        if (array_key_exists('laico_sketch_urls', $_POST)) {
            $rawSketchUrls = (string) wp_unslash($_POST['laico_sketch_urls']);
            $cleanSketchUrls = array_values(array_filter(array_map(static fn (string $u): string => esc_url_raw(trim($u)), preg_split('/\r\n|\r|\n/', $rawSketchUrls) ?: [])));
            update_post_meta($postId, '_laico_sketch_urls', implode("\n", $cleanSketchUrls));
        }

        $videoUrl = isset($_POST['laico_video_url']) ? esc_url_raw(trim((string) wp_unslash($_POST['laico_video_url']))) : '';
        $videoPoster = isset($_POST['laico_video_poster']) ? esc_url_raw(trim((string) wp_unslash($_POST['laico_video_poster']))) : '';
        update_post_meta($postId, '_laico_video_url', $videoUrl);
        update_post_meta($postId, '_laico_video_poster', $videoPoster);

        if (array_key_exists('laico_lede_en', $_POST)) {
            update_post_meta($postId, '_laico_lede_en', sanitize_textarea_field(wp_unslash((string) $_POST['laico_lede_en'])));
        }
        if (array_key_exists('laico_content_en', $_POST)) {
            update_post_meta($postId, '_laico_content_en', wp_kses_post((string) wp_unslash($_POST['laico_content_en'])));
        }
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function filterAdminColumns(array $columns): array
    {
        $new = [];
        $new['cb'] = $columns['cb'] ?? '<input type="checkbox">';
        $new['laico_thumb'] = 'تصویر';
        $new['title'] = 'نام پروژه';
        $new['taxonomy-' . self::TAX_CATEGORY] = 'دسته‌بندی';
        $new['taxonomy-' . self::TAX_SPACE] = 'نوع فضا';
        $new['taxonomy-' . self::TAX_CITY] = 'موقعیت';
        $new['laico_year'] = 'سال';
        $new['laico_area'] = 'متراژ';
        $new['laico_featured'] = 'منتخب صفحه اصلی';
        $new['date'] = $columns['date'] ?? 'تاریخ';
        return $new;
    }

    public function renderAdminColumn(string $column, int $postId): void
    {
        if ($column === 'laico_thumb') {
            if (has_post_thumbnail($postId)) {
                echo get_the_post_thumbnail($postId, [64, 48], ['style' => 'border-radius:6px;object-fit:cover;']);
            } else {
                echo '<span style="color:#8b9aab;">—</span>';
            }
        } elseif ($column === 'laico_year') {
            $year = (string) get_post_meta($postId, '_laico_year', true);
            echo $year !== '' ? esc_html(Jalali::toPersianDigits($year)) : '—';
        } elseif ($column === 'laico_area') {
            $area = (int) get_post_meta($postId, '_laico_area', true);
            echo $area > 0 ? esc_html(Jalali::toPersianDigits(number_format($area)) . ' م²') : '—';
        } elseif ($column === 'laico_featured') {
            $on = (bool) get_post_meta($postId, '_laico_featured_home', true);
            echo $on ? '<span style="color:#0b1d33;font-weight:700;">★ بله</span>' : '<span style="color:#8b9aab;">—</span>';
        }
    }

    /**
     * Helper: compute area size bucket (`small`, `medium`, `large`) matching `projects.js`.
     */
    public static function sizeBucket(int $area): string
    {
        if ($area <= 0) {
            return 'medium';
        }
        if ($area < 120) {
            return 'small';
        }
        return $area <= 300 ? 'medium' : 'large';
    }
}
