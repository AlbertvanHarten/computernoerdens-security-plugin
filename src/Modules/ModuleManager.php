<?php

namespace Computernoerden\Security\Modules;

use Computernoerden\Security\Contracts\ModuleInterface;

defined('ABSPATH') || exit;

class ModuleManager
{
    private $modules = array();

    public function __construct()
    {
        $this->register(new PlaceholderModule('https', 'HTTPS', 'HTTP to HTTPS redirects and HTTPS diagnostics.', true, 'Ready'));
        $this->register(new PlaceholderModule('headers', 'Security Headers', 'Browser security headers and hardening.', true, 'Ready'));
        $this->register(new PlaceholderModule('csp', 'Content Security Policy', 'Nonce-based CSP, strictness profiles and learning mode.', true, 'Planned'));
        $this->register(new PlaceholderModule('security_txt', 'security.txt', 'Security contact policy and /.well-known/security.txt.', true, 'Planned'));
        $this->register(new PlaceholderModule('scanner', 'Scanner', 'sikkerpånettet.dk and Internet.nl readiness checks.', true, 'Planned'));
        $this->register(new PlaceholderModule('reports', 'Reports', 'Client-facing security reports.', false, 'Planned'));
    }

    public function register(ModuleInterface $module)
    {
        $this->modules[$module->id()] = $module;
    }

    public function boot()
    {
        foreach ($this->modules as $module) {
            $module->boot();
        }
    }

    public function all()
    {
        return $this->modules;
    }

    public function has($module_id)
    {
        return isset($this->modules[$module_id]);
    }
}
