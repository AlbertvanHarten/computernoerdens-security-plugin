<?php
/**
 * @var \Computernoerden\Security\Modules\SecurityTxt\SecurityTxtModule $module
 * @var \Computernoerden\Security\Settings\SettingsRepository $settings
 */
defined('ABSPATH') || exit;

$txt = $settings->get('security_txt', []);
?>
<div class="cno-settings-grid">
    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="enabled" <?php checked(!empty($txt['enabled'])); ?>>
        <span>Publish /.well-known/security.txt</span>
    </label>

    <label class="cno-field cno-field-wide">
        <span>Contact <em>(one per line — email, mailto:, or https:// link)</em></span>
        <textarea data-field="contact" rows="2"><?php echo esc_textarea((string) ($txt['contact'] ?? '')); ?></textarea>
    </label>

    <label class="cno-field">
        <span>Expires</span>
        <input type="text" data-field="expires" value="<?php echo esc_attr((string) ($txt['expires'] ?? '')); ?>" placeholder="2027-06-30T00:00:00Z">
    </label>

    <label class="cno-field">
        <span>Preferred-Languages</span>
        <input type="text" data-field="preferred_languages" value="<?php echo esc_attr((string) ($txt['preferred_languages'] ?? '')); ?>" placeholder="da, en">
    </label>

    <label class="cno-field">
        <span>Encryption (PGP key URL)</span>
        <input type="text" data-field="encryption" value="<?php echo esc_attr((string) ($txt['encryption'] ?? '')); ?>">
    </label>

    <label class="cno-field">
        <span>Acknowledgments</span>
        <input type="text" data-field="acknowledgments" value="<?php echo esc_attr((string) ($txt['acknowledgments'] ?? '')); ?>">
    </label>

    <label class="cno-field">
        <span>Canonical</span>
        <input type="text" data-field="canonical" value="<?php echo esc_attr((string) ($txt['canonical'] ?? '')); ?>">
    </label>

    <label class="cno-field">
        <span>Policy</span>
        <input type="text" data-field="policy" value="<?php echo esc_attr((string) ($txt['policy'] ?? '')); ?>">
    </label>

    <label class="cno-field">
        <span>Hiring</span>
        <input type="text" data-field="hiring" value="<?php echo esc_attr((string) ($txt['hiring'] ?? '')); ?>">
    </label>
</div>
<p class="cno-hint">
    Will be published at
    <code><?php echo esc_html(home_url('/.well-known/security.txt')); ?></code>
    once enabled.
</p>
