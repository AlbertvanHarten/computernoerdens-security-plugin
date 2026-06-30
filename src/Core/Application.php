<?php

namespace Computernoerden\Security\Core;

use Computernoerden\Security\Contracts\ApplicationInterface;
use Computernoerden\Security\Container\Container;
use Computernoerden\Security\Admin\Admin;

defined('ABSPATH') || exit;

/**
 * Main application bootstrap.
 */
class Application implements ApplicationInterface
{
    /**
     * Service container.
     *
     * @var Container
     */
    private $container;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Boot the application.
     */
    public function boot()
    {
        $admin = new Admin();
        $admin->boot();
    }

    /**
     * Get the service container.
     *
     * @return Container
     */
    public function container()
    {
        return $this->container;
    }
}