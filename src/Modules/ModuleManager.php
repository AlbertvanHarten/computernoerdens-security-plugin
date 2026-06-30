<?php

namespace Computernoerden\Security\Modules;

use Computernoerden\Security\Contracts\ModuleInterface;
use Computernoerden\Security\Modules\Csp\CspModule;
use Computernoerden\Security\Modules\Diagnostics\DiagnosticsModule;
use Computernoerden\Security\Modules\Headers\HeadersModule;
use Computernoerden\Security\Modules\Https\HttpsModule;
use Computernoerden\Security\Modules\Reports\ReportsModule;
use Computernoerden\Security\Modules\Scanner\ScannerModule;
use Computernoerden\Security\Modules\SecurityTxt\SecurityTxtModule;
use Computernoerden\Security\Settings\EditionManager;
use Computernoerden\Security\Settings\SettingsRepository;

defined('ABSPATH') || exit;

class ModuleManager
{
    /**
     * @var SettingsRepository
     */
    private $settings;

    /**
     * @var array<string, ModuleInterface>
     */
    private $modules = [];

    public function __construct(SettingsRepository $settings, EditionManager $edition = null)
    {
        $this->settings = $settings;
        $edition = $edition ?: new EditionManager();

        $this->register(new HttpsModule($settings));
        $this->register(new HeadersModule($settings));
        $this->register(new CspModule($settings));
        $this->register(new SecurityTxtModule($settings));
        $this->register(new ScannerModule($settings));
        $this->register(new DiagnosticsModule($settings));
        $this->register(new ReportsModule($settings, $this, $edition));
    }

    public function register(ModuleInterface $module)
    {
        $this->modules[$module->id()] = $module;
    }

    /**
     * Boot only the modules that are enabled, so disabled modules add
     * zero hooks and zero overhead.
     */
    public function boot()
    {
        foreach ($this->modules as $module) {
            if ($this->settings->moduleEnabled($module->id())) {
                $module->boot();
            }
        }
    }

    /**
     * @return array<string, ModuleInterface>
     */
    public function all()
    {
        return $this->modules;
    }

    public function has(string $moduleId): bool
    {
        return isset($this->modules[$moduleId]);
    }

    public function get(string $moduleId)
    {
        return $this->has($moduleId) ? $this->modules[$moduleId] : null;
    }

    /**
     * Average score across modules that opt into scoring (utility
     * modules like Diagnostics and Reports are excluded so they don't
     * dilute the security score with something that isn't a hardening
     * measure).
     */
    public function overallScore(): int
    {
        $scored = array_filter($this->modules, function (ModuleInterface $module) {
            return $module->countsTowardScore();
        });

        if (empty($scored)) {
            return 0;
        }

        $total = 0;

        foreach ($scored as $module) {
            $total += (int) $module->score();
        }

        return (int) round($total / count($scored));
    }
}


