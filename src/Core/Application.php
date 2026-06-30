<?php

namespace Computernoerden\Security\Core;

use Computernoerden\Security\Container\Container;

defined('ABSPATH') || exit;

/**
 * Main application bootstrap.
 */
class Application
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
        // Modules will be registered here.
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