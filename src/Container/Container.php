<?php

namespace Computernoerden\Security\Container;

use Computernoerden\Security\Contracts\ContainerInterface;

defined('ABSPATH') || exit;

class Container implements ContainerInterface
{
    private $services = array();

    public function set($id, $service)
    {
        $this->services[$id] = $service;
    }

    public function get($id)
    {
        if (!$this->has($id)) {
            return null;
        }

        return $this->services[$id];
    }

    public function has($id)
    {
        return array_key_exists($id, $this->services);
    }
}
