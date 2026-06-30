<?php

namespace Computernoerden\Security\Modules\Scanner;

use Computernoerden\Security\Modules\AbstractModule;

defined('ABSPATH') || exit;

/**
 * Runs entirely local checks (settings, constants, filesystem) — never
 * makes an outbound HTTP request, per the project spec. This keeps it
 * fast and safe to run synchronously from an admin AJAX action.
 *
 * internet.nl / sikkerpånettet.dk-equivalent *online* scanning is
 * deliberately out of scope here; see docs/ROADMAP.md.
 */
class ScannerModule extends AbstractModule
{
    public function id(): string
    {
        return 'scanner';
    }

    public function name(): string
    {
        return 'Scanner';
    }

    public function description(): string
    {
        return 'Local readiness scan covering HTTPS, headers, CSP, security.txt, file editing, debug output and more — no outbound requests.';
    }

    public function boot()
    {
        // No runtime hooks: the scan only runs on demand, triggered
        // from the admin UI via AjaxController.
    }

    public function status(): string
    {
        if (!$this->enabled()) {
            return 'Disabled';
        }

        $lastRun = $this->settings->get('scanner.last_run');

        return $lastRun ? 'Last scan ' . human_time_diff((int) $lastRun) . ' ago' : 'Not yet run';
    }

    public function score(): int
    {
        if (!$this->enabled()) {
            return 0;
        }

        $results = $this->settings->get('scanner.last_results', []);

        return $this->scoreFromResults(is_array($results) ? $results : []);
    }

    /**
     * Runs every check, persists the results + timestamp, and returns
     * them — used by the "Run scan" AJAX action and by the Reports
     * module.
     *
     * @return array<int, array{id: string, label: string, status: string, message: string}>
     */
    public function runScan(): array
    {
        $results = [
            $this->checkHttpsActive(),
            $this->checkHsts(),
            $this->checkCsp(),
            $this->checkCspUnsafeInline(),
            $this->checkSecurityHeaders(),
            $this->checkSecurityTxt(),
            $this->checkFileEditingDisabled(),
            $this->checkDebugDisplay(),
            $this->checkForceSslAdmin(),
            $this->checkAdminUsername(),
            $this->checkUploadsPhpExecution(),
            $this->checkCoreUpdateAvailable(),
        ];

        $this->settings->set('scanner.last_results', $results);
        $this->settings->set('scanner.last_run', time());

        return $results;
    }

    /**
     * @param array<int, array{status: string}> $results
     */
    private function scoreFromResults(array $results): int
    {
        $scored = array_filter($results, function ($result) {
            return (isset($result['status']) ? $result['status'] : '') !== 'info';
        });

        if (empty($scored)) {
            return 0;
        }

        $points = [
            'pass' => 100,
            'warn' => 50,
            'fail' => 0,
        ];

        $total = 0;

        foreach ($scored as $result) {
            $total += isset($points[$result['status']]) ? $points[$result['status']] : 0;
        }

        return (int) round($total / count($scored));
    }

    /**
     * @return array{id: string, label: string, status: string, message: string}
     */
    private function checkHttpsActive(): array
    {
        return $this->result(
            'https_active',
            'Site is served over HTTPS',
            is_ssl() ? 'pass' : 'fail',
            is_ssl() ? 'The current request is HTTPS.' : 'The site is not currently being served over HTTPS.'
        );
    }

    private function checkHsts(): array
    {
        $enabled = $this->settings->get('https.hsts_enabled', false);

        return $this->result(
            'hsts_enabled',
            'HSTS is enabled',
            $enabled ? 'pass' : 'warn',
            $enabled ? 'Strict-Transport-Security is being sent.' : 'Enable HSTS in the HTTPS module once you are confident the site only loads over HTTPS.'
        );
    }

    private function checkCsp(): array
    {
        $enabled = $this->settings->get('csp.enabled', false) && $this->settings->moduleEnabled('csp');

        return $this->result(
            'csp_enabled',
            'Content Security Policy is active',
            $enabled ? 'pass' : 'fail',
            $enabled ? 'A CSP header is being sent.' : 'No CSP header is being sent. Enable the CSP module.'
        );
    }

