<?php

namespace Computernoerden\Security\Modules\Reports;

use Computernoerden\Security\Modules\AbstractModule;
use Computernoerden\Security\Modules\ModuleManager;
use Computernoerden\Security\Settings\EditionManager;
use Computernoerden\Security\Settings\SettingsRepository;

defined('ABSPATH') || exit;

/**
 * Builds a point-in-time summary of every module's status/score plus
 * the latest scanner results, exportable as CSV/JSON in the Free
 * edition. Branded, white-labelled PDF client reports are a Pro
 * feature (see docs/FREE-PRO.md) — the gate already exists here so
 * Pro can light it up later without touching this class.
 */
class ReportsModule extends AbstractModule
{
    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var EditionManager
     */
    private $edition;

    public function __construct(SettingsRepository $settings, ModuleManager $moduleManager, EditionManager $edition = null)
    {
        parent::__construct($settings);

        $this->moduleManager = $moduleManager;
        $this->edition = $edition ?: new EditionManager();
    }

    public function id(): string
    {
        return 'reports';
    }

    public function name(): string
    {
        return 'Reports';
    }

    public function description(): string
    {
        return 'A point-in-time summary of every module and the latest scan, exportable as CSV/JSON. Branded PDF client reports are a Pro feature.';
    }

    public function boot()
    {
        // No runtime hooks: reports are generated on demand.
    }

    public function status(): string
    {
        return $this->enabled() ? 'Available' : 'Disabled';
    }

    public function countsTowardScore(): bool
    {
        return false;
    }

    /**
     * @return array<int, array{id: string, name: string, status: string, score: int}>
     */
    public function summary(): array
    {
        $rows = [];

        foreach ($this->moduleManager->all() as $module) {
            if ($module->id() === $this->id()) {
                continue;
            }

            $rows[] = [
                'id' => $module->id(),
                'name' => $module->name(),
                'status' => $module->status(),
                'score' => $module->score(),
            ];
        }

        return $rows;
    }

    public function toCsv(): string
    {
        $rows = $this->summary();
        $lines = ['Module,Status,Score'];

        foreach ($rows as $row) {
            $lines[] = sprintf(
                '%s,%s,%d',
                $this->escapeCsv($row['name']),
                $this->escapeCsv($row['status']),
                $row['score']
            );
        }

        return implode("\n", $lines) . "\n";
    }

    public function toJson(): string
    {
        return (string) wp_json_encode([
            'generated_at' => gmdate('c'),
            'overall_score' => $this->moduleManager->overallScore(),
            'modules' => $this->summary(),
        ], JSON_PRETTY_PRINT);
    }

    public function pdfAvailable(): bool
    {
        return $this->edition->isPro();
    }

    private function escapeCsv(string $value): string
    {
        if (strpbrk($value, ",\"\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }
}
