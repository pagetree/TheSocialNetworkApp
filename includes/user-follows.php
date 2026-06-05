<?php

declare(strict_types=1);

const SIDEBAR_WHO_TO_FOLLOW_LIMIT = 4;

/**
 * @return array{following: int, followers: int}
 */
function fetchUserFollowCounts(int $userId): array
{
    if ($userId < 1) {
        return ['following' => 0, 'followers' => 0];
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*)::int FROM user_follows WHERE follower_id = :user_id) AS following,
            (SELECT COUNT(*)::int FROM user_follows WHERE following_id = :user_id) AS followers'
    );
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch();

    if ($row === false) {
        return ['following' => 0, 'followers' => 0];
    }

    return [
        'following' => (int) ($row['following'] ?? 0),
        'followers' => (int) ($row['followers'] ?? 0),
    ];
}

function isUserFollowedBy(int $followerId, int $followingId): bool
{
    if ($followerId < 1 || $followingId < 1 || $followerId === $followingId) {
        return false;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT 1
         FROM user_follows
         WHERE follower_id = :follower_id
           AND following_id = :following_id
         LIMIT 1'
    );
    $stmt->execute([
        'follower_id' => $followerId,
        'following_id' => $followingId,
    ]);

    return $stmt->fetch() !== false;
}

/**
 * @return array{ok: true, following: bool}|array{ok: false, error: string}
 */
function toggleUserFollow(int $followerId, int $followingId): array
{
    if ($followerId < 1 || $followingId < 1) {
        return ['ok' => false, 'error' => 'Invalid user.'];
    }

    if ($followerId === $followingId) {
        return ['ok' => false, 'error' => 'You cannot follow yourself.'];
    }

    $pdo = createPdoConnection();
    $targetStmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
    $targetStmt->execute(['id' => $followingId]);
    if ($targetStmt->fetch() === false) {
        return ['ok' => false, 'error' => 'User not found.'];
    }

    $existing = $pdo->prepare(
        'SELECT 1
         FROM user_follows
         WHERE follower_id = :follower_id
           AND following_id = :following_id
         LIMIT 1'
    );
    $existing->execute([
        'follower_id' => $followerId,
        'following_id' => $followingId,
    ]);
    $isFollowing = $existing->fetch() !== false;

    if ($isFollowing) {
        $delete = $pdo->prepare(
            'DELETE FROM user_follows
             WHERE follower_id = :follower_id
               AND following_id = :following_id'
        );
        $delete->execute([
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ]);

        return ['ok' => true, 'following' => false];
    }

    $insert = $pdo->prepare(
        'INSERT INTO user_follows (follower_id, following_id)
         VALUES (:follower_id, :following_id)'
    );
    $insert->execute([
        'follower_id' => $followerId,
        'following_id' => $followingId,
    ]);

    notifyUserFollow($followingId, $followerId);

    return ['ok' => true, 'following' => true];
}

/**
 * @param list<int> $targetUserIds
 * @return array<int, true>
 */
function fetchFollowedUserIdsAmong(int $followerId, array $targetUserIds): array
{
    $targetUserIds = array_values(array_unique(array_filter(
        array_map('intval', $targetUserIds),
        static fn (int $id): bool => $id > 0
    )));

    if ($followerId < 1 || $targetUserIds === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($targetUserIds), '?'));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT following_id
         FROM user_follows
         WHERE follower_id = ?
           AND following_id IN (' . $placeholders . ')'
    );
    $stmt->execute(array_merge([$followerId], $targetUserIds));

    $followed = [];
    while ($row = $stmt->fetch()) {
        $followed[(int) $row['following_id']] = true;
    }

    return $followed;
}

/**
 * @return list<array<string, mixed>>
 */
function fetchPublicWhoToFollowSuggestions(int $limit = SIDEBAR_WHO_TO_FOLLOW_LIMIT): array
{
    $limit = max(1, min($limit, SIDEBAR_WHO_TO_FOLLOW_LIMIT));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT
            u.id,
            u.username,
            u.display_name,
            u.handle,
            u.avatar_url,
            COALESCE(followers.follower_count, 0) AS follower_count
         FROM users u
         LEFT JOIN LATERAL (
             SELECT COUNT(*)::int AS follower_count
             FROM user_follows uf
             WHERE uf.following_id = u.id
         ) followers ON TRUE
         WHERE u.is_visible = TRUE
           AND u.onboarding_completed_at IS NOT NULL
         ORDER BY followers.follower_count DESC, u.created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $suggestions = [];
    while ($row = $stmt->fetch()) {
        $suggestions[] = $row;
    }

    return $suggestions;
}

/**
 * @return list<array<string, mixed>>
 */
function fetchWhoToFollowSuggestions(int $userId, int $limit = SIDEBAR_WHO_TO_FOLLOW_LIMIT): array
{
    if ($userId < 1) {
        return fetchPublicWhoToFollowSuggestions($limit);
    }

    $limit = max(1, min($limit, SIDEBAR_WHO_TO_FOLLOW_LIMIT));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT
            u.id,
            u.username,
            u.display_name,
            u.handle,
            u.avatar_url,
            COALESCE(shared.shared_count, 0) AS shared_interests,
            COALESCE(followers.follower_count, 0) AS follower_count
         FROM users u
         LEFT JOIN LATERAL (
             SELECT COUNT(*)::int AS shared_count
             FROM user_interests ui_viewer
             INNER JOIN user_interests ui_target
                 ON ui_target.interest_id = ui_viewer.interest_id
                AND ui_target.user_id = u.id
             WHERE ui_viewer.user_id = :viewer_id
         ) shared ON TRUE
         LEFT JOIN LATERAL (
             SELECT COUNT(*)::int AS follower_count
             FROM user_follows uf
             WHERE uf.following_id = u.id
         ) followers ON TRUE
         WHERE u.id <> :viewer_id
           AND u.is_visible = TRUE
           AND u.onboarding_completed_at IS NOT NULL
           AND NOT EXISTS (
               SELECT 1
               FROM user_follows uf_self
               WHERE uf_self.follower_id = :viewer_id
                 AND uf_self.following_id = u.id
           )
         ORDER BY shared.shared_count DESC, followers.follower_count DESC, u.created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue('viewer_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $suggestions = [];
    while ($row = $stmt->fetch()) {
        $suggestions[] = $row;
    }

    return $suggestions;
}
