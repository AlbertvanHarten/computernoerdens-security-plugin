<?php defined('ABSPATH') || exit; ?>
<div class="wrap cno-security">
    <div class="cno-security-hero">
        <div>
            <div class="cno-kicker">Security Center</div>
            <h1>Computernørden's Security Plugin</h1>
            <p>Professional WordPress security hardening with CSP, HTTPS, security headers, security.txt and benchmark readiness.</p>
        </div>
        <div class="cno-score-card">
            <span class="cno-score-label">Current foundation</span>
            <strong>Alpha</strong>
            <span>Framework online</span>
        </div>
    </div>

    <div class="cno-grid">
        <?php foreach ($modules as $module) : ?>
            <div class="cno-card">
                <div class="cno-card-header">
                    <h2><?php echo esc_html($module->name()); ?></h2>
                    <span class="cno-status"><?php echo esc_html($module->status()); ?></span>
                </div>
                <p><?php echo esc_html($module->description()); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
