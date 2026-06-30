<?php

namespace Computernoerden\Security\Modules\SecurityTxt;

use Computernoerden\Security\Modules\AbstractModule;

defined('ABSPATH') || exit;

/**
 * Publishes /.well-known/security.txt per RFC 9116.
 */
class SecurityTxtModule extends AbstractModule
{
    /**
     * @var array<string, string>
     */
    const FIELD_MAP = [
        'encryption' => 'Encryption',
        'acknowledgments' => 'Acknowledgments',
        'canonical' => 'Canonical',
        'policy' => 'Policy',
        'hiring' => 'Hiring',
    ];

    public function id(): string
    {
        return 'security_txt';
    }

    public function name(): string
    {
        return 'security.txt';
    }

    public function description(): string
    {
        return 'Publishes a validated /.well-known/security.txt (RFC 9116) with a GUI editor for contact, expiry, and related fields.';
    }

    public function enabledByDefault(): bool
    {
        return false;
    }

    public function boot()
    {
        add_action('init', [$this, 'addRewriteRule']);
        add_filter('query_vars', [$this, 'addQueryVar']);
        add_action('template_redirect', [$this, 'render']);
    }

    public function status(): string
    {
        if (!$this->enabled() || !$this->settings->get('security_txt.enabled', false)) {
            return 'Disabled';
        }

        return $this->isExpired() ? 'Published (expired)' : 'Published';
    }

    public function score(): int
    {
        if (!$this->enabled() || !$this->settings->get('security_txt.enabled', false)) {
            return 40;
        }

        return $this->isExpired() ? 60 : 100;
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
        $sanitized = [
            'enabled' => !empty($input['enabled']),
            'contact' => $this->sanitizeMultiline($input['contact'] ?? ''),
            'expires' => $this->sanitizeExpires($input['expires'] ?? ''),
            'preferred_languages' => sanitize_text_field((string) ($input['preferred_languages'] ?? '')),
        ];

        foreach (array_keys(self::FIELD_MAP) as $field) {
            $value = (string) ($input[$field] ?? '');
            $sanitized[$field] = $field === 'acknowledgments' || $field === 'canonical' || $field === 'policy' || $field === 'hiring'
                ? esc_url_raw($value)
                : sanitize_text_field($value);
        }

        return $sanitized;
    }

    public function addRewriteRule()
    {
        add_rewrite_rule('^\.well-known/security\.txt$', 'index.php?cno_security_txt=1', 'top');
    }

    /**
     * @param array<int, string> $vars
     * @return array<int, string>
     */
    public function addQueryVar(array $vars): array
    {
        $vars[] = 'cno_security_txt';

        return $vars;
    }

    public function render()
    {
        if ((int) get_query_var('cno_security_txt') !== 1) {
            return;
        }

        if (!$this->enabled() || !$this->settings->get('security_txt.enabled', false)) {
            status_header(404);
            exit;
        }

        header('Content-Type: text/plain; charset=utf-8');

        $lines = [];

        foreach (explode("\n", (string) $this->settings->get('security_txt.contact', '')) as $contact) {
            $contact = trim($contact);

            if ($contact !== '') {
                $lines[] = 'Contact: ' . $contact;
            }
        }

        $expires = (string) $this->settings->get('security_txt.expires', '');

        if ($expires !== '') {
            $lines[] = 'Expires: ' . $expires;
        }

        foreach (self::FIELD_MAP as $field => $label) {
            $value = (string) $this->settings->get('security_txt.' . $field, '');

            if ($value !== '') {
                $lines[] = $label . ': ' . $value;
            }
        }

        $languages = (string) $this->settings->get('security_txt.preferred_languages', '');

        if ($languages !== '') {
            $lines[] = 'Preferred-Languages: ' . $languages;
        }

        echo implode("\n", $lines) . "\n";
        exit;
    }

    private function isExpired(): bool
    {
        $expires = (string) $this->settings->get('security_txt.expires', '');

        if ($expires === '') {
            return false;
        }

        $timestamp = strtotime($expires);

        return $timestamp !== false && $timestamp < time();
    }

    /**
     * @param mixed $value
     */
    private function sanitizeMultiline($value): string
    {
        $lines = explode("\n", (string) $value);
        $clean = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // Accept "mailto:" or "https://" style URIs as-is, plain
            // email addresses get the mailto: scheme added.
            if (strpos($line, ':') === false && is_email($line)) {
                $line = 'mailto:' . $line;
            }

            $clean[] = esc_url_raw($line);
        }

        return implode("\n", $clean);
    }

    /**
     * @param mixed $value
     */
    private function sanitizeExpires($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 year'));
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 year'));
        }

        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }
}
