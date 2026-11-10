<?php

declare(strict_types=1);

namespace App\Admin;

use App\Services\DemoImporterService;
use App\Services\IppanelSmsService;
use App\Services\PatchUpdaterService;
use App\Services\SecurityIntegrationService;
use App\Services\SeoService;
use App\Services\ThemeOptions;
use App\Support\Jalali;
use App\Support\ViteAssets;

/**
 * Laico Design Theme Settings Panel (`پنل تنظیمات قالب لایکو`).
 *
 * Provides 6 dedicated tabs:
 * 1. عمومی، برندینگ و ارتباطات (`general`) + راه‌اندازی سریع محتوای نمونه
 * 2. مدیریت بخش‌های قالب (`sections`)
 * 3. بهینه‌سازی و سرعت (`optimization`)
 * 4. سئو و اسکیما (`seo`)
 * 5. فرم‌های مشاوره و اعلان‌ها (`forms`)
 * 6. بروزرسانی امن از طریق پچ (`patch`) — Dry-Run, Checksum, Signature, Backup & Rollback
 */
final class ThemeSettingsPage
{
    public const MENU_SLUG = 'laico-settings';

    /** @var array<string, mixed>|null */
    private ?array $flashNotice = null;

    /** @var array<string, mixed>|null */
    private ?array $stagedPatchPreview = null;

    /** @var array<string, mixed>|null */
    private ?array $remoteManifestResult = null;

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_init', [$this, 'handlePostSubmissions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function registerMenus(): void
    {
        add_menu_page(
            'پنل تنظیمات قالب لایکو دیزاین',
            'تنظیمات لایکو',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderPage'],
            'dashicons-admin-customizer',
            59
        );

        $subTabs = [
            'general' => 'عمومی و برندینگ',
            'sections' => 'بخش‌های قالب',
            'optimization' => 'بهینه‌سازی و سرعت',
            'seo' => 'تنظیمات سئو',
            'forms' => 'فرم‌ها و اعلان',
        ];

        if (current_user_can('update_themes')) {
            $subTabs['patch'] = 'بروزرسانی با پچ (Patch)';
        }

        foreach ($subTabs as $tabKey => $label) {
            $slug = $tabKey === 'general' ? self::MENU_SLUG : self::MENU_SLUG . '&tab=' . $tabKey;
            add_submenu_page(
                self::MENU_SLUG,
                $label . ' — لایکو دیزاین',
                $label,
                'manage_options',
                $slug,
                [$this, 'renderPage']
            );
        }
    }

    public function enqueueAdminAssets(string $hookSuffix): void
    {
        $isSettings = str_contains($hookSuffix, self::MENU_SLUG);
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $isMetaScreen = $screen && in_array($screen->post_type, ['laico_project', 'page', 'post'], true);

        if (! $isSettings && ! $isMetaScreen) {
            return;
        }

        wp_enqueue_media();

        $css = ViteAssets::entryCss('resources/css/admin.css');
        if ($css !== null) {
            wp_enqueue_style('laico-admin-styles', $css, [], LAICO_THEME_VERSION);
        }

        $js = ViteAssets::entryJs('resources/js/admin.js');
        if ($js !== null) {
            wp_enqueue_script('laico-admin-scripts', $js, ['jquery'], LAICO_THEME_VERSION, true);
        }
    }

    public function handlePostSubmissions(): void
    {
        if (! isset($_GET['page']) || $_GET['page'] !== self::MENU_SLUG) {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }
        if (! current_user_can('manage_options')) {
            return;
        }

        $action = isset($_POST['laico_admin_action']) ? sanitize_key(wp_unslash((string) $_POST['laico_admin_action'])) : '';

        // 1. Save Options Form
        if ($action === 'save_options') {
            check_admin_referer('laico_save_theme_options');
            $tab = isset($_POST['active_tab']) ? sanitize_key(wp_unslash((string) $_POST['active_tab'])) : 'general';
            $rawOptions = isset($_POST['laico_opt']) && is_array($_POST['laico_opt']) ? wp_unslash($_POST['laico_opt']) : [];

            // Explicitly ensure unchecked checkboxes belonging to the active tab become `false`
            foreach ($this->tabBooleanKeys($tab) as $boolKey) {
                if (! array_key_exists($boolKey, $rawOptions)) {
                    $rawOptions[$boolKey] = false;
                }
            }

            ThemeOptions::update($rawOptions);
            $this->flashNotice = ['type' => 'success', 'text' => 'تنظیمات قالب با موفقیت ذخیره شد.'];
            return;
        }

        // 2. Reset All Options to Defaults
        if ($action === 'reset_options') {
            check_admin_referer('laico_reset_theme_options');
            ThemeOptions::reset();
            $this->flashNotice = ['type' => 'warning', 'text' => 'تمام تنظیمات قالب به مقادیر پیش‌فرض بازنشانی شد.'];
            return;
        }

        // 3. 1-Click Demo Content & Main Pages Importer
        if ($action === 'import_demo') {
            check_admin_referer('laico_import_demo_content');
            $res = (new DemoImporterService())->importAll();
            $this->flashNotice = [
                'type' => $res['ok'] ? 'success' : 'error',
                'text' => $res['message'],
            ];
            return;
        }

        // 4. Patch Updater Actions (require `update_themes` capability in addition to `manage_options`)
        if (str_starts_with($action, 'patch_')) {
            if (! current_user_can('update_themes')) {
                $this->flashNotice = ['type' => 'error', 'text' => 'شما مجوز update_themes برای اعمال بروزرسانی قالب را ندارید.'];
                return;
            }

            $patcher = new PatchUpdaterService();

            if ($action === 'patch_stage_upload') {
                check_admin_referer('laico_patch_stage_upload');
                if (empty($_FILES['patch_file']['tmp_name']) || ! empty($_FILES['patch_file']['error'])) {
                    $this->flashNotice = ['type' => 'error', 'text' => 'لطفاً یک فایل پچ معتبر (.zip، .patch یا .diff) انتخاب کنید.'];
                    return;
                }
                $tmpPath = (string) $_FILES['patch_file']['tmp_name'];
                $origName = sanitize_file_name((string) ($_FILES['patch_file']['name'] ?? 'patch.zip'));
                $analysis = $patcher->stageAndAnalyzePatch($tmpPath, $origName);

                if ($analysis['ok'] ?? false) {
                    $this->stagedPatchPreview = $analysis;
                    $this->flashNotice = [
                        'type' => 'info',
                        'text' => 'بررسی آزمایشی (Dry-Run) با موفقیت انجام شد. فهرست تغییرات را در جدول زیر بازبینی و در صورت تأیید روی «نصب نهایی پچ» کلیک کنید.',
                    ];
                } else {
                    $this->flashNotice = ['type' => 'error', 'text' => (string) ($analysis['error'] ?? 'خطا در بررسی پچ.')];
                }
                return;
            }

            if ($action === 'patch_apply_staged') {
                check_admin_referer('laico_patch_apply_staged');
                $token = isset($_POST['staged_token']) ? sanitize_text_field(wp_unslash((string) $_POST['staged_token'])) : '';
                $applied = $patcher->applyStagedPatch($token);

                if ($applied['ok'] ?? false) {
                    $this->flashNotice = [
                        'type' => 'success',
                        'text' => sprintf(
                            'پچ «%s» با موفقیت و به‌صورت اتمیک روی قالب اعمال شد (%d فایل بروزرسانی شد و نسخه پشتیبان خودکار ذخیره گردید).',
                            $applied['record']['patch_id'],
                            $applied['record']['files_count']
                        ),
                    ];
                } else {
                    $this->flashNotice = ['type' => 'error', 'text' => (string) ($applied['error'] ?? 'خطا در نصب پچ.')];
                }
                return;
            }

            if ($action === 'patch_rollback') {
                check_admin_referer('laico_patch_rollback');
                $recordId = isset($_POST['record_id']) ? sanitize_text_field(wp_unslash((string) $_POST['record_id'])) : '';
                $rb = $patcher->rollbackPatch($recordId);
                $this->flashNotice = [
                    'type' => $rb['ok'] ? 'success' : 'error',
                    'text' => (string) ($rb['ok'] ? $rb['message'] : $rb['error']),
                ];
                return;
            }

            if ($action === 'patch_check_remote') {
                check_admin_referer('laico_patch_check_remote');
                $check = $patcher->checkRemoteManifest();
                $this->remoteManifestResult = $check;
                if (! ($check['ok'] ?? false)) {
                    $this->flashNotice = ['type' => 'error', 'text' => (string) ($check['error'] ?? 'خطا در بررسی سرور بروزرسانی.')];
                }
                return;
            }

            if ($action === 'patch_stage_remote_url') {
                check_admin_referer('laico_patch_stage_remote_url');
                $remoteUrl = isset($_POST['remote_patch_url']) ? esc_url_raw(wp_unslash((string) $_POST['remote_patch_url'])) : '';
                $analysis = $patcher->stageRemotePatchUrl($remoteUrl);
                if ($analysis['ok'] ?? false) {
                    $this->stagedPatchPreview = $analysis;
                    $this->flashNotice = ['type' => 'info', 'text' => 'بستهٔ پچ از سرور دریافت و بررسی آزمایشی شد. در صورت تأیید روی دکمهٔ نصب کلیک کنید.'];
                } else {
                    $this->flashNotice = ['type' => 'error', 'text' => (string) ($analysis['error'] ?? 'خطا در دریافت پچ.')];
                }
            }
        }
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('شما اجازه دسترسی به این بخش را ندارید.');
        }

