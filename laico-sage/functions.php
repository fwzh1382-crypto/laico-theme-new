<?php

/**
 * Laico Design — Sage Theme Bootstrapper (functions.php).
 */

declare(strict_types=1);

use App\Providers\ThemeServiceProvider;

if (! defined('ABSPATH')) {
    exit;
}

define('LAICO_THEME_VERSION', '1.5.5');
define('LAICO_THEME_DIR', get_template_directory());
define('LAICO_THEME_URI', get_template_directory_uri());

/*
|--------------------------------------------------------------------------
| Register Composer or Fallback PSR-4 Autoloader
|--------------------------------------------------------------------------
|
| Loads vendor/autoload.php when present, and always registers a lightweight
| PSR-4 autoloader for App\ so the theme works both with Composer inside the
| theme directory and with the standalone Roots Acorn WordPress plugin.
|
*/

if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    require_once $composer;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/app/helpers.php';

/*
|--------------------------------------------------------------------------
| Boot Roots Acorn Application Container (Sage v10 / v11 compatible)
|--------------------------------------------------------------------------
*/

if (class_exists(\Roots\Acorn\Application::class)) {
    if (method_exists(\Roots\Acorn\Application::class, 'configure')) {
        \Roots\Acorn\Application::configure()
            ->withProviders([
                ThemeServiceProvider::class,
            ])
            ->boot();
    } elseif (function_exists('\Roots\bootloader')) {
        \Roots\bootloader()->boot();
    }
} else {
    // Boot core WordPress services (Settings, CPT, Forms, SEO, Optimization, Patch Updater)
    // even before `composer install` is executed so WP-Admin never breaks.
    add_action('after_setup_theme', static function (): void {
        \App\Services\ThemeKernel::boot();
    }, 5);

    add_action('admin_notices', static function (): void {
        if (! current_user_can('manage_options')) {
            return;
        }
        echo '<div class="notice notice-warning"><p><strong>لایکو دیزاین (Sage):</strong> برای رندر قالب‌های Blade در فرانت‌اند، دستور <code>composer install</code> را در پوشهٔ <code>laico-sage</code> اجرا کنید یا افزونهٔ <strong>Acorn</strong> را فعال نمایید.</p></div>';
    });
}

/*
|--------------------------------------------------------------------------
| Load Sage Theme Setup & Filters
|--------------------------------------------------------------------------
*/

collect_or_each_laico(['setup', 'filters']);
