<?php
/**
 * @var \Computernoerden\Security\Modules\Headers\HeadersModule $module
 * @var \Computernoerden\Security\Settings\SettingsRepository $settings
 */
defined('ABSPATH') || exit;

$headers = $settings->get('headers', []);
?>
<div class="cno-settings-grid">
    <label class="cno-field cno-field-checkbox">
        <input type="checkbox" data-field="x_content_type_options" <?php checked(!empty($headers['x_content_type_options'])); ?>>
        <span>X-Content-Type-Options: nosniff</span>
    </label>

    <label class="cno-field">
        <span>Referrer-Policy</span>
        <select data-field="referrer_policy">
            <?php foreach ([
                '' => '(off)',
                'no-referrer' => 'no-referrer',
                'strict-origin-when-cross-origin' => 'strict-origin-when-cross-origin',
                'strict-origin' => 'strict-origin',
                'same-origin' => 'same-origin',
            ] as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($headers['referrer_policy'] ?? ''), $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="cno-field">
        <span>X-Frame-Options</span>
        <select data-field="x_frame_options">
            <?php foreach (['' => '(off)', 'SAMEORIGIN' => 'SAMEORIGIN', 'DENY' => 'DENY'] as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($headers['x_frame_options'] ?? ''), $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="cno-field">
        <span>Permissions-Policy</span>
        <input type="text" data-field="permissions_policy" value="<?php echo esc_attr((string) ($headers['permissions_policy'] ?? '')); ?>">
    </label>

    <label class="cno-field">
        <span>Cross-Origin-Opener-Policy</span>
        <select data-field="coop">
            <?php foreach ([
                '' => '(off)',
                'unsafe-none' => 'unsafe-none',
                'same-origin-allow-popups' => 'same-origin-allow-popups',
                'same-origin' => 'same-origin',
            ] as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($headers['coop'] ?? ''), $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="cno-field">
        <span>Cross-Origin-Resource-Policy</span>
        <select data-field="corp">
            <?php foreach ([
                '' => '(off)',
                'same-site' => 'same-site',
                'same-origin' => 'same-origin',
                'cross-origin' => 'cross-origin',
            ] as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($headers['corp'] ?? ''), $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="cno-field">
        <span>Cross-Origin-Embedder-Policy</span>
        <select data-field="coep">
            <?php foreach ([
                '' => '(off)',
                'unsafe-none' => 'unsafe-none',
                'require-corp' => 'require-corp',
                'credentialless' => 'credentialless',
            ] as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected((string) ($headers['coep'] ?? ''), $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <em>Only enable if every embedded resource (images, iframes) opts in — this can break embeds.</em>
    </label>
</div>