    private function checkCspUnsafeInline(): array
    {
        $directives = $this->settings->get('csp.directives', []);
        $scriptSrc = is_array($directives) && isset($directives['script-src']) ? (string) $directives['script-src'] : '';
        $hasUnsafeInline = strpos($scriptSrc, "'unsafe-inline'") !== false;
        $nonceEnabled = $this->settings->get('csp.nonce_script', false);

        $status = !$hasUnsafeInline ? 'pass' : ($nonceEnabled ? 'warn' : 'fail');

        return $this->result(
            'csp_unsafe_inline',
            "script-src avoids 'unsafe-inline'",
            $status,
            $status === 'pass'
                ? "script-src does not allow 'unsafe-inline'."
                : "script-src allows 'unsafe-inline', which weakens CSP protection against XSS. Switch to nonce-based scripts where possible."
        );
    }

    private function checkSecurityHeaders(): array
    {
        $headersEnabled = $this->settings->moduleEnabled('headers');

        return $this->result(
            'security_headers',
            'Security headers module is active',
            $headersEnabled ? 'pass' : 'fail',
            $headersEnabled ? 'X-Content-Type-Options, Referrer-Policy and friends are being sent.' : 'Enable the Security Headers module.'
        );
    }

    private function checkSecurityTxt(): array
    {
        $published = $this->settings->moduleEnabled('security_txt') && $this->settings->get('security_txt.enabled', false);

        return $this->result(
            'security_txt_published',
            'security.txt is published',
            $published ? 'pass' : 'warn',
            $published ? '/.well-known/security.txt is being served.' : 'Consider publishing a security.txt so researchers know how to reach you.'
        );
    }

    private function checkFileEditingDisabled(): array
    {
        $disabled = defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT;

        return $this->result(
            'file_edit_disabled',
            'Theme/plugin file editor is disabled',
            $disabled ? 'pass' : 'warn',
            $disabled
                ? 'DISALLOW_FILE_EDIT is set.'
                : "Add define('DISALLOW_FILE_EDIT', true); to wp-config.php to remove the built-in theme/plugin file editor."
        );
    }

    private function checkDebugDisplay(): array
    {
        $displaying = defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : (defined('WP_DEBUG') && WP_DEBUG);

        return $this->result(
            'debug_display_off',
            'Debug output is hidden from visitors',
            $displaying ? 'fail' : 'pass',
            $displaying
                ? 'WP_DEBUG_DISPLAY is on (or WP_DEBUG is on without disabling display), which can leak paths and errors to visitors.'
                : 'Errors are not being displayed to visitors.'
        );
    }

    private function checkForceSslAdmin(): array
    {
        $forced = defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN;

        return $this->result(
            'force_ssl_admin',
            'wp-admin forces SSL',
            $forced ? 'pass' : 'info',
            $forced
                ? 'FORCE_SSL_ADMIN is set.'
                : "Optional: define('FORCE_SSL_ADMIN', true); in wp-config.php as defense-in-depth alongside the HTTPS module's redirect."
        );
    }

    private function checkAdminUsername(): array
    {
        $exists = get_user_by('login', 'admin') !== false;

        return $this->result(
            'admin_username',
            'No account uses the username "admin"',
            $exists ? 'warn' : 'pass',
            $exists
                ? 'An account with the username "admin" exists, which is the first guess in credential-stuffing attacks.'
                : 'No "admin" username found.'
        );
    }

    private function checkUploadsPhpExecution(): array
    {
        $uploadDir = wp_get_upload_dir();
        $htaccess = trailingslashit($uploadDir['basedir']) . '.htaccess';
        $protected = file_exists($htaccess) && strpos((string) file_get_contents($htaccess), 'php') !== false;

        return $this->result(
            'uploads_php_execution',
            'PHP execution is blocked in /wp-content/uploads',
            $protected ? 'pass' : 'warn',
            $protected
                ? 'An .htaccess rule blocking PHP execution was found in the uploads directory.'
                : 'No PHP-blocking .htaccess found in /wp-content/uploads. On Apache, add one denying execution of .php files there.'
        );
    }

    private function checkCoreUpdateAvailable(): array
    {
        $updates = get_site_transient('update_core');
        $hasUpdate = isset($updates->updates[0]->response) && $updates->updates[0]->response === 'upgrade';

        return $this->result(
            'core_update_available',
            'WordPress core is up to date',
            $hasUpdate ? 'warn' : 'info',
            $hasUpdate ? 'A WordPress core update is available.' : "No pending core update detected (based on WordPress's own update check)."
        );
    }

    /**
     * @return array{id: string, label: string, status: string, message: string}
     */
    private function result(string $id, string $label, string $status, string $message): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'status' => $status,
            'message' => $message,
        ];
    }
}
