<?php

namespace Computernoerden\Security\Modules\Csp;

use Computernoerden\Security\Modules\AbstractModule;

defined('ABSPATH') || exit;

/**
 * Content Security Policy module.
 *
 * This is the flagship module of the plugin. It supports:
 * - a per-directive policy builder (no single raw textarea)
 * - per-request nonces for script-src / style-src
 * - manually curated hash allowlisting
 * - enforce / report-only modes
 * - a "learning mode" that forces report-only and collects violation
 *   reports via a REST endpoint, summarised in the admin UI so sources
 *   can be added to the policy with one click
 *
 * Known limitations (tracked in docs/ROADMAP.md): there is no
 * automatic detection of Elementor/WooCommerce/Cookiebot/Google
 * Fonts/Maps/YouTube/Vimeo requirements yet, and nonce injection only
 * covers WordPress-enqueued scripts/styles and wp_add_inline_script —
 * not every possible inline <script>/<style> a theme might print.
 */
class CspModule extends AbstractModule
{
    /**
     * The directives exposed in the admin builder UI. Custom/arbitrary
     * directives are roadmap (see docs/ROADMAP.md) — this list covers
     * everything a typical WordPress + common plugin stack needs.
     */
    const KNOWN_DIRECTIVES = [
        'default-src',
        'base-uri',
        'object-src',
        'frame-ancestors',
        'form-action',
        'img-src',
        'font-src',
        'style-src',
        'script-src',
        'connect-src',
        'frame-src',
        'media-src',
        'worker-src',
        'manifest-src',
    ];

    const MAX_HASH_ROWS = 5;

    /**
     * @var string|null
     */
    private static $nonce = null;

    public function id(): string
    {
        return 'csp';
    }

    public function name(): string
    {
        return 'Content Security Policy';
    }

    public function description(): string
    {
        return 'Per-directive CSP builder with nonce support, hash allowlisting, report-only/enforce modes and a learning mode that suggests policy updates from real traffic.';
    }

    public function boot()
    {
        add_action('send_headers', [$this, 'sendCsp'], 30);

        if ($this->settings->get('csp.nonce_script', false)) {
            add_filter('wp_inline_script_attributes', [$this, 'addNonceToInlineScriptAttributes']);
            add_filter('script_loader_tag', [$this, 'addNonceToScriptTag'], 10, 1);
        }

        if ($this->settings->get('csp.nonce_style', false)) {
            add_filter('style_loader_tag', [$this, 'addNonceToStyleTag'], 10, 1);
        }
    }

    public function status(): string
    {
        if (!$this->enabled() || !$this->settings->get('csp.enabled', true)) {
            return 'Disabled';
        }

        if ($this->settings->get('csp.learning_mode', false)) {
            return 'Learning (Report-Only)';
        }

        return $this->settings->get('csp.mode', 'enforce') === 'report-only' ? 'Report-Only' : 'Enforced';
    }

    public function score(): int
    {
        if (!$this->enabled() || !$this->settings->get('csp.enabled', true)) {
            return 0;
        }

        $directives = $this->settings->get('csp.directives', []);

        if (empty($directives)) {
            return 10;
        }

        $isEnforcing = !$this->settings->get('csp.learning_mode', false)
            && $this->settings->get('csp.mode', 'enforce') === 'enforce';

        $score = $isEnforcing ? 60 : 35;

        $hasUnsafeInlineScript = is_array($directives)
            && isset($directives['script-src'])
            && strpos((string) $directives['script-src'], "'unsafe-inline'") !== false;

        if (!$hasUnsafeInlineScript || $this->settings->get('csp.nonce_script', false)) {
            $score += 20;
        }

        if (isset($directives['object-src']) && trim((string) $directives['object-src']) === "'none'") {
            $score += 10;
        }

        if (isset($directives['base-uri']) && $directives['base-uri'] !== '') {
            $score += 5;
        }

        if ($this->settings->get('csp.report_uri_enabled', false)) {
            $score += 5;
        }

        return min(100, $score);
    }

    public function hasSettings(): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitizeSettings(array $input): array
    {
        $mode = isset($input['mode']) && $input['mode'] === 'enforce' ? 'enforce' : 'report-only';

        $directives = [];

        foreach (self::KNOWN_DIRECTIVES as $directive) {
            $field = 'directive_' . $directive;

            if (!isset($input[$field])) {
                continue;
            }

            $value = $this->sanitizeDirectiveValue((string) $input[$field]);

            if ($value !== '') {
                $directives[$directive] = $value;
            }
        }

        $hashes = [];

        for ($i = 0; $i < self::MAX_HASH_ROWS; $i++) {
            $hashValue = isset($input['hash_' . $i . '_hash']) ? (string) $input['hash_' . $i . '_hash'] : '';

            if (trim($hashValue) === '') {
                continue;
            }

            $directive = $this->sanitizeDirectiveName(
                isset($input['hash_' . $i . '_directive']) ? (string) $input['hash_' . $i . '_directive'] : ''
            );

            $algorithmInput = isset($input['hash_' . $i . '_algorithm']) ? (string) $input['hash_' . $i . '_algorithm'] : '';
            $algorithm = in_array($algorithmInput, ['sha256', 'sha384', 'sha512'], true) ? $algorithmInput : 'sha256';

            if ($directive === '') {
                continue;
            }

            $hashes[] = [
                'directive' => $directive,
                'algorithm' => $algorithm,
                'hash' => preg_replace('/[^A-Za-z0-9+\/=]/', '', $hashValue),
            ];
        }

        return [
            'enabled' => !empty($input['enabled']),
            'mode' => $mode,
            'learning_mode' => !empty($input['learning_mode']),
            'nonce_script' => !empty($input['nonce_script']),
            'nonce_style' => !empty($input['nonce_style']),
            'report_uri_enabled' => !empty($input['report_uri_enabled']),
            'directives' => $directives,
            'hashes' => $hashes,
        ];
    }

