<?php

namespace Computernoerden\Security\Modules\Headers;

use Computernoerden\Security\Modules\AbstractModule;

defined('ABSPATH') || exit;

class HeadersModule extends AbstractModule
{
    /**
     * Maps a settings key to its HTTP header name.
     *
     * @var array<string, string>
     */
    const HEADER_MAP = [
        'x_frame_options' => 'X-Frame-Options',
        'permissions_policy' => 'Permissions-Policy',
        'coop' => 'Cross-Origin-Opener-Policy',
        'corp' => 'Cross-Origin-Resource-Policy',
        'coep' => 'Cross-Origin-Embedder-Policy',
        'referrer_policy' => 'Referrer-Policy',
    ];

    public function id(): string
    {
        return 'headers';
    }

    public function name(): string
    {
        return 'Security Headers';
    }

    public function description(): string
    {
        return 'X-Content-Type-Options, Referrer-Policy, Permissions-Policy, X-Frame-Options and the Cross-Origin-* headers.';
    }

    public function boot()
    {
        add_action('send_headers', [$this, 'sendHeaders'], 20);
    }

    public function score(): int
    {
        if (!$this->enabled()) {
            return 0;
        }

        $fields = array_merge(['x_content_type_options'], array_keys(self::HEADER_MAP));
        $set = 0;

        foreach ($fields as $field) {
            $value = $this->settings->get('headers.' . $field, '');

            if ($value === true || (is_string($value) && $value !== '')) {
                $set++;
            }
        }

        return (int) round(($set / count($fields)) * 100);
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
            'x_content_type_options' => !empty($input['x_content_type_options']),
        ];

        foreach (array_keys(self::HEADER_MAP) as $field) {
            $value = isset($input[$field]) ? (string) $input[$field] : '';
            $sanitized[$field] = $this->cleanHeaderValue(sanitize_text_field($value));
        }

        return $sanitized;
    }

    public function sendHeaders()
    {
        if (is_admin() || headers_sent()) {
            return;
        }

        if (!$this->enabled()) {
            return;
        }

        if ($this->settings->get('headers.x_content_type_options', true)) {
            header('X-Content-Type-Options: nosniff');
        }

        foreach (self::HEADER_MAP as $field => $headerName) {
            $value = $this->settings->get('headers.' . $field, '');

            if ($value === '') {
                continue;
            }

            header($headerName . ': ' . $this->cleanHeaderValue((string) $value));
        }
    }
}
