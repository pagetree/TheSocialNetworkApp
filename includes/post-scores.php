<?php

declare(strict_types=1);

/** @var array<string, float> */
const POST_SCORE_WEIGHTS = [
    'like' => 5.0,
    'reply' => 3.0,
    'repost' => 4.0,
    'bookmark' => 2.0,
    'profile_click' => 1.0,
    'dwell' => 0.15,
];

const POST_SCORE_LOOKBACK_DAYS = 14;

function recomputePostScores(): int
{
    $pdo = createPdoConnection();

    $weightCases = [];
    foreach (POST_SCORE_WEIGHTS as $type => $weight) {
        if ($type === 'dwell') {
            $weightCases[] = "WHEN pi.type = 'dwell' THEN pi.value * {$weight}";
            continue;
        }
        $weightCases[] = "WHEN pi.type = '{$type}' THEN {$weight} * pi.value";
    }
    $weightSql = 'CASE ' . implode(' ', $weightCases) . ' ELSE 0 END';
    $lookbackDays = POST_SCORE_LOOKBACK_DAYS;

    $sql = "
INSERT INTO post_scores (post_id, score, last_computed_at)
SELECT
    p.id AS post_id,
    COALESCE(SUM({$weightSql}), 0) AS score,
    NOW() AS last_computed_at
FROM posts p
LEFT JOIN post_interactions pi ON pi.post_id = p.id
    AND pi.created_at >= NOW() - INTERVAL '{$lookbackDays} days'
WHERE p.is_deleted = FALSE
  AND p.repost_of_post_id IS NULL
GROUP BY p.id
ON CONFLICT (post_id) DO UPDATE
SET score = EXCLUDED.score,
    last_computed_at = EXCLUDED.last_computed_at
";

    $updated = $pdo->exec($sql);

    return $updated === false ? 0 : (int) $updated;
}
