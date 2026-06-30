<?php

namespace Computernoerden\Security\Modules;

use Computernoerden\Security\Contracts\ModuleInterface;
use Computernoerden\Security\Settings\SettingsRepository;

defined('ABSPATH') || exit;

abstract class AbstractModule implements ModuleInterface
{
    /**
     * @var SettingsRepository
     */
    protected $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    public function enabledByDefault(): bool
    {
        return true;
    }

    public function enabled(): bool
    {
        return $this->settings->moduleEnabled($this->id());
    }

    public function status(): string
    {
        return $this->enabled() ? 'Active' : 'Disabled';
    }

    public function score(): int
    {
        return $this->enabled() ? 100 : 0;
    }

    public function countsTowardScore(): bool
    {
        return true;
    }

    public function configureUrl(): string
    {
        return admin_url('admin.php?page=computernoerdens-security-plugin-modules#' . $this->id());
    }

    public function hasSettings(): bool
    {
        return false;
    }

    public function settingsKey(): string
    {
        return $this->id();
    }

    public function sanitizeSettings(array $input): array
    {
        return [];
    }

    /**
     * Shared helper: strips header-injection-unsafe characters from a
     * value before it's sent as an HTTP header.
     */
    protected function cleanHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
