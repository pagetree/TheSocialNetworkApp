<?php

declare(strict_types=1);

const POST_PARTICIPANTS_LIMIT = 4;

/**
 * @param array<string, mixed> $user
 */
function userProfileIsVisible(array $user): bool
{
    if (!array_key_exists('is_visible', $user)) {
        return true;
    }

    $value = $user['is_visible'];

    return $value === true
        || $value === 1
        || $value === '1'
        || $value === 't'
        || $value === 'true';
}

/**
 * @return list<array<string, mixed>>
 */
function fetchVisiblePostParticipants(int $postId, int $limit = POST_PARTICIPANTS_LIMIT): array
{
    if ($postId < 1) {
        return [];
    }

    $limit = max(1, min($limit, 100));

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'WITH activity AS (
            SELECT p.user_id, p.created_at AS acted_at
            FROM posts p
            WHERE p.id = :post_id
              AND p.is_deleted = FALSE

            UNION ALL

            SELECT pl.user_id, pl.created_at
            FROM post_likes pl
            WHERE pl.post_id = :post_id

            UNION ALL

            SELECT pr.user_id, pr.created_at
            FROM post_replies pr
            WHERE pr.conversation_id = :post_id
              AND pr.is_deleted = FALSE

            UNION ALL

            SELECT p.user_id, p.created_at
            FROM posts p
            WHERE p.repost_of_post_id = :post_id
              AND p.is_deleted = FALSE

            UNION ALL

            SELECT pse.user_id, pse.created_at
            FROM post_stat_events pse
            WHERE pse.post_id = :post_id
        ),
        ranked AS (
            SELECT user_id, MAX(acted_at) AS last_active_at
            FROM activity
            WHERE user_id IS NOT NULL
            GROUP BY user_id
        )
        SELECT u.id, u.display_name, u.handle, u.username, u.avatar_url, u.bio, r.last_active_at
        FROM ranked r
        INNER JOIN users u ON u.id = r.user_id
        WHERE u.is_visible = TRUE
        ORDER BY r.last_active_at DESC
        LIMIT ' . $limit
    );
    $stmt->execute(['post_id' => $postId]);
    $rows = $stmt->fetchAll();

    return is_array($rows) ? $rows : [];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function postParticipantPayload(array $row, callable $url, bool $viewerFollows = false): array
{
    $user = [
        'id' => (int) ($row['id'] ?? 0),
        'display_name' => (string) ($row['display_name'] ?? ''),
        'handle' => (string) ($row['handle'] ?? ''),
        'username' => (string) ($row['username'] ?? ''),
        'avatar_url' => (string) ($row['avatar_url'] ?? ''),
    ];
    $bio = trim((string) ($row['bio'] ?? ''));

    return [
        'id' => $user['id'],
        'display_name' => $user['display_name'],
        'handle' => $user['handle'],
        'bio' => $bio,
        'avatar_url' => userMediaUrl($user, 'avatar_url', $url),
        'profile_url' => profileUrlForUser($user, $url),
        'viewer_follows' => $viewerFollows,
    ];
}
