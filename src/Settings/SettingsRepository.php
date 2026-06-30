<?php

namespace Computernoerden\Security\Settings;

defined('ABSPATH') || exit;

/**
 * Persists all plugin settings under a single WordPress option.
 *
 * Deliberately avoids custom database tables (per project spec) — even
 * the CSP violation log and scanner results are capped, serialised
 * arrays stored via the Options API.
 */
class SettingsRepository
{
    const OPTION = 'cno_security_settings';

    /**
     * Bump whenever defaults() changes shape in a way that needs a
     * one-time migration in upgrade().
     */
    const SCHEMA_VERSION = 2;

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'modules' => [
                'https' => true,
                'headers' => true,
                'csp' => true,
                'security_txt' => false,
                'scanner' => true,
                'diagnostics' => true,
                'reports' => true,
            ],
            'https' => [
                'redirect_enabled' => true,
                'redirect_status' => 301,
                'force_ssl' => false,
                'hsts_enabled' => false,
                'hsts_max_age' => 15552000, // 180 days
                'hsts_include_subdomains' => true,
                'hsts_preload' => false,
                'trust_reverse_proxy' => false,
                'cloudflare_aware' => false,
            ],
            'headers' => [
                'x_content_type_options' => true,
                'referrer_policy' => 'strict-origin-when-cross-origin',
                'x_frame_options' => 'SAMEORIGIN',
                'permissions_policy' => 'geolocation=(), microphone=(), camera=()',
                'coop' => 'same-origin',
                'corp' => '',
                'coep' => '',
            ],
            'csp' => [
                'enabled' => true,
                'mode' => 'report-only',
                'learning_mode' => true,
                'nonce_script' => false,
                'nonce_style' => false,
                'report_uri_enabled' => true,
                'directives' => [
                    'default-src' => "'self'",
                    'base-uri' => "'self'",
                    'object-src' => "'none'",
                    'frame-ancestors' => "'self'",
                    'form-action' => "'self'",
                    'img-src' => "'self' data: https:",
                    'font-src' => "'self' data: https:",
                    'style-src' => "'self' 'unsafe-inline' https:",
                    'script-src' => "'self' https:",
                    'connect-src' => "'self' https:",
                    'frame-src' => "'self' https:",
                    'media-src' => "'self' https:",
                    'worker-src' => "'self'",
                    'manifest-src' => "'self'",
                ],
                'hashes' => [],
                'violation_log' => [],
            ],
            'security_txt' => [
                'enabled' => false,
                'contact' => get_option('admin_email'),
                'expires' => gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 year')),
                'encryption' => '',
                'acknowledgments' => '',
                'preferred_languages' => 'da, en',
                'canonical' => '',
                'policy' => '',
                'hiring' => '',
            ],
            'scanner' => [
                'last_run' => null,
                'last_results' => [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $stored = get_option(self::OPTION, []);

        if (!is_array($stored)) {
            $stored = [];
        }

        return $this->merge($this->defaults(), $stored);
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $path, $default = null)
    {
        $settings = $this->all();
        $parts = explode('.', $path);
        $value = $settings;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }

            $value = $value[$part];
        }

        return $value;
    }

    /**
     * @param mixed $value
     */
    public function set(string $path, $value)
    {
        $settings = $this->all();
        $parts = explode('.', $path);
        $cursor = &$settings;

        foreach ($parts as $part) {
            if (!isset($cursor[$part]) || !is_array($cursor[$part])) {
                $cursor[$part] = [];
            }

            $cursor = &$cursor[$part];
        }

        $cursor = $value;

        update_option(self::OPTION, $settings, false);
    }

    /**
     * Merge a partial settings array into the currently stored ones,
     * one level deep per top-level key (used by module settings saves
     * so unrelated keys are never clobbered).
     *
     * @param array<string, mixed> $values
     */
    public function mergeInto(string $path, array $values)
    {
        $current = $this->get($path, []);

        if (!is_array($current)) {
            $current = [];
        }

        $this->set($path, array_merge($current, $values));
    }

    public function moduleEnabled(string $moduleId): bool
    {
        return (bool) $this->get('modules.' . $moduleId, false);
    }

    public function setModuleEnabled(string $moduleId, bool $enabled)
    {
        $this->set('modules.' . $moduleId, $enabled);
    }

    public function installDefaults()
    {
        if (get_option(self::OPTION) === false) {
            add_option(self::OPTION, $this->defaults(), '', false);
        }

        $this->upgrade();
    }

    /**
     * One-time migrations between schema versions. Safe to call on
     * every boot — it no-ops once the stored schema_version matches
     * the current one.
     */
    public function upgrade()
    {
        $stored = get_option(self::OPTION, []);

        if (!is_array($stored)) {
            return;
        }

        $version = isset($stored['schema_version']) ? (int) $stored['schema_version'] : 1;

        if ($version >= self::SCHEMA_VERSION) {
            return;
        }

        // v1 -> v2: the CSP module moved from a single raw "policy"
        // string to a per-directive "directives" array. Best-effort
        // carry the old default-src-style policy into "default-src" so
        // sites that already customised it don't silently lose it.
        if ($version < 2 && isset($stored['csp']['policy']) && is_string($stored['csp']['policy'])) {
            $stored['csp']['directives']['default-src'] = trim((string) strtok($stored['csp']['policy'], ';'));
            unset($stored['csp']['policy']);
        }

        $stored['schema_version'] = self::SCHEMA_VERSION;

        update_option(self::OPTION, $stored, false);
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $stored
     * @return array<string, mixed>
     */
    private function merge(array $defaults, array $stored): array
    {
        foreach ($stored as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])) {
                $defaults[$key] = $this->merge($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }
}
