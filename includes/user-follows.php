<?php

declare(strict_types=1);

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

    return ['ok' => true, 'following' => true];
}
