<?php

declare(strict_types=1);

namespace App\Services;

use App\Admin\SubmissionsAdminPage;
use App\Admin\ThemeSettingsPage;
use App\PostTypes\PageAndPostMeta;
use App\PostTypes\ProjectPostType;

/**
 * Standalone WordPress Service Kernel for Laico Sage.
 *
 * Decoupled from `Roots\Acorn\Sage\SageServiceProvider` inheritance so all
 * theme services (CPT, Meta Boxes, SEO, Optimization, Form Submissions,
 * Admin Settings & Patch Updater) boot reliably both under Acorn and in
 * environments where Composer dependencies have not been installed yet.
 */
final class ThemeKernel
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        ThemeOptions::ensureBilingualDefault();

        (new ManagerRoleService())->register();
        (new SecurityIntegrationService())->register();
        (new IppanelSmsService())->register();
        (new ProjectPostType())->register();
        (new PageAndPostMeta())->register();
        (new OptimizationService())->register();
        (new SeoService())->register();
        (new FormSubmissionsService())->register();
        (new BundledSketches())->register();

        if (is_admin()) {
            (new ThemeSettingsPage())->register();
            (new SubmissionsAdminPage())->register();
        }
    }
}