    /**
     * Adds (or extends) one directive with one extra source — used by
     * the "add to policy" action on the learning-mode violation list.
     */
    public function addSourceToDirective(string $directive, string $source)
    {
        $directive = $this->sanitizeDirectiveName($directive);
        $source = trim(preg_replace('/[\r\n]+/', ' ', $source) ?? '');

        if ($directive === '' || $source === '') {
            return;
        }

        $directives = $this->settings->get('csp.directives', []);
        $current = isset($directives[$directive]) ? (string) $directives[$directive] : '';

        $existing = array_filter(explode(' ', $current));

        if (in_array($source, $existing, true)) {
            return;
        }

        $existing[] = $source;
        $directives[$directive] = implode(' ', $existing);

        $this->settings->set('csp.directives', $directives);
    }

    public function sendCsp()
    {
        if (is_admin() || headers_sent()) {
            return;
        }

        if (!$this->enabled() || !$this->settings->get('csp.enabled', true)) {
            return;
        }

        $directives = $this->settings->get('csp.directives', []);

        if (empty($directives) || !is_array($directives)) {
            return;
        }

        $parts = [];

        foreach ($directives as $directive => $value) {
            $value = trim((string) $value);

            if ($directive === 'script-src' && $this->settings->get('csp.nonce_script', false)) {
                $value .= " 'nonce-" . $this->nonce() . "'";
            }

            if ($directive === 'style-src' && $this->settings->get('csp.nonce_style', false)) {
                $value .= " 'nonce-" . $this->nonce() . "'";
            }

            $value = trim($value . ' ' . $this->hashesFor((string) $directive));

            if ($value === '') {
                continue;
            }

            $parts[] = $directive . ' ' . $value;
        }

        if ($this->settings->get('csp.report_uri_enabled', false)) {
            $reportUrl = rest_url('cno-security/v1/csp-report');
            $parts[] = 'report-uri ' . esc_url_raw($reportUrl);
            $parts[] = 'report-to csp-endpoint';

            header('Reporting-Endpoints: csp-endpoint="' . esc_url_raw($reportUrl) . '"');
            header('Report-To: ' . wp_json_encode([
                'group' => 'csp-endpoint',
                'max_age' => 10886400,
                'endpoints' => [['url' => $reportUrl]],
            ]));
        }

        if (empty($parts)) {
            return;
        }

        $policy = implode('; ', $parts);

        $useReportOnly = $this->settings->get('csp.learning_mode', false)
            || $this->settings->get('csp.mode', 'enforce') === 'report-only';

        $header = $useReportOnly ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';

        header($header . ': ' . $policy);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function addNonceToInlineScriptAttributes(array $attributes): array
    {
        $attributes['nonce'] = $this->nonce();

        return $attributes;
    }

    public function addNonceToScriptTag(string $tag): string
    {
        if (strpos($tag, 'nonce=') !== false) {
            return $tag;
        }

        return preg_replace('/<script\s/', '<script nonce="' . esc_attr($this->nonce()) . '" ', $tag, 1) ?? $tag;
    }

    public function addNonceToStyleTag(string $tag): string
    {
        if (strpos($tag, 'nonce=') !== false) {
            return $tag;
        }

        return preg_replace('/<link\s/', '<link nonce="' . esc_attr($this->nonce()) . '" ', $tag, 1) ?? $tag;
    }

    /**
     * One nonce per request, shared between the header and every tag
     * we inject it into.
     */
    public function nonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = base64_encode(random_bytes(16));
        }

        return self::$nonce;
    }

    private function hashesFor(string $directive): string
    {
        $hashes = $this->settings->get('csp.hashes', []);

        if (empty($hashes) || !is_array($hashes)) {
            return '';
        }

        $tokens = [];

        foreach ($hashes as $hash) {
            if (!is_array($hash) || ($hash['directive'] ?? '') !== $directive) {
                continue;
            }

            $tokens[] = "'" . $hash['algorithm'] . '-' . $hash['hash'] . "'";
        }

        return implode(' ', $tokens);
    }

    private function sanitizeDirectiveName(string $directive): string
    {
        $directive = strtolower(trim($directive));

        if (!preg_match('/^[a-z-]+$/', $directive)) {
            return '';
        }

        return $directive;
    }

    private function sanitizeDirectiveValue(string $value): string
    {
        $value = wp_unslash($value);
        $value = str_replace(["\r", "\n"], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }
}
