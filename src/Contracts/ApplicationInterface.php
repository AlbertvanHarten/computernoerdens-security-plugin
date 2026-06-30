<?php

namespace Computernoerden\Security\Contracts;

defined('ABSPATH') || exit;

/**
 * Defines the application contract.
 */
interface ApplicationInterface
{
    /**
     * Boot the application.
     *
     * @return void
     */
    public function boot();

    /**
     * Get the service container.
     *
     * @return ContainerInterface
     */
    public function container();
}