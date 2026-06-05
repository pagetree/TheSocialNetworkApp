<?php

declare(strict_types=1);

/**
 * @return '24h'|'7d'|'30d'|'1y'|null
 */
function normalizeAnalyticsPeriod(string $period): ?string
{
    $period = strtolower(trim($period));

    return in_array($period, ['24h', '7d', '30d', '1y'], true) ? $period : null;
}

/**
 * @return array{trunc_unit: string, bucket_count: int, since_modifier: string, label_pattern: string}|null
 */
function analyticsPeriodConfig(string $period): ?array
{
    return match (normalizeAnalyticsPeriod($period)) {
        '24h' => [
            'trunc_unit' => 'hour',
            'bucket_count' => 24,
            'since_modifier' => '-24 hours',
            'label_pattern' => 'hour',
        ],
        '7d' => [
            'trunc_unit' => 'day',
            'bucket_count' => 7,
            'since_modifier' => '-7 days',
            'label_pattern' => 'day_short',
        ],
        '30d' => [
            'trunc_unit' => 'day',
            'bucket_count' => 30,
            'since_modifier' => '-30 days',
            'label_pattern' => 'day_short',
        ],
        '1y' => [
            'trunc_unit' => 'month',
            'bucket_count' => 12,
            'since_modifier' => '-1 year',
            'label_pattern' => 'month',
        ],
        default => null,
    };
}

function analyticsBucketLabel(DateTimeImmutable $bucketStart, string $labelPattern): string
{
    return match ($labelPattern) {
        'hour' => $bucketStart->format('H:i'),
        'day_short' => $bucketStart->format('M j'),
        'week' => $bucketStart->format('M j'),
        'month' => $bucketStart->format('M Y'),
        default => $bucketStart->format('M j'),
    };
}

function analyticsBucketKey(DateTimeImmutable $bucketStart, string $truncUnit): string
{
    return match ($truncUnit) {
        'hour' => $bucketStart->format('Y-m-d H:00:00'),
        'day' => $bucketStart->format('Y-m-d 00:00:00'),
        'week', 'month' => $bucketStart->format('Y-m-d 00:00:00'),
        default => $bucketStart->format('Y-m-d H:i:s'),
    };
}

/**
 * @return list<DateTimeImmutable>
 */
function analyticsBucketStarts(DateTimeImmutable $now, string $truncUnit, int $bucketCount): array
{
    $endBucket = match ($truncUnit) {
        'hour' => $now->setTime((int) $now->format('H'), 0, 0),
        'day' => $now->setTime(0, 0, 0),
        'week' => $now->modify('monday this week')->setTime(0, 0, 0),
        'month' => $now->modify('first day of this month')->setTime(0, 0, 0),
        default => $now,
    };

    $starts = [];
    for ($index = $bucketCount - 1; $index >= 0; $index--) {
        $starts[] = match ($truncUnit) {
            'hour' => $endBucket->sub(new DateInterval('PT' . $index . 'H')),
            'day' => $endBucket->sub(new DateInterval('P' . $index . 'D')),
            'week' => $endBucket->sub(new DateInterval('P' . ($index * 7) . 'D')),
            'month' => $endBucket->sub(new DateInterval('P' . $index . 'M')),
            default => $endBucket,
        };
    }

    return $starts;
}

function analyticsPeriodSince(string $period): ?DateTimeImmutable
{
    $config = analyticsPeriodConfig($period);
    if ($config === null) {
        return null;
    }

    return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
        ->modify($config['since_modifier']);
}

/**
 * @return array{current_since: DateTimeImmutable, previous_since: DateTimeImmutable, current_until: DateTimeImmutable}|null
 */
function analyticsPeriodBounds(string $period): ?array
{
    $normalizedPeriod = normalizeAnalyticsPeriod($period);
    if ($normalizedPeriod === null) {
        return null;
    }

    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $currentSince = analyticsPeriodSince($normalizedPeriod);
    if ($currentSince === null) {
        return null;
    }

    $previousSince = match ($normalizedPeriod) {
        '24h' => $now->modify('-48 hours'),
        '7d' => $now->modify('-14 days'),
        '30d' => $now->modify('-60 days'),
        '1y' => $now->modify('-2 years'),
        default => null,
    };

    if ($previousSince === null) {
        return null;
    }

    return [
        'current_since' => $currentSince,
        'previous_since' => $previousSince,
        'current_until' => $now,
    ];
}

