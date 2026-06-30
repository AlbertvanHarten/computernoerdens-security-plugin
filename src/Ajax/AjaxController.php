<?php

namespace Computernoerden\Security\Ajax;

use Computernoerden\Security\Modules\Csp\CspModule;
use Computernoerden\Security\Modules\Csp\CspViolationStore;
use Computernoerden\Security\Modules\ModuleManager;
use Computernoerden\Security\Modules\Reports\ReportsModule;
use Computernoerden\Security\Modules\Scanner\ScannerModule;
use Computernoerden\Security\Settings\SettingsRepository;

defined('ABSPATH') || exit;

/**
 * Everything in the admin UI is AJAX-driven — there is no Save button
 * anywhere (per the project spec). This class is the single place
 * that wires up wp_ajax_* actions so Admin.php stays focused on
 * rendering.
 */
class AjaxController
{
    /**
     * @var ModuleManager
     */
    private $modules;

    /**
     * @var SettingsRepository
     */
    private $settings;

    public function __construct(ModuleManager $modules, SettingsRepository $settings)
    {
        $this->modules = $modules;
        $this->settings = $settings;
    }

    public function boot()
    {
        add_action('wp_ajax_cno_security_toggle_module', [$this, 'toggleModule']);
        add_action('wp_ajax_cno_security_save_settings', [$this, 'saveSettings']);
        add_action('wp_ajax_cno_security_run_scan', [$this, 'runScan']);
        add_action('wp_ajax_cno_security_csp_apply_violation', [$this, 'applyCspViolation']);
        add_action('wp_ajax_cno_security_csp_clear_violations', [$this, 'clearCspViolations']);
        add_action('wp_ajax_cno_security_export_report', [$this, 'exportReport']);
    }

    public function toggleModule()
    {
        $this->authorize();

        $moduleId = isset($_POST['module']) ? sanitize_key(wp_unslash($_POST['module'])) : '';
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1';

        if (!$this->modules->has($moduleId)) {
            wp_send_json_error(['message' => 'Unknown module.'], 404);
        }

        $this->settings->setModuleEnabled($moduleId, $enabled);

        wp_send_json_success([
            'message' => $enabled ? 'Module enabled.' : 'Module disabled.',
            'module' => $moduleId,
            'enabled' => $enabled,
            'status' => $this->modules->get($moduleId)->status(),
            'score' => $this->modules->get($moduleId)->score(),
            'overallScore' => $this->modules->overallScore(),
        ]);
    }

    public function saveSettings()
    {
        $this->authorize();

        $moduleId = isset($_POST['module']) ? sanitize_key(wp_unslash($_POST['module'])) : '';
        $module = $this->modules->get($moduleId);

        if ($module === null || !$module->hasSettings()) {
            wp_send_json_error(['message' => 'Unknown or non-configurable module.'], 404);
        }

        $fields = isset($_POST['fields']) && is_array($_POST['fields']) ? wp_unslash($_POST['fields']) : [];
        $sanitized = $module->sanitizeSettings($fields);

        $this->settings->set($module->settingsKey(), $sanitized);

        wp_send_json_success([
            'message' => 'Settings saved.',
            'module' => $moduleId,
            'status' => $module->status(),
            'score' => $module->score(),
            'overallScore' => $this->modules->overallScore(),
        ]);
    }

    public function runScan()
    {
        $this->authorize();

        $scanner = $this->modules->get('scanner');

        if (!($scanner instanceof ScannerModule) || !$scanner->enabled()) {
            wp_send_json_error(['message' => 'Scanner module is disabled.'], 400);
        }

        $results = $scanner->runScan();

        wp_send_json_success([
            'message' => 'Scan complete.',
            'results' => $results,
            'score' => $scanner->score(),
            'overallScore' => $this->modules->overallScore(),
        ]);
    }

    public function applyCspViolation()
    {
        $this->authorize();

        $csp = $this->modules->get('csp');

        if (!($csp instanceof CspModule)) {
            wp_send_json_error(['message' => 'CSP module unavailable.'], 404);
        }

        $directive = isset($_POST['directive']) ? sanitize_text_field(wp_unslash($_POST['directive'])) : '';
        $source = isset($_POST['source']) ? sanitize_text_field(wp_unslash($_POST['source'])) : '';

        $csp->addSourceToDirective($directive, $source);

        wp_send_json_success([
            'message' => sprintf('Added %s to %s.', $source, $directive),
        ]);
    }

    public function clearCspViolations()
    {
        $this->authorize();

        $store = new CspViolationStore($this->settings);
        $store->clear();

        wp_send_json_success(['message' => 'Violation log cleared.']);
    }

    public function exportReport()
    {
        $this->authorize();

        $reports = $this->modules->get('reports');

        if (!($reports instanceof ReportsModule)) {
            wp_send_json_error(['message' => 'Reports module unavailable.'], 404);
        }

        $format = isset($_POST['format']) && $_POST['format'] === 'json' ? 'json' : 'csv';

        wp_send_json_success([
            'format' => $format,
            'filename' => 'computernoerdens-security-report-' . gmdate('Y-m-d') . '.' . $format,
            'content' => $format === 'json' ? $reports->toJson() : $reports->toCsv(),
        ]);
    }

    private function authorize()
    {
        check_ajax_referer('cno_security_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }
    }
}
