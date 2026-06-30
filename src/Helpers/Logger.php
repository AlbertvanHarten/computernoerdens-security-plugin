<?php

namespace Computernoerden\Security\Helpers;

defined('ABSPATH') || exit;

/**
 * A deliberately simple, capped log stored in a single WordPress
 * option (autoload disabled). Not a general-purpose logging framework
 * — just enough for the plugin's own diagnostic trail.
 */
class Logger
{
    const OPTION = 'cno_security_log';
    const MAX_ENTRIES = 200;

    public function info(string $message, array $context = [])
    {
        $this->write('info', $message, $context);
    }

    public function warning(string $message, array $context = [])
    {
        $this->write('warning', $message, $context);
    }

    public function error(string $message, array $context = [])
    {
        $this->write('error', $message, $context);
    }

    /**
     * @return array<int, array{level: string, message: string, context: array<string, mixed>, time: int}>
     */
    public function all(): array
    {
        $entries = get_option(self::OPTION, []);

        return is_array($entries) ? $entries : [];
    }

    public function clear()
    {
        delete_option(self::OPTION);
    }

    private function write(string $level, string $message, array $context)
    {
        $entries = $this->all();

        $entries[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'time' => time(),
        ];

        if (count($entries) > self::MAX_ENTRIES) {
            $entries = array_slice($entries, -self::MAX_ENTRIES);
        }

        update_option(self::OPTION, $entries, false);
    }
}
