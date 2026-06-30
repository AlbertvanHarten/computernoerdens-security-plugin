<?php

namespace Computernoerden\Security\Admin;

use Computernoerden\Security\Modules\ModuleManager;
use Computernoerden\Security\Settings\EditionManager;
use Computernoerden\Security\Settings\SettingsRepository;

defined('ABSPATH') || exit;

class Admin
{
    const PAGE_SLUG = 'computernoerdens-security-plugin';
    const MODULES_PAGE_SLUG = 'computernoerdens-security-plugin-modules';
    const DIAGNOSTICS_PAGE_SLUG = 'computernoerdens-security-plugin-diagnostics';

    /**
     * @var ModuleManager
     */
    private $modules;

    /**
     * @var SettingsRepository
     */
    private $settings;

    /**
     * @var EditionManager
     */
    private $edition;

    public function __construct(ModuleManager $modules, SettingsRepository $settings, EditionManager $edition)
    {
        $this->modules = $modules;
        $this->settings = $settings;
        $this->edition = $edition;
    }

    public function boot()
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenu()
    {
        add_menu_page(
            "Computernørden's Security Plugin",
            'Computernørden',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderSecurityCenter'],
            'dashicons-shield-alt',
            58
        );

        add_submenu_page(
            self::PAGE_SLUG,
            'Security Center',
            'Security Center',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderSecurityCenter']
        );

        add_submenu_page(
            self::PAGE_SLUG,
            'Modules',
            'Modules',
            'manage_options',
            self::MODULES_PAGE_SLUG,
            [$this, 'renderModules']
        );

        add_submenu_page(
            self::PAGE_SLUG,
            'Diagnostics',
            'Diagnostics',
            'manage_options',
            self::DIAGNOSTICS_PAGE_SLUG,
            [$this, 'renderDiagnostics']
        );
    }

    /**
     * @param string $hook
     */
    public function enqueueAssets($hook)
    {
        if (strpos($hook, self::PAGE_SLUG) === false) {
            return;
        }

        wp_enqueue_style('cno-security-admin', CNO_SECURITY_PLUGIN_URL . 'assets/css/admin.css', [], CNO_SECURITY_VERSION);
        wp_enqueue_script('cno-security-admin', CNO_SECURITY_PLUGIN_URL . 'assets/js/admin.js', [], CNO_SECURITY_VERSION, true);

        wp_localize_script('cno-security-admin', 'cnoSecurity', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cno_security_ajax'),
            'i18n' => [
                'savedDefault' => __('Saved.', 'computernoerdens-security-plugin'),
                'errorDefault' => __('Something went wrong. Please try again.', 'computernoerdens-security-plugin'),
                'scanning' => __('Scanning…', 'computernoerdens-security-plugin'),
            ],
        ]);
    }

    public function renderSecurityCenter()
    {
        $modules = $this->modules->all();
        $score = $this->modules->overallScore();
        $settings = $this->settings;
        $scanner = $this->modules->get('scanner');
        $edition = $this->edition;

        require CNO_SECURITY_PLUGIN_DIR . 'templates/admin/security-center.php';
    }

    public function renderModules()
    {
        $modules = $this->modules->all();
        $settings = $this->settings;
        $edition = $this->edition;

        require CNO_SECURITY_PLUGIN_DIR . 'templates/admin/modules.php';
    }

    public function renderDiagnostics()
    {
        $diagnostics = $this->modules->get('diagnostics');
        $reports = $this->modules->get('reports');
        $edition = $this->edition;

        require CNO_SECURITY_PLUGIN_DIR . 'templates/admin/diagnostics.php';
    }
}
