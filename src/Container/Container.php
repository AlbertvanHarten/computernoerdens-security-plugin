<?php

namespace Computernoerden\Security\Container;

defined('ABSPATH') || exit;

/**
 * Simple dependency injection container.
 */
use Computernoerden\Security\Contracts\ContainerInterface;

class Container implements ContainerInterface{
    /**
     * Registered services.
     *
     * @var array
     */
    private $services = [];

    /**
     * Register a service.
     *
     * @param string $id
     * @param mixed  $service
     */
    public function set($id, $service)
    {
        $this->services[$id] = $service;
    }

    /**
     * Retrieve a service.
     *
     * @param string $id
     *
     * @return mixed|null
     */
    public function get($id)
    {
        if (!isset($this->services[$id])) {
            return null;
        }

        return $this->services[$id];
    }

    /**
     * Check whether a service exists.
     *
     * @param string $id
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->services[$id]);
    }
}