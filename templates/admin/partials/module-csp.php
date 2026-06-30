<?php
/**
 * @var \Computernoerden\Security\Modules\Csp\CspModule $module
 * @var \Computernoerden\Security\Settings\SettingsRepository $settings
 */

use Computernoerden\Security\Modules\Csp\CspModule;
use Computernoerden\Security\Modules\Csp\CspViolationStore;

defined('ABSPATH') || exit;

$csp = $settings->get('csp', []);
$directives = is_array($csp['directives'] ?? null) ? $csp['directives'] : [];
$hashes = is_array($csp['hashes'] ?? null) ? $csp['hashes'] : [];
$violationStore = new CspViolationStore($settings);
$violations = $violationStore->summarized();
?>
<div class="cno-settings-grid">
    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="enabled" <?php checked(!empty($csp['enabled'])); ?>>
        <span>Send a CSP header</span>
    </label>

    <label class="cno-field">
        <span>Mode</span>
        <select data-field="mode">
            <option value="report-only" <?php selected((string) ($csp['mode'] ?? 'report-only'), 'report-only'); ?>>Report-Only</option>
            <option value="enforce" <?php selected((string) ($csp['mode'] ?? 'report-only'), 'enforce'); ?>>Enforce</option>
        </select>
    </label>

    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="learning_mode" <?php checked(!empty($csp['learning_mode'])); ?>>
        <span>Learning mode <em>(forces Report-Only and collects violations below)</em></span>
    </label>

    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="nonce_script" <?php checked(!empty($csp['nonce_script'])); ?>>
        <span>Add a per-request nonce to script-src</span>
    </label>

    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="nonce_style" <?php checked(!empty($csp['nonce_style'])); ?>>
        <span>Add a per-request nonce to style-src</span>
    </label>

    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="report_uri_enabled" <?php checked(!empty($csp['report_uri_enabled'])); ?>>
        <span>Send report-uri / report-to (required for learning mode)</span>
    </label>
</div>

<h4 class="cno-subhead">Directives</h4>
<p class="cno-hint">One source list per directive (space-separated, e.g. <code>'self' https://fonts.googleapis.com</code>). Leave blank to omit a directive entirely. Custom/arbitrary directive names are roadmap — see docs/ROADMAP.md.</p>
<div class="cno-directive-list">
    <?php foreach (CspModule::KNOWN_DIRECTIVES as $directive) : ?>
        <label class="cno-field cno-field-directive">
            <span><?php echo esc_html($directive); ?></span>
            <input type="text" data-field="directive_<?php echo esc_attr($directive); ?>" value="<?php echo esc_attr((string) ($directives[$directive] ?? '')); ?>" placeholder="e.g. 'self' https:">
        </label>
    <?php endforeach; ?>
</div>

<h4 class="cno-subhead">Hash allowlist</h4>
<p class="cno-hint">For specific inline scripts/styles you don't want to rewrite with a nonce. Up to <?php echo (int) CspModule::MAX_HASH_ROWS; ?> entries.</p>
<table class="cno-hash-table">
    <thead>
        <tr><th>Directive</th><th>Algorithm</th><th>Hash (base64)</th></tr>
    </thead>
    <tbody>
        <?php for ($i = 0; $i < CspModule::MAX_HASH_ROWS; $i++) : ?>
            <?php $hash = $hashes[$i] ?? []; ?>
            <tr>
                <td><input type="text" data-field="hash_<?php echo (int) $i; ?>_directive" value="<?php echo esc_attr((string) ($hash['directive'] ?? '')); ?>" placeholder="script-src"></td>
                <td>
                    <select data-field="hash_<?php echo (int) $i; ?>_algorithm">
                        <?php foreach (['sha256', 'sha384', 'sha512'] as $algo) : ?>
                            <option value="<?php echo esc_attr($algo); ?>" <?php selected((string) ($hash['algorithm'] ?? 'sha256'), $algo); ?>><?php echo esc_html($algo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" data-field="hash_<?php echo (int) $i; ?>_hash" value="<?php echo esc_attr((string) ($hash['hash'] ?? '')); ?>"></td>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>

<h4 class="cno-subhead">Learning mode — recent violations</h4>
<?php if (empty($violations)) : ?>
    <p class="cno-hint">No violations recorded yet. Enable learning mode and browse the site to start collecting data.</p>
<?php else : ?>
    <table class="cno-violation-table">
        <thead>
            <tr><th>Directive</th><th>Blocked source</th><th>Count</th><th>Last seen</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($violations as $violation) : ?>
                <tr>
                    <td><?php echo esc_html($violation['directive']); ?></td>
                    <td><code><?php echo esc_html($violation['source']); ?></code></td>
                    <td><?php echo esc_html((string) $violation['count']); ?></td>
                    <td><?php echo esc_html(human_time_diff($violation['lastSeen'])); ?> ago</td>
                    <td>
                        <button
                            type="button"
                            class="button button-small"
                            data-apply-violation
                            data-directive="<?php echo esc_attr($violation['directive']); ?>"
                            data-source="<?php echo esc_attr($violation['source']); ?>"
                        >Add to policy</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="button" class="button-link" data-clear-violations>Clear violation log</button>
<?php endif; ?>
