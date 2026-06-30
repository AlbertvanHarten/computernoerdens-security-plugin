<?php

namespace Computernoerden\Security\Core;

use Computernoerden\Security\Admin\Admin;
use Computernoerden\Security\Ajax\AjaxController;
use Computernoerden\Security\Container\Container;
use Computernoerden\Security\Helpers\Logger;
use Computernoerden\Security\Modules\ModuleManager;
use Computernoerden\Security\REST\CspReportController;
use Computernoerden\Security\Settings\EditionManager;
use Computernoerden\Security\Settings\SettingsRepository;

defined('ABSPATH') || exit;

/**
 * Application bootstrap.
 *
 * No corresponding interface: there is exactly one implementation and
 * nothing instantiates an alternative, so an interface here would be
 * pure ceremony.
 */
class Application
{
    /**
     * @var Container
     */
    private $container;

    public function __construct()
    {
        $this->container = new Container();
    }

    public function boot()
    {
        $settings = new SettingsRepository();
        $settings->installDefaults();

        $edition = new EditionManager();
        $logger = new Logger();

        $moduleManager = new ModuleManager($settings, $edition);

        $this->container->set('settings', $settings);
        $this->container->set('edition', $edition);
        $this->container->set('logger', $logger);
        $this->container->set('modules', $moduleManager);

        // Modules only boot their hooks when enabled (lazy loading per
        // the project spec — disabled modules cost nothing at runtime).
        $moduleManager->boot();

        $admin = new Admin($moduleManager, $settings, $edition);
        $admin->boot();

        $ajax = new AjaxController($moduleManager, $settings);
        $ajax->boot();

        $cspReports = new CspReportController($settings, $logger);
        $cspReports->boot();
    }

    public function container()
    {
        return $this->container;
    }
}
