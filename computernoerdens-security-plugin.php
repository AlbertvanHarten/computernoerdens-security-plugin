<?php
/**
 * Plugin Name: Computernørden's Security Plugin
 * Plugin URI: https://computernoerden.dk/
 * Description: A modern Security Center for WordPress.
 * Version: 2.0.0-alpha1
 * Requires at least: 6.5
 * Requires PHP: 7.0
 * Author: Computernørden ApS
 * Author URI: https://computernoerden.dk/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: computernoerdens-security-plugin
 */

defined('ABSPATH') || exit;

define('CNO_SECURITY_VERSION', '2.0.0-alpha1');
define('CNO_SECURITY_PLUGIN_FILE', __FILE__);

require_once __DIR__ . '/vendor/autoload.php';

(new Computernoerden\Security\Core\Application())->boot();
