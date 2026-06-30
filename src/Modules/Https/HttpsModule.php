<?php

namespace Computernoerden\Security\Modules\Https;

use Computernoerden\Security\Modules\AbstractModule;

defined('ABSPATH') || exit;

class HttpsModule extends AbstractModule
{
    public function id(): string
    {
        return 'https';
    }

    public function name(): string
    {
        return 'HTTPS';
    }

    public function description(): string
    {
        return 'HTTP to HTTPS redirects, HSTS (with optional preload), and reverse proxy / Cloudflare aware SSL detection.';
    }

    public function boot()
    {
        add_action('template_redirect', [$this, 'redirectToHttps'], -1000);
        add_action('send_headers', [$this, 'sendHsts'], 10);

        if ($this->settings->get('https.force_ssl', false)) {
            add_filter('home_url', [$this, 'forceHttpsUrl'], 10, 1);
            add_filter('site_url', [$this, 'forceHttpsUrl'], 10, 1);
        }
    }

    public function status(): string
    {
        if (!$this->enabled()) {
            return 'Disabled';
        }

        if (!$this->isHttps()) {
            return 'Site is not on HTTPS';
        }

        return $this->settings->get('https.hsts_enabled', false) ? 'Enforced + HSTS' : 'Enforced';
    }

    public function score(): int
    {
        if (!$this->enabled()) {
            return 0;
        }

        if (!$this->isHttps()) {
            return 20;
        }

        $score = 70;

        if ($this->settings->get('https.hsts_enabled', false)) {
            $score += 20;
        }

        if ($this->settings->get('https.hsts_preload', false)) {
            $score += 10;
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
        $status = isset($input['redirect_status']) ? (int) $input['redirect_status'] : 301;

        if (!in_array($status, [301, 302, 307, 308], true)) {
            $status = 301;
        }

        $maxAge = isset($input['hsts_max_age']) ? (int) $input['hsts_max_age'] : 15552000;

        if ($maxAge < 0) {
            $maxAge = 0;
        }

        return [
            'redirect_enabled' => !empty($input['redirect_enabled']),
            'redirect_status' => $status,
            'force_ssl' => !empty($input['force_ssl']),
            'hsts_enabled' => !empty($input['hsts_enabled']),
            'hsts_max_age' => $maxAge,
            'hsts_include_subdomains' => !empty($input['hsts_include_subdomains']),
            'hsts_preload' => !empty($input['hsts_preload']),
            'trust_reverse_proxy' => !empty($input['trust_reverse_proxy']),
            'cloudflare_aware' => !empty($input['cloudflare_aware']),
        ];
    }

    public function redirectToHttps()
    {
        if (is_admin() || wp_doing_ajax() || wp_is_json_request() || headers_sent()) {
            return;
        }

        if (!$this->settings->get('https.redirect_enabled', true)) {
            return;
        }

        if ($this->isHttps()) {
            return;
        }

        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';

        if ($host === '') {
            return;
        }

        $status = (int) $this->settings->get('https.redirect_status', 301);

        if (!in_array($status, [301, 302, 307, 308], true)) {
            $status = 301;
        }

        wp_safe_redirect('https://' . $host . $uri, $status);
        exit;
    }

    /**
     * HSTS (and optionally preload) only ever makes sense once we know
     * the current request really is HTTPS — sending it over plain HTTP
     * would be ignored by browsers anyway, but we avoid it regardless.
     */
    public function sendHsts()
    {
        if (is_admin() || headers_sent()) {
            return;
        }

        if (!$this->settings->get('https.hsts_enabled', false)) {
            return;
        }

        if (!$this->isHttps()) {
            return;
        }

        $maxAge = (int) $this->settings->get('https.hsts_max_age', 15552000);
        $directive = 'max-age=' . $maxAge;

        if ($this->settings->get('https.hsts_include_subdomains', true)) {
            $directive .= '; includeSubDomains';
        }

        if ($this->settings->get('https.hsts_preload', false)) {
            $directive .= '; preload';
        }

        header('Strict-Transport-Security: ' . $directive);
    }

    /**
     * @param string $url
     * @return string
     */
    public function forceHttpsUrl($url)
    {
        if (!is_string($url) || $url === '') {
            return $url;
        }

        return set_url_scheme($url, 'https');
    }

    /**
     * Detects HTTPS while being aware of reverse proxies and
     * Cloudflare "Flexible SSL" setups where the connection between
     * Cloudflare and the origin server is plain HTTP even though the
     * visitor sees HTTPS. is_ssl() alone misses these cases.
     */
    private function isHttps(): bool
    {
        if (is_ssl()) {
            return true;
        }

        $trustProxy = $this->settings->get('https.trust_reverse_proxy', false);
        $cloudflareAware = $this->settings->get('https.cloudflare_aware', false);

        if (!$trustProxy && !$cloudflareAware) {
            return false;
        }

        if (
            $trustProxy
            && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
        ) {
            return true;
        }

        if (
            $cloudflareAware
            && !empty($_SERVER['HTTP_CF_VISITOR'])
            && strpos((string) $_SERVER['HTTP_CF_VISITOR'], '"scheme":"https"') !== false
        ) {
            return true;
        }

        return false;
    }
}
