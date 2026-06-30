<?php

namespace Computernoerden\Security\Modules;

use Computernoerden\Security\Contracts\ModuleInterface;

defined('ABSPATH') || exit;

class PlaceholderModule implements ModuleInterface
{
    private $id;
    private $name;
    private $description;
    private $enabled_by_default;
    private $status;

    public function __construct($id, $name, $description, $enabled_by_default, $status)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->enabled_by_default = $enabled_by_default;
        $this->status = $status;
    }

    public function id() { return $this->id; }

    public function name() { return $this->name; }

    public function description() { return $this->description; }

    public function boot() {}

    public function enabledByDefault() { return $this->enabled_by_default; }

    public function status() { return $this->status; }
}
