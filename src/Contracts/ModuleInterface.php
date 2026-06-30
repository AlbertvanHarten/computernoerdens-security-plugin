<?php

namespace Computernoerden\Security\Contracts;

defined('ABSPATH') || exit;

/**
 * Defines a plugin module.
 */
interface ModuleInterface
{
    /**
     * Unique module identifier.
     *
     * @return string
     */
    public function id();

    /**
     * Human-readable module name.
     *
     * @return string
     */
    public function name();

    /**
     * Boot the module.
     *
     * @return void
     */
    public function boot();

    /**
     * Whether the module is enabled.
     *
     * @return bool
     */
    public function enabledByDefault();
}