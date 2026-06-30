<?php

namespace Computernoerden\Security\Core;

use Computernoerden\Security\Admin\Admin;
use Computernoerden\Security\Container\Container;
use Computernoerden\Security\Contracts\ApplicationInterface;
use Computernoerden\Security\Modules\ModuleManager;

defined('ABSPATH') || exit;

class Application implements ApplicationInterface
{
    private $container;

    public function __construct()
    {
        $this->container = new Container();
    }

    public function boot()
    {
        $module_manager = new ModuleManager();

        $this->container->set('modules', $module_manager);

        $admin = new Admin($module_manager);
        $admin->boot();

        $module_manager->boot();
    }

    public function container()
    {
        return $this->container;
    }
}
