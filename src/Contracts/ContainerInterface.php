<?php

namespace Computernoerden\Security\Contracts;

defined('ABSPATH') || exit;

/**
 * Defines the service container contract.
 */
interface ContainerInterface
{
    /**
     * Register a service.
     *
     * @param string $id
     * @param mixed  $service
     *
     * @return void
     */
    public function set($id, $service);

    /**
     * Retrieve a service.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function get($id);

    /**
     * Determine whether a service exists.
     *
     * @param string $id
     *
     * @return bool
     */
    public function has($id);
}