/**
 * @return 'up'|'down'|'neutral'
 */
function analyticsTrendDirection(int|float $current, int|float $previous): string
{
    if ($current > $previous) {
        return 'up';
    }

    if ($current < $previous) {
        return 'down';
    }

    return 'neutral';
}

/**
 * @return 'trending-up'|'trending-down'|'minus'
 */
function analyticsTrendIcon(string $trend): string
{
    return match ($trend) {
        'up' => 'trending-up',
        'down' => 'trending-down',
        default => 'minus',
    };
}

/**
 * @return array{
 *     impressions: int,
 *     likes: int,
 *     replies: int,
 *     reposts: int,
 *     new_followers: int,
 *     interactions: int,
 *     profile_views: int,
 *     link_clicks: int,
 *     engagement_rate: float
 * }
 */
function fetchUserAnalyticsMetricCounts(
    int $userId,
    DateTimeImmutable $since,
    ?DateTimeImmutable $until = null,
): array {
    $pdo = createPdoConnection();
    $pdo->exec("SET TIME ZONE 'UTC'");

    $untilSql = $until !== null ? ' AND metric_created_at < :until' : '';
    $params = [
        'user_id' => $userId,
        'since' => $since->format('Y-m-d H:i:sP'),
    ];

    if ($until !== null) {
        $params['until'] = $until->format('Y-m-d H:i:sP');
    }

    $stmt = $pdo->prepare(
        'SELECT
            (
                SELECT COUNT(*)::int
                FROM post_stat_events pse
                INNER JOIN posts p ON p.id = pse.post_id
                WHERE p.user_id = :user_id
                  AND p.is_deleted = FALSE
                  AND pse.event_type = \'view\'
                  AND pse.created_at >= :since' . str_replace('metric_created_at', 'pse.created_at', $untilSql) . '
            ) AS impressions,
            (
                SELECT COUNT(*)::int
                FROM post_likes pl
                INNER JOIN posts p ON p.id = pl.post_id
                WHERE p.user_id = :user_id
                  AND p.is_deleted = FALSE
                  AND pl.created_at >= :since' . str_replace('metric_created_at', 'pl.created_at', $untilSql) . '
            ) AS likes,
            (
                SELECT COUNT(*)::int
                FROM post_replies pr
                INNER JOIN posts p ON p.id = pr.conversation_id
                WHERE p.user_id = :user_id
                  AND p.is_deleted = FALSE
                  AND pr.is_deleted = FALSE
                  AND pr.created_at >= :since' . str_replace('metric_created_at', 'pr.created_at', $untilSql) . '
            ) AS replies,
            (
                SELECT COUNT(*)::int
                FROM posts rp
                INNER JOIN posts p ON p.id = rp.repost_of_post_id
                WHERE p.user_id = :user_id
                  AND p.is_deleted = FALSE
                  AND rp.is_deleted = FALSE
                  AND rp.created_at >= :since' . str_replace('metric_created_at', 'rp.created_at', $untilSql) . '
            ) AS reposts,
            (
                SELECT COUNT(*)::int
                FROM user_follows uf
                WHERE uf.following_id = :user_id
                  AND uf.created_at >= :since' . str_replace('metric_created_at', 'uf.created_at', $untilSql) . '
            ) AS new_followers,
            (
                SELECT COUNT(*)::int
                FROM post_stat_events pse
                INNER JOIN posts p ON p.id = pse.post_id
                WHERE p.user_id = :user_id
                  AND p.is_deleted = FALSE
                  AND pse.event_type = \'interaction\'
                  AND pse.created_at >= :since' . str_replace('metric_created_at', 'pse.created_at', $untilSql) . '
            ) AS interactions,
            (
                SELECT COUNT(*)::int
                FROM profile_stat_events pse
                WHERE pse.profile_user_id = :user_id
                  AND pse.event_type = \'view\'
                  AND pse.created_at >= :since' . str_replace('metric_created_at', 'pse.created_at', $untilSql) . '
            ) AS profile_views,
            (
                SELECT COUNT(*)::int
                FROM link_click_events lce
                WHERE lce.owner_user_id = :user_id
                  AND lce.created_at >= :since' . str_replace('metric_created_at', 'lce.created_at', $untilSql) . '
            ) AS link_clicks'
    );
    $stmt->execute($params);
    $row = $stmt->fetch();

    $impressions = (int) ($row['impressions'] ?? 0);
    $likes = (int) ($row['likes'] ?? 0);
    $replies = (int) ($row['replies'] ?? 0);
    $reposts = (int) ($row['reposts'] ?? 0);
    $newFollowers = (int) ($row['new_followers'] ?? 0);
    $interactions = (int) ($row['interactions'] ?? 0);
    $profileViews = (int) ($row['profile_views'] ?? 0);
    $linkClicks = (int) ($row['link_clicks'] ?? 0);
    $engagements = $likes + $replies + $reposts + $interactions;
    $engagementRate = $impressions > 0
        ? round(($engagements / $impressions) * 100, 1)
        : 0.0;

    return [
        'impressions' => $impressions,
        'likes' => $likes,
        'replies' => $replies,
        'reposts' => $reposts,
        'new_followers' => $newFollowers,
        'interactions' => $interactions,
        'profile_views' => $profileViews,
        'link_clicks' => $linkClicks,
        'engagement_rate' => $engagementRate,
    ];
}

