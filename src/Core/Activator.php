<?php

namespace Computernoerden\Security\Core;

use Computernoerden\Security\Settings\SettingsRepository;

defined('ABSPATH') || exit;

class Activator
{
    public static function activate()
    {
        $settings = new SettingsRepository();
        $settings->installDefaults();

        // The security.txt module registers a rewrite rule; make sure
        // it's picked up immediately rather than waiting for WordPress
        // to flush rewrite rules on its own schedule.
        flush_rewrite_rules();
    }
}
