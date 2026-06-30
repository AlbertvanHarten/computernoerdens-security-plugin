<?php
/**
 * @var array<string, \Computernoerden\Security\Contracts\ModuleInterface> $modules
 * @var int $score
 * @var \Computernoerden\Security\Settings\SettingsRepository $settings
 * @var \Computernoerden\Security\Modules\Scanner\ScannerModule|null $scanner
 * @var \Computernoerden\Security\Settings\EditionManager $edition
 */
defined('ABSPATH') || exit;

$lastResults = $settings->get('scanner.last_results', []);
$lastResults = is_array($lastResults) ? $lastResults : [];
$lastRun = $settings->get('scanner.last_run');

$warnings = array_filter($lastResults, function ($r) {
    return ($r['status'] ?? '') === 'fail';
});
$recommendations = array_filter($lastResults, function ($r) {
    return ($r['status'] ?? '') === 'warn';
});

$scoreClass = $score >= 80 ? 'is-good' : ($score >= 50 ? 'is-medium' : 'is-poor');
?>
<div class="wrap cno-security">
    <div class="cno-security-hero">
        <div>
            <div class="cno-kicker">Security Center</div>
            <h1>Computernørden's Security Plugin</h1>
            <p>Professional WordPress hardening for HTTPS, CSP, security headers, security.txt and local readiness scanning.</p>
            <?php if ($scanner) : ?>
                <button type="button" class="button button-primary" data-run-scan>
                    <?php echo $lastRun ? 'Re-run scan' : 'Run first scan'; ?>
                </button>
                <?php if ($lastRun) : ?>
                    <span class="cno-scan-meta">Last scan: <?php echo esc_html(human_time_diff((int) $lastRun)); ?> ago</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="cno-score-card cno-score-<?php echo esc_attr($scoreClass); ?>">
            <span class="cno-score-label">Security score</span>
            <strong data-overall-score><?php echo esc_html((string) $score); ?>%</strong>
            <span>Based on enabled modules + latest scan</span>
        </div>
    </div>

    <?php if (!empty($warnings)) : ?>
        <div class="cno-alert cno-alert-fail">
            <h3>Warnings</h3>
            <ul>
                <?php foreach ($warnings as $w) : ?>
                    <li><strong><?php echo esc_html($w['label']); ?>:</strong> <?php echo esc_html($w['message']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($recommendations)) : ?>
        <div class="cno-alert cno-alert-warn">
            <h3>Recommendations</h3>
            <ul>
                <?php foreach ($recommendations as $r) : ?>
                    <li><strong><?php echo esc_html($r['label']); ?>:</strong> <?php echo esc_html($r['message']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="cno-grid">
        <?php foreach ($modules as $module) : ?>
            <div class="cno-card">
                <div class="cno-card-header">
                    <h2><?php echo esc_html($module->name()); ?></h2>
                    <span class="cno-status"><?php echo esc_html($module->status()); ?></span>
                </div>
                <p><?php echo esc_html($module->description()); ?></p>
                <?php if ($module->countsTowardScore()) : ?>
                    <div class="cno-progress">
                        <span style="width: <?php echo esc_attr((string) $module->score()); ?>%"></span>
                    </div>
                <?php endif; ?>
                <a class="cno-card-link" href="<?php echo esc_url($module->configureUrl()); ?>">Configure &rarr;</a>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="cno-toast" class="cno-toast" aria-live="polite"></div>
</div>
