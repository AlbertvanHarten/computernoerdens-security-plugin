<?php

namespace Computernoerden\Security\Contracts;

defined('ABSPATH') || exit;

interface ContainerInterface
{
    public function set($id, $service);

    public function get($id);

    public function has($id);
}
