<?php

namespace Computernoerden\Security\Contracts;

defined('ABSPATH') || exit;

interface ModuleInterface
{
    public function id();

    public function name();

    public function description();

    public function boot();

    public function enabledByDefault();

    public function status();
}
