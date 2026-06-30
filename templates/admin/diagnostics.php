<?php
/**
 * @var \Computernoerden\Security\Modules\Diagnostics\DiagnosticsModule|null $diagnostics
 * @var \Computernoerden\Security\Modules\Reports\ReportsModule|null $reports
 * @var \Computernoerden\Security\Settings\EditionManager $edition
 */
defined('ABSPATH') || exit;
?>
<div class="wrap cno-security">
    <h1>Diagnostics &amp; Reports</h1>

    <?php if ($reports) : ?>
        <div class="cno-card cno-card-wide">
            <div class="cno-card-header">
                <h2>Reports</h2>
            </div>
            <p>Export a point-in-time summary of every module's status and score.</p>
            <div class="cno-inline-form">
                <button type="button" class="button" data-export-report="csv">Export CSV</button>
                <button type="button" class="button" data-export-report="json">Export JSON</button>
                <button type="button" class="button" disabled title="Pro feature">
                    PDF client report <?php echo $edition->isPro() ? '' : '(Pro)'; ?>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($diagnostics) : ?>
        <div class="cno-card cno-card-wide">
            <div class="cno-card-header">
                <h2>Environment</h2>
            </div>
            <table class="cno-diagnostics-table">
                <tbody>
                    <?php foreach ($diagnostics->items() as $item) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($item['label']); ?></th>
                            <td><?php echo esc_html($item['value']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div id="cno-toast" class="cno-toast" aria-live="polite"></div>
</div>
