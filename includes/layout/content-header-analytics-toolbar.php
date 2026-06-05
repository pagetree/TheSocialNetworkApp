<?php

declare(strict_types=1);

/** @var string $analyticsDefaultPeriod */

$analyticsDefaultPeriod = normalizeAnalyticsPeriod($analyticsDefaultPeriod ?? '7d') ?? '7d';
$analyticsPeriods = ['24h', '7d', '30d', '1y'];
?>
                    <header class="app-shell-header app-content-header">
                        <h2 class="app-content-header-title"><?php echo __e('analytics.account_overview'); ?></h2>
                        <div class="app-content-header-actions">
                            <div
                                class="analytics-period-picker"
                                role="group"
                                aria-label="<?php echo __e('analytics.period_label'); ?>"
                            >
<?php foreach ($analyticsPeriods as $analyticsPeriod) :
    $isActivePeriod = $analyticsPeriod === $analyticsDefaultPeriod;
    $periodLabelKey = 'analytics.period_' . str_replace(['h', 'd', 'y'], ['_h', '_d', '_y'], $analyticsPeriod);
    ?>
                                <button
                                    type="button"
                                    class="analytics-period-btn<?php echo $isActivePeriod ? ' is-active' : ''; ?>"
                                    data-analytics-period="<?php echo htmlspecialchars($analyticsPeriod, ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-pressed="<?php echo $isActivePeriod ? 'true' : 'false'; ?>"
                                ><?php echo __e($periodLabelKey); ?></button>
<?php endforeach; ?>
                            </div>
                        </div>
                    </header>
