<?php
/**
 * @var \Computernoerden\Security\Modules\Https\HttpsModule $module
 * @var \Computernoerden\Security\Settings\SettingsRepository $settings
 */
defined('ABSPATH') || exit;

$https = $settings->get('https', []);
?>
<div class="cno-settings-grid">
    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="redirect_enabled" <?php checked(!empty($https['redirect_enabled'])); ?>>
        <span>Redirect HTTP requests to HTTPS</span>
    </label>

    <label class="cno-field">
        <span>Redirect status code</span>
        <select data-field="redirect_status">
            <?php foreach ([301, 302, 307, 308] as $code) : ?>
                <option value="<?php echo esc_attr((string) $code); ?>" <?php selected((int) ($https['redirect_status'] ?? 301), $code); ?>><?php echo esc_html((string) $code); ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="force_ssl" <?php checked(!empty($https['force_ssl'])); ?>>
        <span>Force HTTPS in generated site/home URLs</span>
    </label>

    <hr class="cno-field-divider">

    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="hsts_enabled" <?php checked(!empty($https['hsts_enabled'])); ?>>
        <span>Enable HSTS (Strict-Transport-Security)</span>
    </label>

    <label class="cno-field">
        <span>HSTS max-age (seconds)</span>
        <input type="number" min="0" step="1" data-field="hsts_max_age" value="<?php echo esc_attr((string) ($https['hsts_max_age'] ?? 15552000)); ?>">
    </label>

    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="hsts_include_subdomains" <?php checked(!empty($https['hsts_include_subdomains'])); ?>>
        <span>includeSubDomains</span>
    </label>

    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="hsts_preload" <?php checked(!empty($https['hsts_preload'])); ?>>
        <span>preload <em>(only enable once you intend to submit to hstspreload.org — this is hard to reverse)</em></span>
    </label>

    <hr class="cno-field-divider">

    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="trust_reverse_proxy" <?php checked(!empty($https['trust_reverse_proxy'])); ?>>
        <span>Trust X-Forwarded-Proto from a reverse proxy / load balancer</span>
    </label>

    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="cloudflare_aware" <?php checked(!empty($https['cloudflare_aware'])); ?>>
        <span>Cloudflare aware (recognise Flexible SSL visitors as HTTPS)</span>
    </label>
</div>
