<?php
/**
 * @var array<string, \Computernoerden\Security\Contracts\ModuleInterface> $modules
 * @var \Computernoerden\Security\Settings\SettingsRepository $settings
 * @var \Computernoerden\Security\Settings\EditionManager $edition
 */
defined('ABSPATH') || exit;
?>
<div class="wrap cno-security">
    <h1>Modules</h1>
    <p class="cno-lead">Enable or disable modules instantly — every change here, and inside each module's settings, is saved immediately. There is no Save button.</p>

    <div class="cno-module-list">
        <?php foreach ($modules as $module) : ?>
            <?php
            $enabled = $settings->moduleEnabled($module->id());
            $partial = CNO_SECURITY_PLUGIN_DIR . 'templates/admin/partials/module-' . $module->id() . '.php';
            ?>
            <div class="cno-module-card" id="<?php echo esc_attr($module->id()); ?>" data-module="<?php echo esc_attr($module->id()); ?>">
                <div class="cno-module-card-row">
                    <div class="cno-module-card-main">
                        <div class="cno-module-card-title">
                            <h2><?php echo esc_html($module->name()); ?></h2>
                            <span class="cno-status" data-status><?php echo esc_html($module->status()); ?></span>
                        </div>
                        <p><?php echo esc_html($module->description()); ?></p>
                    </div>

                    <div class="cno-module-card-actions">
                        <?php if ($module->hasSettings()) : ?>
                            <button type="button" class="button cno-configure-toggle" data-configure-toggle>Configure</button>
                        <?php endif; ?>
                        <label class="cno-toggle">
                            <input type="checkbox" class="cno-module-toggle" data-module="<?php echo esc_attr($module->id()); ?>" <?php checked($enabled); ?>>
                            <span></span>
                        </label>
                    </div>
                </div>

                <?php if ($module->hasSettings() && file_exists($partial)) : ?>
                    <div class="cno-module-settings" data-settings-panel hidden>
                        <?php require $partial; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="cno-toast" class="cno-toast" aria-live="polite"></div>
</div>