        $tabs = [
            'general' => 'عمومی و برندینگ',
            'sections' => 'بخش‌های قالب',
            'optimization' => 'بهینه‌سازی و سرعت',
            'seo' => 'تنظیمات سئو',
            'forms' => 'فرم‌ها و اعلان‌ها',
        ];

        if (current_user_can('update_themes')) {
            $tabs['patch'] = 'بروزرسانی از طریق پچ (Patch)';
        }

        $activeTab = isset($_GET['tab']) ? sanitize_key(wp_unslash((string) $_GET['tab'])) : 'general';
        if (! array_key_exists($activeTab, $tabs)) {
            $activeTab = 'general';
        }

        $opt = ThemeOptions::all();
        ?>
        <div class="wrap laico-admin-wrap" dir="rtl">
          <div class="laico-admin-header">
            <div>
              <h1>پنل تنظیمات قالب لایکو دیزاین <span class="laico-ver-pill">نسخه <?php echo esc_html(Jalali::toPersianDigits(PatchUpdaterService::currentThemeVersion())); ?></span></h1>
              <p>مبتنی بر معماری Roots Sage — کنترل یکپارچهٔ بخش‌ها، بهینه‌سازی، سئو، فرم‌ها و نصب امن پچ‌های بروزرسانی</p>
            </div>
            <div class="laico-admin-header-actions">
              <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener" class="button">مشاهده وب‌سایت ↗</a>
              <a href="<?php echo esc_url(admin_url('admin.php?page=laico-submissions')); ?>" class="button button-secondary">ارسالی‌های فرم</a>
            </div>
          </div>

          <?php if ($this->flashNotice !== null) : ?>
            <div class="notice notice-<?php echo esc_attr((string) $this->flashNotice['type']); ?> is-dismissible">
              <p><?php echo esc_html((string) $this->flashNotice['text']); ?></p>
            </div>
          <?php endif; ?>

          <nav class="laico-tabs-nav" aria-label="تب‌های تنظیمات">
            <?php foreach ($tabs as $tabKey => $tabLabel) :
                $url = add_query_arg(['page' => self::MENU_SLUG, 'tab' => $tabKey], admin_url('admin.php'));
            ?>
              <a href="<?php echo esc_url($url); ?>" class="laico-tab-link <?php echo $activeTab === $tabKey ? 'is-active' : ''; ?>">
                <?php echo esc_html($tabLabel); ?>
              </a>
            <?php endforeach; ?>
          </nav>

          <?php if ($activeTab === 'patch') : ?>
            <?php $this->renderPatchUpdaterTab($opt); ?>
          <?php else : ?>
            <form method="post" action="<?php echo esc_url(add_query_arg(['page' => self::MENU_SLUG, 'tab' => $activeTab], admin_url('admin.php'))); ?>" class="laico-settings-form">
              <?php wp_nonce_field('laico_save_theme_options'); ?>
              <input type="hidden" name="laico_admin_action" value="save_options">
              <input type="hidden" name="active_tab" value="<?php echo esc_attr($activeTab); ?>">

              <?php
              if ($activeTab === 'general') {
                  $this->renderGeneralTab($opt);
              } elseif ($activeTab === 'sections') {
                  $this->renderSectionsTab($opt);
              } elseif ($activeTab === 'optimization') {
                  $this->renderOptimizationTab($opt);
              } elseif ($activeTab === 'seo') {
                  $this->renderSeoTab($opt);
              } elseif ($activeTab === 'forms') {
                  $this->renderFormsTab($opt);
              }
              ?>

              <div class="laico-submit-bar">
                <button type="submit" class="button button-primary button-hero">ذخیره تغییرات این بخش</button>
              </div>
            </form>

            <?php if ($activeTab === 'general') : ?>
              <?php $this->renderQuickToolsCards(); ?>
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $opt
     */
    private function renderGeneralTab(array $opt): void
    {
        ?>
        <div class="laico-card">
          <h2>هویت بصری و تنظیمات زبان</h2>
          <div class="laico-meta-grid laico-meta-grid--2col">
            <div class="laico-meta-field">
              <label><strong>لوگوی هدر و فوتر</strong></label>
              <div class="laico-media-input-row">
                <input type="url" id="laico_logo_url" name="laico_opt[logo_url]" value="<?php echo esc_attr((string) $opt['logo_url']); ?>" class="widefat" dir="ltr" placeholder="پیش‌فرض: لوگوی اصلی لایکو">
                <button type="button" class="button laico-pick-media" data-target="#laico_logo_url">انتخاب لوگو</button>
              </div>
            </div>

            <div class="laico-meta-field">
              <label><strong>آیکون سایت (Favicon)</strong></label>
              <div class="laico-media-input-row">
                <input type="url" id="laico_favicon_url" name="laico_opt[favicon_url]" value="<?php echo esc_attr((string) $opt['favicon_url']); ?>" class="widefat" dir="ltr" placeholder="پیش‌فرض: favicon.svg">
                <button type="button" class="button laico-pick-media" data-target="#laico_favicon_url">انتخاب آیکون</button>
              </div>
            </div>

            <div class="laico-meta-field">
              <label><strong>فعال‌سازی بخش زبان انگلیسی سایت (FA / EN)</strong></label>
              <p style="margin-top:8px;">
                <label>
                  <input type="checkbox" id="laico_enable_english" name="laico_opt[enable_english]" value="1" <?php checked(! empty($opt['enable_english'])); ?>>
                  نمایش بخش و سوییچر زبان انگلیسی (FA / EN) در سایت و فعال‌سازی فیلدهای ترجمهٔ انگلیسی
                </label>
              </p>
              <p class="description">سایت به‌صورت پیش‌فرض دوزبانه است (فارسی / انگلیسی). اگر این تیک را بردارید، سایت فقط فارسی نمایش داده می‌شود و سوییچر زبان از هدر حذف می‌شود؛ ترجمه‌های انگلیسی ثبت‌شده پاک نمی‌شوند.</p>
            </div>

            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label for="laico_default_lang"><strong>زبان پیش‌فرض وب‌سایت</strong></label>
              <select id="laico_default_lang" name="laico_opt[default_language]" class="widefat">
                <option value="fa" <?php selected($opt['default_language'], 'fa'); ?>>فارسی — راست‌به‌چپ (FA / RTL)</option>
                <option value="en" <?php selected($opt['default_language'], 'en'); ?>>انگلیسی — چپ‌به‌راست (EN / LTR)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="laico-card">
          <h2>اطلاعات تماس، آدرس شو روم و شبکه‌های اجتماعی</h2>
          <div class="laico-meta-grid laico-meta-grid--2col">
            <div class="laico-meta-field">
              <label><strong>شماره تماس اصلی (نمایشی فارسی)</strong></label>
              <input type="text" name="laico_opt[phone_primary]" value="<?php echo esc_attr((string) $opt['phone_primary']); ?>" class="widefat">
            </div>
            <div class="laico-meta-field">
              <label><strong>شماره تماس لینک <code>tel:</code> (انگلیسی)</strong></label>
              <input type="text" name="laico_opt[phone_primary_tel]" value="<?php echo esc_attr((string) $opt['phone_primary_tel']); ?>" class="widefat" dir="ltr">
            </div>

            <div class="laico-meta-field">
              <label><strong>شماره تماس دوم (اختیاری)</strong></label>
              <input type="text" name="laico_opt[phone_secondary]" value="<?php echo esc_attr((string) $opt['phone_secondary']); ?>" class="widefat">
            </div>
            <div class="laico-meta-field">
              <label><strong>ایمیل رسمی استودیو</strong></label>
              <input type="email" name="laico_opt[email]" value="<?php echo esc_attr((string) $opt['email']); ?>" class="widefat" dir="ltr">
            </div>

            <div class="laico-meta-field">
              <label><strong>آدرس شو روم و کارگاه (فارسی)</strong></label>
              <input type="text" name="laico_opt[address_fa]" value="<?php echo esc_attr((string) $opt['address_fa']); ?>" class="widefat">
            </div>
            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>آدرس شو روم و کارگاه (انگلیسی)</strong></label>
              <input type="text" name="laico_opt[address_en]" value="<?php echo esc_attr((string) $opt['address_en']); ?>" class="widefat" dir="ltr">
            </div>

            <div class="laico-meta-field">
              <label><strong>ساعات کاری (فارسی)</strong></label>
              <input type="text" name="laico_opt[hours_fa]" value="<?php echo esc_attr((string) $opt['hours_fa']); ?>" class="widefat">
            </div>
            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>ساعات کاری (انگلیسی)</strong></label>
              <input type="text" name="laico_opt[hours_en]" value="<?php echo esc_attr((string) $opt['hours_en']); ?>" class="widefat" dir="ltr">
            </div>

            <div class="laico-meta-field">
              <label><strong>لینک واتس‌اپ</strong></label>
              <input type="url" name="laico_opt[whatsapp_url]" value="<?php echo esc_attr((string) $opt['whatsapp_url']); ?>" class="widefat" dir="ltr">
            </div>
            <div class="laico-meta-field">
              <label><strong>لینک تلگرام</strong></label>
              <input type="url" name="laico_opt[telegram_url]" value="<?php echo esc_attr((string) $opt['telegram_url']); ?>" class="widefat" dir="ltr">
            </div>

            <div class="laico-meta-field">
              <label><strong>لینک اینستاگرام</strong></label>
              <input type="url" name="laico_opt[instagram_url]" value="<?php echo esc_attr((string) $opt['instagram_url']); ?>" class="widefat" dir="ltr">
            </div>
            <div class="laico-meta-field">
              <label><strong>لینک یوتیوب</strong></label>
              <input type="url" name="laico_opt[youtube_url]" value="<?php echo esc_attr((string) $opt['youtube_url']); ?>" class="widefat" dir="ltr">
            </div>

            <div class="laico-meta-field">
              <label><strong>لینک مسیریابی روی نقشه (گوگل مپ / نشان)</strong></label>
              <input type="url" name="laico_opt[map_link_url]" value="<?php echo esc_attr((string) $opt['map_link_url']); ?>" class="widefat" dir="ltr">
            </div>
            <div class="laico-meta-field">
              <label><strong>مختصات جغرافیایی (طول / عرض برای نقشه و اسکیما)</strong></label>
              <div style="display:flex;gap:8px;">
                <input type="text" name="laico_opt[map_lat]" value="<?php echo esc_attr((string) $opt['map_lat']); ?>" class="widefat" dir="ltr" placeholder="Lat: 36.4696">
                <input type="text" name="laico_opt[map_lng]" value="<?php echo esc_attr((string) $opt['map_lng']); ?>" class="widefat" dir="ltr" placeholder="Lng: 52.3507">
              </div>
            </div>
          </div>
        </div>

        <div class="laico-card">
          <h2>متن‌های فوتر (<code>footer-brand</code> و <code>footer-legal</code>)</h2>
          <p class="description">در فیلدهای فوتر، استفاده از تگ لینک <code>&lt;a href="..." target="_blank" rel="noopener"&gt;...&lt;/a&gt;</code> و بولد <code>&lt;strong&gt;...&lt;/strong&gt;</code> یا <code>&lt;b&gt;...&lt;/b&gt;</code> کاملاً مجاز و فعال است.</p>
          <div class="laico-meta-grid laico-meta-grid--2col" style="margin-top:12px;">
            <div class="laico-meta-field">
              <label><strong>متن معرفی برند زیر لوگوی فوتر — <code>footer-brand</code> (فارسی)</strong></label>
              <input type="text" name="laico_opt[footer_tagline_fa]" value="<?php echo esc_attr((string) $opt['footer_tagline_fa']); ?>" class="widefat" placeholder="لایکودیزاین مجموعه‌ی معماری و طراحی داخلی">
            </div>
            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>متن معرفی برند زیر لوگوی فوتر (انگلیسی)</strong></label>
              <input type="text" name="laico_opt[footer_tagline_en]" value="<?php echo esc_attr((string) $opt['footer_tagline_en']); ?>" class="widefat" dir="ltr">
            </div>

            <div class="laico-meta-field">
              <label><strong>متن کپی‌رایت در نوار پایین فوتر — <code>footer-legal</code> (فارسی)</strong></label>
              <input type="text" name="laico_opt[footer_copy_fa]" value="<?php echo esc_attr((string) $opt['footer_copy_fa']); ?>" class="widefat" placeholder="تمامی حقوق این سایت محفوظ می‌باشد.">
              <small style="color:#5c6f84;">پشتیبانی از تگ‌های <code>&lt;a&gt;</code> و <code>&lt;strong&gt;</code> / <code>&lt;b&gt;</code></small>
            </div>
            <div class="laico-meta-field">
              <label><strong>متن طراحی و اجرا در نوار پایین فوتر — <code>footer-legal</code> (فارسی)</strong></label>
              <input type="text" name="laico_opt[footer_credit_fa]" value="<?php echo esc_attr((string) $opt['footer_credit_fa']); ?>" class="widefat" placeholder="طراحی و اجرا آمل نوین">
              <small style="color:#5c6f84;">مثال: <code>طراحی و اجرا &lt;a href="https://amolnovin.ir" target="_blank" rel="noopener"&gt;&lt;strong&gt;آمل نوین&lt;/strong&gt;&lt;/a&gt;</code></small>
            </div>

            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>متن کپی‌رایت فوتر (انگلیسی)</strong></label>
              <input type="text" name="laico_opt[footer_copy_en]" value="<?php echo esc_attr((string) $opt['footer_copy_en']); ?>" class="widefat" dir="ltr">
            </div>
            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>متن طراحی و اجرا فوتر (انگلیسی)</strong></label>
              <input type="text" name="laico_opt[footer_credit_en]" value="<?php echo esc_attr((string) $opt['footer_credit_en']); ?>" class="widefat" dir="ltr">
            </div>
          </div>
        </div>
        <?php
    }

