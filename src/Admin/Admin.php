<?php

namespace Computernoerden\Security\Admin;

use Computernoerden\Security\Modules\ModuleManager;

defined('ABSPATH') || exit;

class Admin
{
    private $modules;

    public function __construct(ModuleManager $modules)
    {
        $this->modules = $modules;
    }

    public function boot()
    {
        add_action('admin_menu', array($this, 'registerMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'));
        add_action('wp_ajax_cno_security_toggle_module', array($this, 'ajaxToggleModule'));
    }

    public function registerMenu()
    {
        add_menu_page(
            "Computernørden's Security Plugin",
            'Computernørden',
            'manage_options',
            'computernoerdens-security-plugin',
            array($this, 'renderSecurityCenter'),
            'dashicons-shield-alt',
            58
        );

        add_submenu_page('computernoerdens-security-plugin', 'Security Center', 'Security Center', 'manage_options', 'computernoerdens-security-plugin', array($this, 'renderSecurityCenter'));
        add_submenu_page('computernoerdens-security-plugin', 'Modules', 'Modules', 'manage_options', 'computernoerdens-security-plugin-modules', array($this, 'renderModules'));
    }

    public function enqueueAssets($hook)
    {
        if (strpos($hook, 'computernoerdens-security-plugin') === false) {
            return;
        }

        wp_enqueue_style('cno-security-admin', plugin_dir_url(CNO_SECURITY_PLUGIN_FILE) . 'assets/css/admin.css', array(), CNO_SECURITY_VERSION);
        wp_enqueue_script('cno-security-admin', plugin_dir_url(CNO_SECURITY_PLUGIN_FILE) . 'assets/js/admin.js', array(), CNO_SECURITY_VERSION, true);
        wp_localize_script('cno-security-admin', 'cnoSecurity', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cno_security_ajax'),
        ));
    }

    public function renderSecurityCenter()
    {
        $modules = $this->modules->all();
        require dirname(__DIR__, 2) . '/templates/admin/security-center.php';
    }

    public function renderModules()
    {
        $modules = $this->modules->all();
        require dirname(__DIR__, 2) . '/templates/admin/modules.php';
    }

    public function ajaxToggleModule()
    {
        check_ajax_referer('cno_security_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'), 403);
        }

        $module = isset($_POST['module']) ? sanitize_key(wp_unslash($_POST['module'])) : '';
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1';

        if (!$this->modules->has($module)) {
            wp_send_json_error(array('message' => 'Unknown module.'), 404);
        }

        wp_send_json_success(array(
            'message' => $enabled ? 'Module enabled.' : 'Module disabled.',
            'module' => $module,
            'enabled' => $enabled,
        ));
    }
}
