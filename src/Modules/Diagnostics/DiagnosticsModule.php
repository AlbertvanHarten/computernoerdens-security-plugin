<?php

namespace Computernoerden\Security\Modules\Diagnostics;

use Computernoerden\Security\Modules\AbstractModule;

defined('ABSPATH') || exit;

/**
 * Read-only system diagnostics. Doesn't harden anything itself, so it
 * intentionally opts out of the overall security score.
 */
class DiagnosticsModule extends AbstractModule
{
    public function id(): string
    {
        return 'diagnostics';
    }

    public function name(): string
    {
        return 'Diagnostics';
    }

    public function description(): string
    {
        return 'Read-only environment overview: PHP/WordPress versions, debug constants, REST API status, cron and object cache.';
    }

    public function boot()
    {
        // Read-only — nothing to hook.
    }

    public function status(): string
    {
        return $this->enabled() ? 'Available' : 'Disabled';
    }

    public function countsTowardScore(): bool
    {
        return false;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function items(): array
    {
        global $wp_version;

        return [
            ['label' => 'PHP version', 'value' => PHP_VERSION],
            ['label' => 'WordPress version', 'value' => $wp_version],
            ['label' => 'Server software', 'value' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'Unknown'],
            ['label' => 'Memory limit', 'value' => ini_get('memory_limit') ?: 'Unknown'],
            ['label' => 'Max execution time', 'value' => ini_get('max_execution_time') . 's'],
            ['label' => 'HTTPS detected', 'value' => is_ssl() ? 'Yes' : 'No'],
            ['label' => 'WP_DEBUG', 'value' => (defined('WP_DEBUG') && WP_DEBUG) ? 'Enabled' : 'Disabled'],
            ['label' => 'WP_DEBUG_DISPLAY', 'value' => (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) ? 'Enabled' : 'Disabled'],
            ['label' => 'DISALLOW_FILE_EDIT', 'value' => (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) ? 'Enabled' : 'Disabled'],
            ['label' => 'WP cron', 'value' => (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) ? 'Disabled (external trigger expected)' : 'WordPress default'],
            ['label' => 'Object cache', 'value' => wp_using_ext_object_cache() ? 'External' : 'Default (database)'],
            ['label' => 'OPcache', 'value' => (function_exists('opcache_get_status') && opcache_get_status(false)) ? 'Enabled' : 'Not detected'],
            ['label' => 'REST API', 'value' => 'Enabled'],
            ['label' => 'Active theme', 'value' => wp_get_theme()->get('Name')],
            ['label' => 'Active plugins', 'value' => (string) count((array) get_option('active_plugins', []))],
        ];
    }
}
