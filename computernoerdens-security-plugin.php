<?php
/**
 * Plugin Name: Computernørden's Security Plugin
 * Plugin URI: https://computernoerden.dk/
 * Description: A professional WordPress hardening plugin: HTTPS, security headers, a full Content Security Policy builder with nonce/hash/learning mode, security.txt, a local readiness scanner, diagnostics and reports.
 * Version: 2.1.0-alpha1
 * Requires at least: 6.5
 * Requires PHP: 7.0
 * Author: Computernørden ApS
 * Author URI: https://computernoerden.dk/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: computernoerdens-security-plugin
 */

defined('ABSPATH') || exit;

define('CNO_SECURITY_VERSION', '2.1.0-alpha1');
define('CNO_SECURITY_PLUGIN_FILE', __FILE__);
define('CNO_SECURITY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CNO_SECURITY_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook(__FILE__, array('Computernoerden\Security\Core\Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Computernoerden\Security\Core\Deactivator', 'deactivate'));

add_action('plugins_loaded', function () {
    $app = new Computernoerden\Security\Core\Application();
    $app->boot();

    // A single, deliberate global entry point — not scattered global
    // state — so theme/plugin authors can reach the CSP nonce without
    // the plugin needing to expose its DI container more broadly.
    // Mirrors the pattern used by most major WordPress plugins (e.g.
    // a singleton accessor) for the one thing external code legitimately
    // needs: the current request's nonce.
    $GLOBALS['cno_security_app'] = $app;
});

if (!function_exists('cno_security_csp_nonce')) {
    /**
     * Returns the current request's CSP nonce, for themes/plugins that
     * print their own inline <script>/<style> tags and want them to
     * pass a nonce-based Content-Security-Policy.
     *
     * Returns an empty string if the CSP module is disabled or nonce
     * support isn't turned on, so it's always safe to print directly
     * into a nonce="" attribute.
     */
    function cno_security_csp_nonce(): string
    {
        if (empty($GLOBALS['cno_security_app'])) {
            return '';
        }

        $modules = $GLOBALS['cno_security_app']->container()->get('modules');
        $csp = $modules ? $modules->get('csp') : null;

        return $csp ? $csp->nonce() : '';
    }
}