/**
 * @return array{trend: string, trend_display: string, trend_delta: float}
 */
function analyticsFormatTrendDelta(int|float $current, int|float $previous, bool $isPercent = false): array
{
    $delta = (float) $current - (float) $previous;
    $trend = analyticsTrendDirection($current, $previous);

    if ($isPercent) {
        if (abs($delta) < 0.05) {
            return [
                'trend' => 'neutral',
                'trend_display' => '0%',
                'trend_delta' => 0.0,
            ];
        }

        $sign = $delta > 0 ? '+' : '';

        return [
            'trend' => $trend,
            'trend_display' => $sign . rtrim(rtrim(number_format($delta, 1, '.', ''), '0'), '.') . '%',
            'trend_delta' => $delta,
        ];
    }

    $intDelta = (int) round($delta);
    if ($intDelta === 0) {
        return [
            'trend' => 'neutral',
            'trend_display' => '0',
            'trend_delta' => 0.0,
        ];
    }

    return [
        'trend' => $trend,
        'trend_display' => $intDelta > 0 ? '+' . number_format($intDelta) : number_format($intDelta),
        'trend_delta' => (float) $intDelta,
    ];
}

/**
 * @return array{key: string, label: string, value: int|float|null, display: string, trend: string, trend_display: string, trend_delta: float|null, placeholder: bool}
 */
function analyticsStatCard(
    string $key,
    string $label,
    int|float|null $value,
    int|float $previousValue = 0,
    bool $placeholder = false,
    bool $isPercent = false,
): array {
    if ($placeholder || $value === null) {
        return [
            'key' => $key,
            'label' => $label,
            'value' => null,
            'display' => '—',
            'trend' => 'neutral',
            'trend_display' => '—',
            'trend_delta' => null,
            'placeholder' => true,
        ];
    }

    $trendMeta = analyticsFormatTrendDelta($value, $previousValue, $isPercent);

    if ($isPercent) {
        $percent = max(0, (float) $value);

        return [
            'key' => $key,
            'label' => $label,
            'value' => $percent,
            'display' => rtrim(rtrim(number_format($percent, 1, '.', ''), '0'), '.') . '%',
            'trend' => $trendMeta['trend'],
            'trend_display' => $trendMeta['trend_display'],
            'trend_delta' => $trendMeta['trend_delta'],
            'placeholder' => false,
        ];
    }

    $intValue = max(0, (int) $value);

    return [
        'key' => $key,
        'label' => $label,
        'value' => $intValue,
        'display' => formatEngagementCount($intValue),
        'trend' => $trendMeta['trend'],
        'trend_display' => $trendMeta['trend_display'],
        'trend_delta' => $trendMeta['trend_delta'],
        'placeholder' => false,
    ];
}

/**
 * @return array{ok: true, period: string, stats: list<array{key: string, label: string, value: int|float|null, display: string, trend: string, trend_display: string, trend_delta: float|null, placeholder: bool}>}|array{ok: false, error: string}
 */
