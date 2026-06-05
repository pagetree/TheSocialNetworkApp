<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var array{ok: true, period: string, total: int, labels: list<string>, values: list<int>} $analyticsInitialData */
/** @var array{ok: true, period: string, stats: list<array{key: string, label: string, value: int|float|null, display: string, trend: string, trend_display: string, placeholder: bool}>} $analyticsInitialStats */

$analyticsStatCards = $analyticsInitialStats['stats'] ?? [];
?>
                    <div class="analytics-page">
                        <section class="analytics-chart-section" aria-label="<?php echo __e('analytics.impressions_chart_aria'); ?>">
                            <div
                                id="analytics-impressions-chart"
                                class="analytics-chart"
                                role="img"
                                aria-label="<?php echo __e('analytics.impressions_chart_aria'); ?>"
                            ></div>
                            <p class="analytics-chart-loading" data-analytics-loading hidden><?php echo __e('analytics.loading'); ?></p>
                        </section>

                        <section class="analytics-stats-section" aria-label="<?php echo __e('analytics.stats_section_aria'); ?>">
                            <div class="analytics-stats-grid">
<?php foreach ($analyticsStatCards as $analyticsStatCard) :
    $statKey = (string) ($analyticsStatCard['key'] ?? '');
    $statLabel = (string) ($analyticsStatCard['label'] ?? '');
    $statDisplay = (string) ($analyticsStatCard['display'] ?? '—');
    $statTrend = (string) ($analyticsStatCard['trend'] ?? 'neutral');
    $statTrendDisplay = (string) ($analyticsStatCard['trend_display'] ?? '—');
    $isPlaceholder = !empty($analyticsStatCard['placeholder']);
    $statCardClass = 'analytics-stat-card' . ($isPlaceholder ? ' is-placeholder' : '');
    $trendClass = 'analytics-stat-card-trend analytics-stat-card-trend--' . $statTrend;
    $trendIcon = analyticsTrendIcon($statTrend);
    $trendAriaLabel = $isPlaceholder
        ? __('analytics.stats.trend_unavailable')
        : __('analytics.stats.trend_change', ['change' => $statTrendDisplay]);
    ?>
                                <article
                                    class="<?php echo htmlspecialchars($statCardClass, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-analytics-stat="<?php echo htmlspecialchars($statKey, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <h3 class="analytics-stat-card-label"><?php echo htmlspecialchars($statLabel, ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p class="analytics-stat-card-value" data-analytics-stat-value><?php echo htmlspecialchars($statDisplay, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <div
                                        class="<?php echo htmlspecialchars($trendClass, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-analytics-stat-trend
                                        aria-label="<?php echo htmlspecialchars($trendAriaLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <i data-lucide="<?php echo htmlspecialchars($trendIcon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                        <span class="analytics-stat-card-trend-value" data-analytics-stat-trend-value><?php echo htmlspecialchars($statTrendDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </article>
<?php endforeach; ?>
                            </div>
                        </section>
                    </div>
