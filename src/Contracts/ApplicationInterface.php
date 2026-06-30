<?php

namespace Computernoerden\Security\Contracts;

defined('ABSPATH') || exit;

interface ApplicationInterface
{
    public function boot();

    public function container();
}