    private function renderQuickToolsCards(): void
    {
        ?>
        <div class="laico-detail-grid" style="margin-top:20px;">
          <div class="laico-card">
            <h2>🚀 راه‌اندازی یک‌کلیکی برگه‌های اصلی، پروژه‌ها و مجله</h2>
            <p>با اجرای این ابزار، ۶ برگهٔ اصلی (<strong>صفحه اصلی، خدمات، پروژه‌ها، مجله، درباره ما، تماس با ما</strong>) به همراه برگه‌های فرعی، تصاویر شاخص هیرو، ۱۵ پروژهٔ نمونه و ۶ مقالهٔ مجله بدون ایجاد تکراری ساخته و تنظیم می‌شوند.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . self::MENU_SLUG . '&tab=general')); ?>">
              <?php wp_nonce_field('laico_import_demo_content'); ?>
              <input type="hidden" name="laico_admin_action" value="import_demo">
              <button type="submit" class="button button-secondary" onclick="return confirm('برگه‌ها و محتوای نمونه ایجاد شوند؟ (موارد موجود تکراری نخواهند شد)');">اجرای درون‌ریزی هوشمند محتوای اولیه</button>
            </form>
          </div>

          <div class="laico-card">
            <h2>↺ بازنشانی تنظیمات قالب</h2>
            <p>در صورتی که می‌خواهید فقط تنظیمات این پنل به حالت پیش‌فرض اولیه برگردد (پروژه‌ها، نوشته‌ها و ارسالی‌های فرم حذف نمی‌شوند):</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . self::MENU_SLUG . '&tab=general')); ?>">
              <?php wp_nonce_field('laico_reset_theme_options'); ?>
              <input type="hidden" name="laico_admin_action" value="reset_options">
              <button type="submit" class="button" onclick="return confirm('آیا از بازنشانی تنظیمات قالب مطمئن هستید؟');">بازنشانی به تنظیمات کارخانه</button>
            </form>
          </div>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $opt
     */
    private function renderSectionsTab(array $opt): void
    {
        $toggles = [
            'sec_home_collections' => 'نمایش بخش «۰۱ — مجموعه‌ها» (آشپزخانه، پذیرایی، حمام، کافه)',
            'sec_home_projects' => 'نمایش بخش «۰۲ — پروژه‌های منتخب» (موزاییک ۴ پروژهٔ ویژه از بخش پروژه‌ها)',
            'sec_home_about' => 'نمایش بخش «۰۳ — استودیو و شمارنده‌های آمار»',
            'sec_home_craft' => 'نمایش بخش «۰۴ — مرام ساخت و متریال صادق»',
            'sec_home_cta' => 'نمایش بخش «۰۵ — فرم دعوت به مشاوره» در پایین صفحه اصلی',
        ];
        ?>
        <div class="laico-card">
          <h2>فعال‌سازی یا پنهان‌سازی بخش‌های صفحه اصلی</h2>
          <p class="description">هر یک از سرفصل‌های صفحهٔ نخست را می‌توانید بدون ویرایش کد روشن یا خاموش کنید:</p>
          <div class="laico-checkbox-list">
            <?php foreach ($toggles as $key => $label) : ?>
              <label class="laico-toggle-row">
                <input type="checkbox" name="laico_opt[<?php echo esc_attr($key); ?>]" value="1" <?php checked(! empty($opt[$key])); ?>>
                <span><?php echo esc_html($label); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="laico-card">
          <h2>متن‌های پیش‌فرض هیرو صفحه اصلی</h2>
          <p class="description">تصویر پس‌زمینهٔ هیرو از <strong>«تصویر شاخص برگهٔ صفحه اصلی»</strong> خوانده می‌شود. متن‌های زیر در صورتی استفاده می‌شوند که در متاباکس خود برگه متنی جایگزین نشده باشد:</p>
          <div class="laico-meta-grid laico-meta-grid--2col">
            <div class="laico-meta-field">
              <label><strong>کیکر بالای تیتر (فارسی)</strong></label>
              <input type="text" name="laico_opt[home_kicker_fa]" value="<?php echo esc_attr((string) $opt['home_kicker_fa']); ?>" class="widefat">
            </div>
            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>کیکر بالای تیتر (انگلیسی)</strong></label>
              <input type="text" name="laico_opt[home_kicker_en]" value="<?php echo esc_attr((string) $opt['home_kicker_en']); ?>" class="widefat" dir="ltr">
            </div>
            <div class="laico-meta-field">
              <label><strong>عنوان H1 هیرو (فارسی)</strong></label>
              <input type="text" name="laico_opt[home_title_fa]" value="<?php echo esc_attr((string) $opt['home_title_fa']); ?>" class="widefat">
            </div>
            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>عنوان H1 هیرو (انگلیسی)</strong></label>
              <input type="text" name="laico_opt[home_title_en]" value="<?php echo esc_attr((string) $opt['home_title_en']); ?>" class="widefat" dir="ltr">
            </div>
            <div class="laico-meta-field">
              <label><strong>زیرعنوان هیرو (فارسی)</strong></label>
              <textarea name="laico_opt[home_lede_fa]" rows="2" class="widefat"><?php echo esc_textarea((string) $opt['home_lede_fa']); ?></textarea>
            </div>
            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>زیرعنوان هیرو (انگلیسی)</strong></label>
              <textarea name="laico_opt[home_lede_en]" rows="2" class="widefat" dir="ltr"><?php echo esc_textarea((string) $opt['home_lede_en']); ?></textarea>
            </div>
          </div>
        </div>

        <div class="laico-card">
          <h2>آمار بخش استودیو و صفحه درباره ما</h2>
          <div class="laico-meta-grid laico-meta-grid--3col">
            <?php for ($i = 1; $i <= 3; $i++) : ?>
              <div class="laico-meta-field">
                <label><strong>شمارنده <?php echo esc_html(Jalali::toPersianDigits($i)); ?> (عدد)</strong></label>
                <input type="text" name="laico_opt[stat_<?php echo $i; ?>_num]" value="<?php echo esc_attr((string) $opt["stat_{$i}_num"]); ?>" class="widefat" dir="ltr">
                <label style="margin-top:6px;display:block;">برچسب فارسی:</label>
                <input type="text" name="laico_opt[stat_<?php echo $i; ?>_fa]" value="<?php echo esc_attr((string) $opt["stat_{$i}_fa"]); ?>" class="widefat">
                <div class="laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
                  <label style="margin-top:6px;display:block;">برچسب انگلیسی:</label>
                  <input type="text" name="laico_opt[stat_<?php echo $i; ?>_en]" value="<?php echo esc_attr((string) $opt["stat_{$i}_en"]); ?>" class="widefat" dir="ltr">
                </div>
              </div>
            <?php endfor; ?>
          </div>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $opt
     */
    private function renderOptimizationTab(array $opt): void
    {
        $perfOptions = [
            'opt_preload_fonts' => ['پیش‌بارگذاری فونت‌های وزیرمتن (Font Preload)', 'افزودن تگ <link rel="preload" as="font" crossorigin> برای حذف پرش تایپوگرافی (CLS).'],
            'opt_preload_hero' => ['پیش‌بارگذاری هوشمند تصویر هیرو (LCP Preload)', 'پیش‌بارگذاری تصویر شاخص برگه در <head> با اولویت بالا (fetchpriority="high") برای بهبود شاخص LCP.'],
            'opt_zero_fouc_lang' => ['اسکریپت ضدپرش زبان و انیمیشن در <head>', 'اعمال فوری جهت صفحه (RTL/LTR) و کلاس mo-on پیش از رندر بدنه برای جلوگیری از پرش ظاهری (FOUC).'],
            'opt_defer_scripts' => ['بارگذاری غیرمسدودکننده اسکریپت‌های قالب (defer)', 'افزودن صفت defer به باندل جاوااسکریپت قالب برای آزادسازی رشتهٔ اصلی مرورگر.'],
            'opt_lazy_images' => ['بارگذاری تنبل تصاویر خارج از دید (Native Lazy-Loading)', 'فعال‌سازی loading="lazy" و decoding="async" روی تصاویر محتوا و گالری‌ها.'],
            'opt_disable_emojis' => ['حذف اسکریپت و استایل ایموجی‌های پیش‌فرض وردپرس', 'حذف wp-emoji-release.min.js از تمام صفحات فرانت‌اند.'],
            'opt_disable_embeds' => ['غیرفعال‌سازی اسکریپت‌های oEmbed اضافی وردپرس', 'جلوگیری از بارگذاری wp-embed.min.js و لینک‌های کشف oEmbed.'],
            'opt_dequeue_jquery_migrate' => ['حذف jQuery Migrate در فرانت‌اند', 'کاهش حجم جاوااسکریپت دریافتی بازدیدکنندگان بدون اختلال در پیشخوان.'],
            'opt_remove_dashicons_guest' => ['حذف فونت سنگین Dashicons برای کاربران مهمان', 'جلوگیری از دانلود ۴۰ کیلوبایت فونت آیکون پیشخوان برای بازدیدکنندگان عادی.'],
            'opt_disable_xmlrpc' => ['مسدودسازی پروتکل قدیمی XML-RPC و هدر X-Pingback', 'افزایش امنیت و جلوگیری از حملات Brute-force روی xmlrpc.php.'],
            'opt_remove_wp_version' => ['پنهان‌سازی نسخه وردپرس، RSD و wlwmanifest از هدر', 'پاک‌سازی تگ‌های غیرضروری <head> برای خروجی تمیز و امن.'],
            'opt_disable_self_pingbacks' => ['جلوگیری از ارسال پینگ‌بک داخلی (Self-Pingback)', 'عدم ایجاد بازتاب‌های خودکار هنگام لینک‌دهی داخلی در مقالات مجله.'],
            'opt_instant_page_prefetch' => ['پیش‌واکشی هوشمند لینک‌ها هنگام حرکت ماوس (Hover Prefetch)', 'بارگذاری پیش‌دستانهٔ صفحات داخلی هنگام قرارگیری نشانگر ماوس روی لینک‌های منو و کارت‌ها (فقط در اتصالات پرسرعت).'],
            'opt_page_loader' => ['نمایش لودینگ ظریف در وسط صفحه هنگام جابجایی بین صفحات', 'نمایش انیمیشن لودینگ مرکزی هنگام کلیک روی لینک‌های داخلی با مدیریت هوشمند بازگشت مرورگر (BFCache).'],
            'opt_webp_uploads' => ['تولید خودکار WebP با کیفیت و ابعاد اصلی برای تصاویر جدید کتابخانه رسانه', 'حفظ ابعاد اصلی تصاویر آپلودی و تولید نسخه‌های WebP با کیفیت ۹۲٪ در صورت پشتیبانی سرور.'],
        ];
        ?>
        <div class="laico-card">
          <h2>بهینه‌سازی سرعت، Core Web Vitals و پاک‌سازی هسته وردپرس</h2>
          <p class="description">تمامی بهینه‌سازی‌ها به‌صورت استاندارد با هوک‌های رسمی وردپرس پیاده‌سازی شده‌اند و با افزونه‌های کش سازگارند:</p>
          <div class="laico-checkbox-list">
            <?php foreach ($perfOptions as $key => [$title, $desc]) : ?>
              <label class="laico-toggle-row">
                <input type="checkbox" name="laico_opt[<?php echo esc_attr($key); ?>]" value="1" <?php checked(! empty($opt[$key])); ?>>
                <span>
                  <strong><?php echo esc_html($title); ?></strong><br>
                  <small style="color:#5c6f84;"><?php echo esc_html($desc); ?></small>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $opt
     */
    private function renderSeoTab(array $opt): void
    {
        $hasExternalSeo = SeoService::hasExternalSeoPlugin();
        ?>
        <?php if ($hasExternalSeo) : ?>
          <div class="notice notice-info inline" style="margin:0 0 16px;">
            <p><strong>تشخیص خودکار افزونه سئو:</strong> یک افزونه سئوی جانبی (مانند Yoast SEO یا Rank Math) روی سایت فعال است. قالب لایکو به‌صورت هوشمند از تولید تگ‌های متای تکراری جلوگیری می‌کند.</p>
          </div>
        <?php endif; ?>

        <div class="laico-card">
          <h2>موتور سئوی داخلی، Open Graph و داده‌های ساختاریافته (Schema JSON-LD)</h2>
          <div class="laico-checkbox-list">
            <label class="laico-toggle-row">
              <input type="checkbox" name="laico_opt[seo_enable_builtin]" value="1" <?php checked(! empty($opt['seo_enable_builtin'])); ?>>
              <span><strong>فعال‌سازی موتور سئوی داخلی قالب</strong> (تولید Canonical، Meta Description، Open Graph و Twitter Cards در صورت عدم نصب افزونه جانبی)</span>
            </label>
            <label class="laico-toggle-row">
              <input type="checkbox" name="laico_opt[seo_schema_org]" value="1" <?php checked(! empty($opt['seo_schema_org'])); ?>>
              <span><strong>تولید خودکار اسکیما JSON-LD</strong> (<code>ArchitectureFirm / HomeAndConstructionBusiness</code>، <code>WebSite</code>، <code>Article</code> برای مجله و <code>CreativeWork</code> برای پروژه‌ها)</span>
            </label>
            <label class="laico-toggle-row">
              <input type="checkbox" name="laico_opt[seo_redirect_legacy_html]" value="1" <?php checked(! empty($opt['seo_redirect_legacy_html'])); ?>>
              <span><strong>ریدایرکت ۳۰۱ خودکار آدرس‌های قدیمی <code>.html</code></strong> (هدایت <code>/services.html</code>، <code>/project-*.html</code> و <code>/article-*.html</code> به پیوندهای یکتای وردپرس برای حفظ رتبهٔ گوگل)</span>
            </label>
            <label class="laico-toggle-row">
              <input type="checkbox" name="laico_opt[seo_noindex_search]" value="1" <?php checked(! empty($opt['seo_noindex_search'])); ?>>
              <span>اعمال <code>noindex, follow</code> روی نتایج جستجوی داخلی سایت</span>
            </label>
            <label class="laico-toggle-row">
              <input type="checkbox" name="laico_opt[seo_noindex_author]" value="1" <?php checked(! empty($opt['seo_noindex_author'])); ?>>
              <span>اعمال <code>noindex, follow</code> روی بایگانی نویسندگان (جلوگیری از محتوای تکراری در سایت‌های استودیویی)</span>
            </label>
          </div>

          <div class="laico-meta-grid laico-meta-grid--2col" style="margin-top:18px;">
            <div class="laico-meta-field">
              <label><strong>جداکننده عنوان صفحات (Title Separator)</strong></label>
              <input type="text" name="laico_opt[seo_title_separator]" value="<?php echo esc_attr((string) $opt['seo_title_separator']); ?>" class="widefat" style="max-width:120px;">
            </div>
            <div class="laico-meta-field">
              <label><strong>تصویر پیش‌فرض اشتراک‌گذاری شبکه‌های اجتماعی (Open Graph Image)</strong></label>
              <div class="laico-media-input-row">
                <input type="url" id="laico_seo_og_img" name="laico_opt[seo_default_og_image]" value="<?php echo esc_attr((string) $opt['seo_default_og_image']); ?>" class="widefat" dir="ltr">
                <button type="button" class="button laico-pick-media" data-target="#laico_seo_og_img">انتخاب تصویر</button>
              </div>
            </div>

            <div class="laico-meta-field">
              <label><strong>توضیحات متای صفحه اصلی و پیش‌فرض (فارسی)</strong></label>
              <textarea name="laico_opt[seo_home_description_fa]" rows="3" class="widefat"><?php echo esc_textarea((string) $opt['seo_home_description_fa']); ?></textarea>
            </div>
            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>توضیحات متای پیش‌فرض (انگلیسی)</strong></label>
              <textarea name="laico_opt[seo_home_description_en]" rows="3" class="widefat" dir="ltr"><?php echo esc_textarea((string) $opt['seo_home_description_en']); ?></textarea>
            </div>

            <div class="laico-meta-field">
              <label><strong>کد تأیید Google Search Console</strong></label>
              <input type="text" name="laico_opt[seo_google_verification]" value="<?php echo esc_attr((string) $opt['seo_google_verification']); ?>" class="widefat" dir="ltr" placeholder="فقط مقدار content متاتگ">
            </div>
            <div class="laico-meta-field">
              <label><strong>کد تأیید Bing Webmaster</strong></label>
              <input type="text" name="laico_opt[seo_bing_verification]" value="<?php echo esc_attr((string) $opt['seo_bing_verification']); ?>" class="widefat" dir="ltr">
            </div>

            <div class="laico-meta-field">
              <label><strong>کدهای سفارشی درون <code>&lt;head&gt;</code> (آنالیتیکس، تگ منیجر و...)</strong></label>
              <textarea name="laico_opt[seo_head_code]" rows="4" class="widefat" dir="ltr"><?php echo esc_textarea((string) $opt['seo_head_code']); ?></textarea>
            </div>
            <div class="laico-meta-field">
              <label><strong>کدهای سفارشی قبل از <code>&lt;/body&gt;</code></strong></label>
              <textarea name="laico_opt[seo_footer_code]" rows="4" class="widefat" dir="ltr"><?php echo esc_textarea((string) $opt['seo_footer_code']); ?></textarea>
            </div>
          </div>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $opt
     */
    private function renderFormsTab(array $opt): void
    {
        $secStatus = SecurityIntegrationService::statusSummary();
        $smsStatus = IppanelSmsService::statusSummary();
        $savedToken = IppanelSmsService::getToken();
        ?>
        <div class="laico-card">
          <h2>وضعیت یکپارچه‌سازی امنیتی، کپچای ریاضی و سامانه پیامک</h2>
          <div class="laico-env-pills" style="margin-top:8px;">
            <span class="laico-env-pill <?php echo ! empty($secStatus['captcha_active']) ? 'is-ok' : 'is-warn'; ?>">
              <?php if (! empty($secStatus['captcha_active'])) : ?>
                ✓ کپچای ریاضی (WP Math Captcha <?php echo esc_html((string) $secStatus['captcha_version']); ?>): فعال روی تمام فرم‌های قالب
              <?php elseif (! empty($secStatus['captcha_installed'])) : ?>
                ⚠ افزونه WP Math Captcha نصب است اما از تنظیمات غیرفعال شده
              <?php else : ?>
                ○ افزونه WP Math Captcha نصب/فعال نیست (در صورت نصب، خودکار روی همه فرم‌ها فعال می‌شود)
              <?php endif; ?>
            </span>

            <span class="laico-env-pill <?php echo ! empty($secStatus['novin_active']) ? 'is-ok' : 'is-warn'; ?>">
              <?php if (! empty($secStatus['novin_active'])) : ?>
                ✓ سپر امنیتی نوین (Novin Security <?php echo esc_html((string) $secStatus['novin_version']); ?>): <?php echo ! empty($secStatus['novin_safe_mode']) ? 'حالت اضطراری (Safe Mode)' : 'متصل و محافظت فعال'; ?>
              <?php else : ?>
                ○ افزونه Novin Security Suite نصب/فعال نیست
              <?php endif; ?>
            </span>

            <span class="laico-env-pill <?php echo (! empty($smsStatus['plugin_active']) && ! empty($smsStatus['has_token'])) ? 'is-ok' : 'is-warn'; ?>">
              <?php if (! empty($smsStatus['plugin_active']) && ! empty($smsStatus['has_token'])) : ?>
                ✓ افزونه IPPanel Core (<?php echo esc_html((string) $smsStatus['plugin_version']); ?>): متصل و دارای کلید دسترسی فعال
              <?php elseif (! empty($smsStatus['plugin_active'])) : ?>
                ⚠ IPPanel Core فعال است — کلید دسترسی قالب را از «دسترسی افزونه‌ها» ایجاد کنید
              <?php else : ?>
                ○ افزونه IPPanel Core نصب/فعال نیست
              <?php endif; ?>
            </span>
          </div>
          <p class="description" style="margin-top:10px;">
            <?php if (! empty($secStatus['captcha_installed'])) : ?>
              <a href="<?php echo esc_url((string) $secStatus['captcha_url']); ?>" class="button button-small">تنظیمات کپچای ریاضی ↗</a>
            <?php endif; ?>
            <?php if (! empty($secStatus['novin_active'])) : ?>
              <a href="<?php echo esc_url((string) $secStatus['novin_url']); ?>" class="button button-small">داشبورد Novin Security ↗</a>
            <?php endif; ?>
            <?php if (! empty($smsStatus['plugin_active'])) : ?>
              <a href="<?php echo esc_url((string) $smsStatus['panel_url']); ?>" class="button button-small">پنل پیامک IPPanel Core ↗</a>
            <?php endif; ?>
          </p>
        </div>

        <div class="laico-card">
          <h2>ذخیره‌سازی سرور، اعلان ایمیلی/تلگرامی و امنیت ضد اسپم فرم‌ها</h2>
          <p class="description">تمامی فرم‌های مشاوره در صفحات سایت پس از بررسی توکن CSRF، لیست سیاه IP نوین سکیوریتی و کپچای ریاضی در جدول اختصاصی پایگاه‌داده ذخیره می‌شوند و در بخش <a href="<?php echo esc_url(admin_url('admin.php?page=laico-submissions')); ?>"><strong>ارسالی‌های فرم</strong></a> قابل مشاهده و خروجی اکسل هستند.</p>

          <div class="laico-checkbox-list" style="margin-top:12px;">
            <label class="laico-toggle-row">
              <input type="checkbox" name="laico_opt[form_enable_email]" value="1" <?php checked(! empty($opt['form_enable_email'])); ?>>
              <span>ارسال ایمیل اطلاع‌رسانی به مدیر هنگام ثبت درخواست مشاوره جدید</span>
            </label>
            <label class="laico-toggle-row">
              <input type="checkbox" name="laico_opt[form_store_ip]" value="1" <?php checked(! empty($opt['form_store_ip'])); ?>>
              <span>ثبت آدرس IP ارسال‌کننده برای جلوگیری از اسپم (غیرفعال‌سازی برای انطباق سخت‌گیرانه با حریم خصوصی)</span>
            </label>
          </div>

          <div class="laico-meta-grid laico-meta-grid--2col" style="margin-top:16px;">
            <div class="laico-meta-field">
              <label><strong>ایمیل دریافت‌کننده اعلان فرم‌ها</strong></label>
              <input type="email" name="laico_opt[form_notify_email]" value="<?php echo esc_attr((string) $opt['form_notify_email']); ?>" class="widefat" dir="ltr" placeholder="در صورت خالی بودن: ایمیل مدیرکل سایت">
            </div>
            <div class="laico-meta-field">
              <label><strong>حداکثر تعداد ارسال مجاز از هر IP در ۱۰ دقیقه (Rate Limit)</strong></label>
              <input type="number" name="laico_opt[form_rate_limit_max]" min="1" max="50" value="<?php echo esc_attr((string) $opt['form_rate_limit_max']); ?>" class="widefat" dir="ltr">
            </div>

            <div class="laico-meta-field">
              <label><strong>توکن ربات تلگرام جهت اعلان فوری (اختیاری)</strong></label>
              <input type="text" name="laico_opt[form_telegram_bot_token]" value="<?php echo esc_attr((string) $opt['form_telegram_bot_token']); ?>" class="widefat" dir="ltr" placeholder="123456789:ABCdef...">
            </div>
            <div class="laico-meta-field">
              <label><strong>شناسه چت / کانال تلگرام (Chat ID)</strong></label>
              <input type="text" name="laico_opt[form_telegram_chat_id]" value="<?php echo esc_attr((string) $opt['form_telegram_chat_id']); ?>" class="widefat" dir="ltr" placeholder="-100123456789">
            </div>

            <div class="laico-meta-field">
              <label><strong>تیتر پیام موفقیت ثبت فرم (فارسی)</strong></label>
              <input type="text" name="laico_opt[form_success_title_fa]" value="<?php echo esc_attr((string) $opt['form_success_title_fa']); ?>" class="widefat">
            </div>
            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>تیتر پیام موفقیت ثبت فرم (انگلیسی)</strong></label>
              <input type="text" name="laico_opt[form_success_title_en]" value="<?php echo esc_attr((string) $opt['form_success_title_en']); ?>" class="widefat" dir="ltr">
            </div>

            <div class="laico-meta-field">
              <label><strong>متن پیام موفقیت ثبت فرم (فارسی)</strong></label>
              <input type="text" name="laico_opt[form_success_body_fa]" value="<?php echo esc_attr((string) $opt['form_success_body_fa']); ?>" class="widefat">
            </div>
            <div class="laico-meta-field laico-en-only-field" <?php echo empty($opt['enable_english']) ? 'style="display:none;"' : ''; ?>>
              <label><strong>متن پیام موفقیت ثبت فرم (انگلیسی)</strong></label>
              <input type="text" name="laico_opt[form_success_body_en]" value="<?php echo esc_attr((string) $opt['form_success_body_en']); ?>" class="widefat" dir="ltr">
            </div>
          </div>
        </div>

        <div class="laico-card">
          <h2>📱 اتصال به افزونه مرجع IPPanel Core (ارسال پیامک پترنی و دستی به مدیر و مشتری)</h2>
          <p class="description">
            قالب لایکو دیزاین به‌صورت خودکار با کد شناسایی <code dir="ltr"><?php echo esc_html((string) $smsStatus['integration_code']); ?></code> و اسلاگ <code dir="ltr"><?php echo esc_html((string) $smsStatus['slug']); ?></code> در رجیستری <strong>IPPanel Core</strong> ثبت می‌شود.
            کافی است در منوی <strong>پیامک IPPanel ← دسترسی افزونه‌ها</strong> گزینهٔ «قالب لایکو دیزاین» را انتخاب و روی «ایجاد کلید دسترسی» کلیک کنید تا توکن به‌صورت خودکار دریافت و ذخیره شود.
          </p>

          <div class="laico-meta-grid laico-meta-grid--2col" style="margin-top:14px;">
            <div class="laico-meta-field">
              <label><strong>توکن دسترسی IPPanel Core (دریافت خودکار یا ورود دستی اختیاری)</strong></label>
              <input type="text" name="laico_opt[sms_ippanel_token]" value="<?php echo esc_attr((string) ($opt['sms_ippanel_token'] ?: $savedToken)); ?>" class="widefat" dir="ltr" placeholder="در صورت صدور کلید از IPPanel Core به‌طور خودکار پر می‌شود">
            </div>
            <div class="laico-meta-field">
              <label><strong>متغیرهای قابل استفاده در پیامک‌ها</strong></label>
              <p class="description" style="margin:6px 0 0;line-height:1.8;">
                <code>{id}</code> کد پیگیری &nbsp;|&nbsp;
                <code>{name}</code> نام مشتری &nbsp;|&nbsp;
                <code>{phone_local}</code> موبایل مشتری (09...) &nbsp;|&nbsp;
                <code>{project_type}</code> نوع پروژه &nbsp;|&nbsp;
                <code>{source_page}</code> صفحهٔ فرم &nbsp;|&nbsp;
                <code>{message}</code> متن پیام &nbsp;|&nbsp;
                <code>{date}</code> تاریخ شمسی
              </p>
            </div>
          </div>

          <hr style="margin:22px 0;border:0;border-top:1px solid #e2e8f0;">

          <h3 style="margin-top:0;">۱. پیامک اطلاع‌رسانی به مدیر سایت (Admin SMS)</h3>
          <label class="laico-toggle-row" style="margin-bottom:14px;">
            <input type="checkbox" name="laico_opt[sms_admin_enable]" value="1" <?php checked(! empty($opt['sms_admin_enable'])); ?>>
            <span><strong>فعال‌سازی ارسال پیامک به مدیر سایت بلافاصله پس از ثبت فرم مشاوره</strong></span>
          </label>

          <div class="laico-meta-grid laico-meta-grid--2col">
            <div class="laico-meta-field">
              <label><strong>شماره موبایل مدیر (یا چند شماره با کاما <code>,</code>)</strong></label>
              <input type="text" name="laico_opt[sms_admin_phones]" value="<?php echo esc_attr((string) $opt['sms_admin_phones']); ?>" class="widefat" dir="ltr" placeholder="مثلاً: 09120898938, 0911... (در صورت خالی بودن از شماره اصلی سایت استفاده می‌شود)">
            </div>
            <div class="laico-meta-field">
              <label><strong>نوع ارسال پیامک مدیر</strong></label>
              <select name="laico_opt[sms_admin_mode]" class="widefat" data-sms-mode-select="admin">
                <option value="pattern" <?php selected((string) ($opt['sms_admin_mode'] ?? 'pattern'), 'pattern'); ?>>ارسال سریع با پترن / الگو (Pattern SMS)</option>
                <option value="text" <?php selected((string) ($opt['sms_admin_mode'] ?? 'pattern'), 'text'); ?>>ارسال پیامک متنی دستی (خط خدماتی / تبلیغاتی)</option>
              </select>
            </div>

            <div class="laico-meta-field" data-sms-mode-panel="admin:pattern" <?php echo (($opt['sms_admin_mode'] ?? 'pattern') !== 'pattern') ? 'style="display:none;"' : ''; ?>>
              <label><strong>کد پترن (Pattern Code) مدیر در IPPanel</strong></label>
              <input type="text" name="laico_opt[sms_admin_pattern_code]" value="<?php echo esc_attr((string) $opt['sms_admin_pattern_code']); ?>" class="widefat" dir="ltr" placeholder="مثلاً: k8m2p9x1ab">
            </div>
            <div class="laico-meta-field" data-sms-mode-panel="admin:pattern" <?php echo (($opt['sms_admin_mode'] ?? 'pattern') !== 'pattern') ? 'style="display:none;"' : ''; ?>>
              <label><strong>متغیرهای پترن مدیر (هر خط به صورت <code>نام_متغیر={مقدار}</code>)</strong></label>
              <textarea name="laico_opt[sms_admin_pattern_vars]" rows="4" class="widefat" dir="ltr"><?php echo esc_textarea((string) $opt['sms_admin_pattern_vars']); ?></textarea>
            </div>

            <div class="laico-meta-field" style="grid-column: 1 / -1; <?php echo (($opt['sms_admin_mode'] ?? 'pattern') !== 'text') ? 'display:none;' : ''; ?>" data-sms-mode-panel="admin:text">
              <label><strong>متن پیامک دستی برای مدیر</strong></label>
              <textarea name="laico_opt[sms_admin_text_template]" rows="4" class="widefat"><?php echo esc_textarea((string) $opt['sms_admin_text_template']); ?></textarea>
            </div>
          </div>

          <hr style="margin:22px 0;border:0;border-top:1px solid #e2e8f0;">

          <h3 style="margin-top:0;">۲. پیامک تأیید ثبت درخواست به مشتری (Customer SMS)</h3>
          <label class="laico-toggle-row" style="margin-bottom:14px;">
            <input type="checkbox" name="laico_opt[sms_customer_enable]" value="1" <?php checked(! empty($opt['sms_customer_enable'])); ?>>
            <span><strong>فعال‌سازی ارسال پیامک تشکر و کد پیگیری به شماره موبایل مشتری</strong></span>
          </label>

          <div class="laico-meta-grid laico-meta-grid--2col">
            <div class="laico-meta-field">
              <label><strong>نوع ارسال پیامک مشتری</strong></label>
              <select name="laico_opt[sms_customer_mode]" class="widefat" data-sms-mode-select="customer">
                <option value="pattern" <?php selected((string) ($opt['sms_customer_mode'] ?? 'pattern'), 'pattern'); ?>>ارسال سریع با پترن / الگو (Pattern SMS — پیشنهادی برای عبور از بلک‌لیست)</option>
                <option value="text" <?php selected((string) ($opt['sms_customer_mode'] ?? 'pattern'), 'text'); ?>>ارسال پیامک متنی دستی</option>
              </select>
            </div>
            <div class="laico-meta-field" data-sms-mode-panel="customer:pattern" <?php echo (($opt['sms_customer_mode'] ?? 'pattern') !== 'pattern') ? 'style="display:none;"' : ''; ?>>
              <label><strong>کد پترن (Pattern Code) مشتری در IPPanel</strong></label>
              <input type="text" name="laico_opt[sms_customer_pattern_code]" value="<?php echo esc_attr((string) $opt['sms_customer_pattern_code']); ?>" class="widefat" dir="ltr" placeholder="مثلاً: q4w9z7n2cd">
            </div>

            <div class="laico-meta-field" style="grid-column: 1 / -1; <?php echo (($opt['sms_customer_mode'] ?? 'pattern') !== 'pattern') ? 'display:none;' : ''; ?>" data-sms-mode-panel="customer:pattern">
              <label><strong>متغیرهای پترن مشتری (هر خط به صورت <code>نام_متغیر={مقدار}</code>)</strong></label>
              <textarea name="laico_opt[sms_customer_pattern_vars]" rows="3" class="widefat" dir="ltr"><?php echo esc_textarea((string) $opt['sms_customer_pattern_vars']); ?></textarea>
            </div>

            <div class="laico-meta-field" style="grid-column: 1 / -1; <?php echo (($opt['sms_customer_mode'] ?? 'pattern') !== 'text') ? 'display:none;' : ''; ?>" data-sms-mode-panel="customer:text">
              <label><strong>متن پیامک دستی برای مشتری</strong></label>
              <textarea name="laico_opt[sms_customer_text_template]" rows="3" class="widefat"><?php echo esc_textarea((string) $opt['sms_customer_text_template']); ?></textarea>
            </div>
          </div>
        </div>
        <?php
    }

    /**
     * Dedicated Tab 6: Secure Patch-based Theme Updater (`نصب بروزرسانی‌های قالب از طریق پچ`).
     *
     * @param array<string, mixed> $opt
     */
    private function renderPatchUpdaterTab(array $opt): void
    {
        $perm = PatchUpdaterService::checkEnvironmentPermissions();
        $history = PatchUpdaterService::getHistory();
        $tabUrl = add_query_arg(['page' => self::MENU_SLUG, 'tab' => 'patch'], admin_url('admin.php'));
        ?>
        <!-- System Readiness Strip -->
        <div class="laico-card laico-patch-status-card">
          <h2>وضعیت آمادگی و ایمنی سامانه بروزرسانی پچ</h2>
          <div class="laico-env-pills">
            <span class="laico-env-pill <?php echo $perm['allowed'] ? 'is-ok' : 'is-err'; ?>">
              <?php echo $perm['allowed'] ? '✓ مجوز نوشتن و بروزرسانی فعال' : '✕ ' . esc_html($perm['reason']); ?>
            </span>
            <span class="laico-env-pill <?php echo class_exists(\ZipArchive::class) ? 'is-ok' : 'is-warn'; ?>">
              ZipArchive: <?php echo class_exists(\ZipArchive::class) ? 'فعال' : 'غیرفعال'; ?>
            </span>
            <span class="laico-env-pill <?php echo function_exists('sodium_crypto_sign_verify_detached') ? 'is-ok' : 'is-warn'; ?>">
              امضای Ed25519 (Sodium): <?php echo function_exists('sodium_crypto_sign_verify_detached') ? 'فعال' : 'غیرفعال'; ?>
            </span>
            <span class="laico-env-pill is-ok">
              بررسی سینتکس PHP پیش از اعمال: فعال
            </span>
            <span class="laico-env-pill is-ok">
              نسخه فعلی قالب: <strong dir="ltr"><?php echo esc_html(PatchUpdaterService::currentThemeVersion()); ?></strong>
            </span>
          </div>
        </div>

        <!-- Staged Dry-Run Confirmation Card (shown right after uploading or fetching a patch) -->
        <?php if ($this->stagedPatchPreview !== null) :
            $p = $this->stagedPatchPreview;
        ?>
          <div class="laico-card laico-staged-box">
            <h2>🔍 پیش‌نمایش و تأیید نهایی پچ (نتیجه بررسی آزمایشی Dry-Run)</h2>
            <p>
              <strong>شناسه پچ:</strong> <code><?php echo esc_html((string) $p['meta']['id']); ?></code> &nbsp;|&nbsp;
              <strong>نسخه هدف:</strong> <code><?php echo esc_html((string) ($p['meta']['to_version'] ?: 'بدون تغییر نسخه')); ?></code> &nbsp;|&nbsp;
              <strong>وضعیت امضا:</strong> <?php echo ! empty($p['signature_verified']) ? '<span style="color:#137333;font-weight:700;">✓ امضای دیجیتال تأیید شد</span>' : '<span style="color:#6d7e90;">بدون امضا (تأیید با هش SHA-256)</span>'; ?> &nbsp;|&nbsp;
              <strong>تعداد کل فایل‌ها:</strong> <?php echo esc_html(Jalali::toPersianDigits((int) $p['counts']['total'])); ?>
            </p>
            <?php if (! empty($p['meta']['description'])) : ?>
              <p><strong>توضیحات پچ:</strong> <?php echo esc_html((string) $p['meta']['description']); ?></p>
            <?php endif; ?>

            <table class="widefat striped" style="margin:12px 0;">
              <thead>
                <tr>
                  <th style="width:120px;">عملیات</th>
                  <th>مسیر نسبی در قالب</th>
                  <th style="width:120px;">حجم جدید</th>
                  <th style="width:160px;">خلاصه هش SHA-256</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($p['files'] as $f) : ?>
                  <tr>
                    <td>
                      <?php if ($f['action'] === 'modify') : ?>
                        <span class="laico-status-badge laico-status-badge--contacted">ویرایش فایل</span>
                      <?php elseif ($f['action'] === 'add') : ?>
                        <span class="laico-status-badge laico-status-badge--read">افزودن فایل</span>
                      <?php else : ?>
                        <span class="laico-status-badge laico-status-badge--new">حذف فایل</span>
                      <?php endif; ?>
                    </td>
                    <td dir="ltr"><code><?php echo esc_html((string) $f['path']); ?></code></td>
                    <td dir="ltr"><?php echo $f['size'] > 0 ? esc_html(number_format($f['size'] / 1024, 1) . ' KB') : '—'; ?></td>
                    <td dir="ltr"><code><?php echo esc_html((string) $f['sha256']); ?></code></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <form method="post" action="<?php echo esc_url($tabUrl); ?>">
              <?php wp_nonce_field('laico_patch_apply_staged'); ?>
              <input type="hidden" name="laico_admin_action" value="patch_apply_staged">
              <input type="hidden" name="staged_token" value="<?php echo esc_attr((string) $p['token']); ?>">
              <button type="submit" class="button button-primary button-hero">✓ تأیید نهایی، تهیه پشتیبان خودکار و نصب اتمیک پچ</button>
              <a href="<?php echo esc_url($tabUrl); ?>" class="button button-large" style="margin-right:8px;">انصراف</a>
            </form>
          </div>
        <?php endif; ?>

        <div class="laico-detail-grid">
          <!-- Upload Patch Package (.zip / .patch / .diff) -->
          <div class="laico-card">
            <h2>۱. بارگذاری و بررسی بستهٔ پچ</h2>
            <p>فایل بروزرسانی قالب را در یکی از دو قالب استاندارد انتخاب کنید:</p>
            <ul style="list-style:disc;padding-right:20px;line-height:1.9;color:#4a5d73;">
              <li><strong>بستهٔ <code>.zip</code> استاندارد لایکو</strong> حاوی فایل <code>patch.json</code> (شامل چک‌سام SHA-256، کنترل نسخه و امضای اختیاری Ed25519/HMAC) — ساخته‌شده با ابزار <code>bin/make-patch.py</code>.</li>
              <li><strong>فایل <code>.patch</code> یا <code>.diff</code></strong> استاندارد Git Unified Diff (موتور تفاضلی خالص PHP بدون نیاز به <code>exec</code> در سرور).</li>
            </ul>

            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($tabUrl); ?>" style="margin-top:16px;">
              <?php wp_nonce_field('laico_patch_stage_upload'); ?>
              <input type="hidden" name="laico_admin_action" value="patch_stage_upload">
              <p>
                <input type="file" name="patch_file" accept=".zip,.patch,.diff" required <?php disabled(! $perm['allowed']); ?>>
              </p>
              <p style="margin-top:12px;">
                <button type="submit" class="button button-primary button-large" <?php disabled(! $perm['allowed']); ?>>
                  بررسی آزمایشی و صحت‌سنجی پچ (Dry-Run)
                </button>
              </p>
            </form>
          </div>

          <!-- Remote Update Check -->
          <div class="laico-card">
            <h2>۲. بررسی سرور بروزرسانی از راه دور</h2>
            <p>در صورت تنظیم آدرس مانیفست JSON در بخش تنظیمات امنیتی پایین، می‌توانید آخرین پچ منتشرشده را مستقیم استعلام و بررسی کنید:</p>
            <form method="post" action="<?php echo esc_url($tabUrl); ?>" style="margin-top:12px;">
              <?php wp_nonce_field('laico_patch_check_remote'); ?>
              <input type="hidden" name="laico_admin_action" value="patch_check_remote">
              <button type="submit" class="button button-secondary">بررسی وجود پچ جدید در سرور</button>
            </form>

            <?php if ($this->remoteManifestResult !== null && ! empty($this->remoteManifestResult['ok'])) :
                $rm = $this->remoteManifestResult;
            ?>
              <div style="margin-top:14px;padding:12px;background:#f4f6f9;border-radius:8px;">
                <p style="margin:0 0 6px;">
                  <strong>آخرین نسخه سرور:</strong> <code dir="ltr"><?php echo esc_html((string) $rm['latest_version']); ?></code>
                  (نسخه فعلی شما: <code dir="ltr"><?php echo esc_html((string) $rm['current_version']); ?></code>)
                </p>
                <?php if (! empty($rm['changelog'])) : ?>
                  <p style="margin:6px 0;font-size:12px;white-space:pre-line;"><?php echo esc_html((string) $rm['changelog']); ?></p>
                <?php endif; ?>
                <?php if (! empty($rm['has_update']) && ! empty($rm['patch_url'])) : ?>
                  <form method="post" action="<?php echo esc_url($tabUrl); ?>" style="margin-top:8px;">
                    <?php wp_nonce_field('laico_patch_stage_remote_url'); ?>
                    <input type="hidden" name="laico_admin_action" value="patch_stage_remote_url">
                    <input type="hidden" name="remote_patch_url" value="<?php echo esc_attr((string) $rm['patch_url']); ?>">
                    <button type="submit" class="button button-primary">دریافت و پیش‌نمایش این پچ</button>
                  </form>
                <?php else : ?>
                  <p style="margin:6px 0 0;color:#137333;font-weight:600;">✓ قالب شما به‌روز است.</p>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Patch Security Configuration Form -->
        <form method="post" action="<?php echo esc_url($tabUrl); ?>" class="laico-card" style="margin-top:20px;">
          <?php wp_nonce_field('laico_save_theme_options'); ?>
          <input type="hidden" name="laico_admin_action" value="save_options">
          <input type="hidden" name="active_tab" value="patch">

          <h2>تنظیمات امنیتی و امضای دیجیتال پچ</h2>
          <div class="laico-checkbox-list">
            <label class="laico-toggle-row">
              <input type="checkbox" name="laico_opt[patch_auto_backup]" value="1" <?php checked(! empty($opt['patch_auto_backup'])); ?>>
              <span><strong>تهیه نسخه پشتیبان خودکار پیش از نصب هر پچ (توصیه‌شده)</strong> — ذخیره آرشیو بازگردانی در پوشهٔ محافظت‌شده <code>uploads/laico-patches/backups/</code></span>
            </label>
            <label class="laico-toggle-row">
              <input type="checkbox" name="laico_opt[patch_require_signature]" value="1" <?php checked(! empty($opt['patch_require_signature'])); ?>>
              <span><strong>الزام امضای دیجیتال معتبر</strong> — رد کردن پچ‌های فاقد امضای Ed25519 یا HMAC-SHA256</span>
            </label>
          </div>

          <div class="laico-meta-grid laico-meta-grid--2col" style="margin-top:14px;">
            <div class="laico-meta-field">
              <label><strong>آدرس مانیفست بروزرسانی از راه دور (HTTPS JSON URL — اختیاری)</strong></label>
              <input type="url" name="laico_opt[patch_manifest_url]" value="<?php echo esc_attr((string) $opt['patch_manifest_url']); ?>" class="widefat" dir="ltr" placeholder="https://github.com/amolnovin/laico-theme/raw/main/releases/manifest.json">
            </div>
            <div class="laico-meta-field">
              <label><strong>کلید عمومی امضای Ed25519 (Base64 یا Hex — اختیاری)</strong></label>
              <input type="text" name="laico_opt[patch_public_key]" value="<?php echo esc_attr((string) $opt['patch_public_key']); ?>" class="widefat" dir="ltr">
            </div>
            <div class="laico-meta-field" style="grid-column: 1 / -1;">
              <label><strong>کلید رمز مشترک HMAC-SHA256 (اختیاری — هماهنگ با <code>--hmac-secret</code> در اسکریپت <code>bin/make-patch.py</code>)</strong></label>
              <input type="password" name="laico_opt[patch_hmac_secret]" value="<?php echo esc_attr((string) $opt['patch_hmac_secret']); ?>" class="widefat" dir="ltr" autocomplete="new-password">
            </div>
          </div>

          <p style="margin-top:14px;">
            <button type="submit" class="button button-primary">ذخیره تنظیمات امنیتی پچ</button>
          </p>
        </form>

        <!-- Installed Patches Audit Log & 1-Click Rollback -->
        <div class="laico-card" style="margin-top:20px;">
          <h2>تاریخچهٔ پچ‌های نصب‌شده و بازگردانی تک‌کلیکی (Rollback)</h2>
          <?php if (empty($history)) : ?>
            <p style="color:#6d7e90;">هنوز هیچ پچی از طریق این پنل نصب نشده است.</p>
          <?php else : ?>
            <table class="widefat striped">
              <thead>
                <tr>
                  <th>شناسه پچ</th>
                  <th>تغییر نسخه</th>
                  <th>تعداد فایل</th>
                  <th>توضیحات</th>
                  <th>توسط / تاریخ</th>
                  <th>وضعیت</th>
                  <th>عملیات بازگردانی</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($history as $rec) :
                    $ts = strtotime((string) ($rec['applied_at'] ?? ''));
                    $jDate = $ts ? Jalali::fromTimestamp($ts) . ' ' . Jalali::toPersianDigits(gmdate('H:i', $ts)) : (string) ($rec['applied_at'] ?? '');
                ?>
                  <tr>
                    <td><code><?php echo esc_html((string) ($rec['patch_id'] ?? '')); ?></code></td>
                    <td dir="ltr"><code><?php echo esc_html((string) ($rec['from_version'] ?? '')); ?> → <?php echo esc_html((string) ($rec['to_version'] ?? '')); ?></code></td>
                    <td><?php echo esc_html(Jalali::toPersianDigits((int) ($rec['files_count'] ?? 0))); ?></td>
                    <td><?php echo esc_html((string) ($rec['description'] ?? '—')); ?></td>
                    <td><?php echo esc_html((string) ($rec['applied_by'] ?? '')); ?><br><small style="color:#6d7e90;"><?php echo esc_html($jDate); ?></small></td>
                    <td>
                      <?php if (! empty($rec['rolled_back'])) : ?>
                        <span class="laico-status-badge laico-status-badge--archived">بازگردانی‌شده</span>
                      <?php else : ?>
                        <span class="laico-status-badge laico-status-badge--read">نصب‌شده</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (empty($rec['rolled_back']) && ! empty($rec['backup_file'])) : ?>
                        <form method="post" action="<?php echo esc_url($tabUrl); ?>" style="display:inline;">
                          <?php wp_nonce_field('laico_patch_rollback'); ?>
                          <input type="hidden" name="laico_admin_action" value="patch_rollback">
                          <input type="hidden" name="record_id" value="<?php echo esc_attr((string) $rec['record_id']); ?>">
                          <button type="submit" class="button button-small" onclick="return confirm('آیا از بازگردانی فایل‌های این پچ به نسخهٔ پشتیبان قبلی مطمئن هستید؟');">↺ بازگردانی (Rollback)</button>
                        </form>
                      <?php else : ?>
                        <span style="color:#8b9aab;">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Return boolean option keys displayed on a specific tab so unchecked checkboxes save `false`.
     *
     * @return array<int, string>
     */
    private function tabBooleanKeys(string $tab): array
    {
        return match ($tab) {
            'general' => ['enable_english', 'enable_lang_switcher'],
            'sections' => [
                'sec_home_collections', 'sec_home_projects', 'sec_home_about',
                'sec_home_craft', 'sec_home_cta',
            ],
            'optimization' => [
                'opt_disable_emojis', 'opt_disable_embeds', 'opt_remove_dashicons_guest',
                'opt_disable_xmlrpc', 'opt_remove_wp_version', 'opt_disable_self_pingbacks',
                'opt_dequeue_jquery_migrate', 'opt_preload_fonts', 'opt_preload_hero',
                'opt_defer_scripts', 'opt_zero_fouc_lang', 'opt_lazy_images', 'opt_instant_page_prefetch',
                'opt_page_loader', 'opt_webp_uploads',
            ],
            'seo' => [
                'seo_enable_builtin', 'seo_noindex_search', 'seo_noindex_author',
                'seo_schema_org', 'seo_redirect_legacy_html',
            ],
            'forms' => ['form_enable_email', 'form_store_ip', 'sms_admin_enable', 'sms_customer_enable'],
            'patch' => ['patch_auto_backup', 'patch_require_signature'],
            default => [],
        };
    }
}