function fetchUserAnalyticsStats(int $userId, string $period): array
{
    $normalizedPeriod = normalizeAnalyticsPeriod($period);
    if ($normalizedPeriod === null || $userId < 1) {
        return ['ok' => false, 'error' => 'Invalid request.'];
    }

    $bounds = analyticsPeriodBounds($normalizedPeriod);
    if ($bounds === null) {
        return ['ok' => false, 'error' => 'Invalid period.'];
    }

    $current = fetchUserAnalyticsMetricCounts($userId, $bounds['current_since']);
    $previous = fetchUserAnalyticsMetricCounts(
        $userId,
        $bounds['previous_since'],
        $bounds['current_since']
    );

    return [
        'ok' => true,
        'period' => $normalizedPeriod,
        'stats' => [
            analyticsStatCard('impressions', __('analytics.stats.impressions'), $current['impressions'], $previous['impressions']),
            analyticsStatCard('engagement_rate', __('analytics.stats.engagement_rate'), $current['engagement_rate'], $previous['engagement_rate'], false, true),
            analyticsStatCard('profile_views', __('analytics.stats.profile_views'), $current['profile_views'], $previous['profile_views']),
            analyticsStatCard('link_clicks', __('analytics.stats.link_clicks'), $current['link_clicks'], $previous['link_clicks']),
            analyticsStatCard('new_followers', __('analytics.stats.new_followers'), $current['new_followers'], $previous['new_followers']),
            analyticsStatCard('replies', __('analytics.stats.replies'), $current['replies'], $previous['replies']),
            analyticsStatCard('likes', __('analytics.stats.likes'), $current['likes'], $previous['likes']),
            analyticsStatCard('reposts', __('analytics.stats.reposts'), $current['reposts'], $previous['reposts']),
        ],
    ];
}

/**
 * @return array{ok: true, period: string, total: int, labels: list<string>, values: list<int>}|array{ok: false, error: string}
 */
function fetchUserPostImpressionsSeries(int $userId, string $period): array
{
    $normalizedPeriod = normalizeAnalyticsPeriod($period);
    if ($normalizedPeriod === null || $userId < 1) {
        return ['ok' => false, 'error' => 'Invalid request.'];
    }

    $config = analyticsPeriodConfig($normalizedPeriod);
    if ($config === null) {
        return ['ok' => false, 'error' => 'Invalid period.'];
    }

    $truncUnit = $config['trunc_unit'];
    if (!in_array($truncUnit, ['hour', 'day', 'week', 'month'], true)) {
        return ['ok' => false, 'error' => 'Invalid period.'];
    }

    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $since = $now->modify($config['since_modifier']);
    $bucketStarts = analyticsBucketStarts($now, $truncUnit, $config['bucket_count']);

    $pdo = createPdoConnection();
    $pdo->exec("SET TIME ZONE 'UTC'");

    $sql = "
        SELECT date_trunc('{$truncUnit}', pse.created_at) AS bucket_start,
               COUNT(*)::int AS impression_count
        FROM post_stat_events pse
        INNER JOIN posts p ON p.id = pse.post_id
        WHERE p.user_id = :user_id
          AND p.is_deleted = FALSE
          AND pse.event_type = 'view'
          AND pse.created_at >= :since
        GROUP BY bucket_start
        ORDER BY bucket_start ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id' => $userId,
        'since' => $since->format('Y-m-d H:i:sP'),
    ]);

    $countsByBucket = [];
    while ($row = $stmt->fetch()) {
        $bucketStart = new DateTimeImmutable((string) $row['bucket_start'], new DateTimeZone('UTC'));
        $countsByBucket[analyticsBucketKey($bucketStart, $truncUnit)] = (int) $row['impression_count'];
    }

    $labels = [];
    $values = [];
    $total = 0;

    foreach ($bucketStarts as $bucketStart) {
        $bucketKey = analyticsBucketKey($bucketStart, $truncUnit);
        $count = $countsByBucket[$bucketKey] ?? 0;
        $labels[] = analyticsBucketLabel($bucketStart, $config['label_pattern']);
        $values[] = $count;
        $total += $count;
    }

    return [
        'ok' => true,
        'period' => $normalizedPeriod,
        'total' => $total,
        'labels' => $labels,
        'values' => $values,
    ];
}
