<?php

namespace Computernoerden\Security\Modules\Csp;

use Computernoerden\Security\Settings\SettingsRepository;

defined('ABSPATH') || exit;

/**
 * A real class rather than static helpers because it has two genuinely
 * different consumers (CspModule reads/summarises it for the admin UI;
 * CspReportController writes to it from an unauthenticated REST
 * request) and both need to agree on the same capped storage format.
 */
class CspViolationStore
{
    const MAX_ENTRIES = 200;

    /**
     * @var SettingsRepository
     */
    private $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param array{directive: string, blockedUri: string, documentUri: string} $violation
     */
    public function add(array $violation)
    {
        $log = $this->all();

        $log[] = [
            'directive' => isset($violation['directive']) ? (string) $violation['directive'] : '',
            'blockedUri' => isset($violation['blockedUri']) ? (string) $violation['blockedUri'] : '',
            'documentUri' => isset($violation['documentUri']) ? (string) $violation['documentUri'] : '',
            'time' => time(),
        ];

        if (count($log) > self::MAX_ENTRIES) {
            $log = array_slice($log, -self::MAX_ENTRIES);
        }

        $this->settings->set('csp.violation_log', $log);
    }

    /**
     * @return array<int, array{directive: string, blockedUri: string, documentUri: string, time: int}>
     */
    public function all(): array
    {
        $log = $this->settings->get('csp.violation_log', []);

        return is_array($log) ? $log : [];
    }

    public function clear()
    {
        $this->settings->set('csp.violation_log', []);
    }

    /**
     * Groups raw violation entries by directive + blocked source so
     * the admin UI can offer "add this to the policy" suggestions
     * instead of a flat, repetitive event log.
     *
     * @return array<int, array{directive: string, source: string, count: int, lastSeen: int}>
     */
    public function summarized(int $limit = 25): array
    {
        $buckets = [];

        foreach ($this->all() as $entry) {
            if ($entry['directive'] === '' || $entry['blockedUri'] === '') {
                continue;
            }

            $source = $this->normalizeSource($entry['blockedUri']);
            $key = $entry['directive'] . '|' . $source;

            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'directive' => $entry['directive'],
                    'source' => $source,
                    'count' => 0,
                    'lastSeen' => 0,
                ];
            }

            $buckets[$key]['count']++;
            $buckets[$key]['lastSeen'] = max($buckets[$key]['lastSeen'], (int) $entry['time']);
        }

        usort($buckets, function (array $a, array $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice(array_values($buckets), 0, $limit);
    }

    /**
     * A blocked-uri is usually a full URL ("https://cdn.example.com/x.js")
     * or a special token ("inline", "eval", "data"). For the purposes
     * of suggesting a CSP source, we only need the origin.
     */
    private function normalizeSource(string $blockedUri): string
    {
        $tokens = [
            'inline' => "'unsafe-inline'",
            'eval' => "'unsafe-eval'",
            'data' => 'data:',
            'self' => "'self'",
        ];

        if (isset($tokens[$blockedUri])) {
            return $tokens[$blockedUri];
        }

        $parts = wp_parse_url($blockedUri);

        if (!is_array($parts) || empty($parts['host'])) {
            return $blockedUri;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';

        return $scheme . $parts['host'];
    }
}
