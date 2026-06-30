<?php

namespace Computernoerden\Security\Contracts;

defined('ABSPATH') || exit;

/**
 * Contract for a self-contained security module.
 *
 * Kept as an interface (rather than an abstract class alone) because the
 * module manager and admin UI both depend on multiple concrete
 * implementations interchangeably (HTTPS, Headers, CSP, security.txt,
 * Scanner, Diagnostics, Reports, ...).
 */
interface ModuleInterface
{
    /**
     * Unique, stable module identifier (snake_case). Also used as the
     * settings key under the plugin's single option, unless
     * settingsKey() returns something different.
     */
    public function id(): string;

    public function name(): string;

    public function description(): string;

    /**
     * Wire up the module's WordPress hooks. Only called when the module
     * is enabled, so modules should assume they are active once boot()
     * runs.
     */
    public function boot();

    public function enabledByDefault(): bool;

    public function enabled(): bool;

    /**
     * Short human-readable status label shown on the Modules page,
     * e.g. "Enforced", "Report-Only", "Disabled".
     */
    public function status(): string;

    /**
     * 0-100 contribution to the overall security score.
     */
    public function score(): int;

    /**
     * Whether this module's score() should count toward the dashboard's
     * overall score. Utility modules (Diagnostics, Reports) opt out.
     */
    public function countsTowardScore(): bool;

    public function configureUrl(): string;

    /**
     * Whether this module exposes a settings panel on the Modules page.
     */
    public function hasSettings(): bool;

    /**
     * Settings array key under the plugin option. Defaults to id().
     */
    public function settingsKey(): string;

    /**
     * Validate and sanitize raw settings input (e.g. from an AJAX
     * request) before it is persisted. Implementations should only
     * return keys they recognise.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitizeSettings(array $input): array;
}
