<?php

namespace Computernoerden\Security\Container;

defined('ABSPATH') || exit;

/**
 * Minimal service container.
 *
 * Intentionally has no corresponding interface: there is exactly one
 * implementation and nothing in the plugin swaps it out or mocks it in
 * isolation, so an interface would only add indirection.
 */
class Container
{
    /**
     * @var array<string, mixed>
     */
    private $services = [];

    /**
     * @param mixed $service
     */
    public function set(string $id, $service)
    {
        $this->services[$id] = $service;
    }

    /**
     * @return mixed|null
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            return null;
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }
}
