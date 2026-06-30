<?php defined('ABSPATH') || exit; ?>
<div class="wrap cno-security">
    <h1>Modules</h1>
    <p>Enable or disable modules instantly. No Save button.</p>

    <div class="cno-module-list">
        <?php foreach ($modules as $module) : ?>
            <div class="cno-module-card" data-module="<?php echo esc_attr($module->id()); ?>">
                <div>
                    <h2><?php echo esc_html($module->name()); ?></h2>
                    <p><?php echo esc_html($module->description()); ?></p>
                    <span class="cno-status"><?php echo esc_html($module->status()); ?></span>
                </div>

                <label class="cno-toggle">
                    <input type="checkbox" class="cno-module-toggle" data-module="<?php echo esc_attr($module->id()); ?>" <?php checked($module->enabledByDefault()); ?>>
                    <span></span>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="cno-toast" class="cno-toast" aria-live="polite"></div>
</div>
