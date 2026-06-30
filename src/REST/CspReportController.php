<?php

namespace Computernoerden\Security\REST;

use Computernoerden\Security\Helpers\Logger;
use Computernoerden\Security\Modules\Csp\CspViolationStore;
use Computernoerden\Security\Settings\SettingsRepository;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Registers POST /wp-json/cno-security/v1/csp-report.
 *
 * This must be reachable without authentication — browsers send CSP
 * violation reports anonymously, with no nonce or cookie. The
 * permission callback is intentionally `__return_true`; the endpoint
 * only ever writes a capped log entry, never anything privileged, and
 * every field is cast to string before storage.
 */
class CspReportController
{
    const REST_NAMESPACE = 'cno-security/v1';

    /**
     * @var SettingsRepository
     */
    private $settings;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(SettingsRepository $settings, Logger $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    public function boot()
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes()
    {
        register_rest_route(self::REST_NAMESPACE, '/csp-report', [
            'methods' => 'POST',
            'callback' => [$this, 'handleReport'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handleReport(WP_REST_Request $request)
    {
        if (!$this->settings->moduleEnabled('csp') || !$this->settings->get('csp.learning_mode', false)) {
            return new WP_REST_Response(null, 204);
        }

        $data = json_decode($request->get_body(), true);
        $report = $this->extractReport($data);

        if ($report === null) {
            return new WP_REST_Response(null, 204);
        }

        $store = new CspViolationStore($this->settings);

        $store->add([
            'directive' => $this->firstString($report, ['effective-directive', 'effectiveDirective', 'violated-directive']),
            'blockedUri' => $this->firstString($report, ['blocked-uri', 'blockedURL']),
            'documentUri' => $this->firstString($report, ['document-uri', 'documentURL']),
        ]);

        $this->logger->info('CSP violation report received.');

        return new WP_REST_Response(null, 204);
    }

    /**
     * CSP Level 2 "report-uri" payloads are wrapped in
     * {"csp-report": {...}}. Level 3 "report-to" reports arrive as a
     * JSON array of {type, body: {...}} objects. Support both, return
     * null for anything else rather than guessing.
     *
     * @param mixed $data
     * @return array<string, mixed>|null
     */
    private function extractReport($data)
    {
        if (!is_array($data)) {
            return null;
        }

        if (isset($data['csp-report']) && is_array($data['csp-report'])) {
            return $data['csp-report'];
        }

        if (isset($data[0]['body']) && is_array($data[0]['body'])) {
            return $data[0]['body'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $report
     * @param array<int, string> $keys
     */
    private function firstString(array $report, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($report[$key]) && is_scalar($report[$key])) {
                return (string) $report[$key];
            }
        }

        return '';
    }
}
