<?php

declare(strict_types=1);

/**
 * @return 'view'|'interaction'|null
 */
function normalizePostStatEventType(string $eventType): ?string
{
    $eventType = strtolower(trim($eventType));

    return in_array($eventType, ['view', 'interaction'], true) ? $eventType : null;
}

/**
 * @return array{ok: true, view_count: int, interaction_count: int, recorded: bool}|array{ok: false, error: string}
 */
function recordPostStat(int $postId, int $viewerUserId, string $eventType): array
{
    $eventType = normalizePostStatEventType($eventType);
    if ($eventType === null) {
        return ['ok' => false, 'error' => 'Invalid stat event.'];
    }

    if ($postId < 1 || $viewerUserId < 1) {
        return ['ok' => false, 'error' => 'Invalid post.'];
    }

    $pdo = createPdoConnection();
    $postStmt = $pdo->prepare(
        'SELECT id, user_id, view_count, interaction_count
         FROM posts
         WHERE id = :id
           AND is_deleted = FALSE
         LIMIT 1'
    );
    $postStmt->execute(['id' => $postId]);
    $post = $postStmt->fetch();

    if ($post === false) {
        return ['ok' => false, 'error' => 'Post not found.'];
    }

    if ((int) $post['user_id'] === $viewerUserId) {
        return [
            'ok' => true,
            'view_count' => (int) $post['view_count'],
            'interaction_count' => (int) ($post['interaction_count'] ?? 0),
            'recorded' => false,
        ];
    }

    $pdo->beginTransaction();

    try {
        $insert = $pdo->prepare(
            'INSERT INTO post_stat_events (post_id, user_id, event_type)
             VALUES (:post_id, :user_id, :event_type)
             ON CONFLICT (post_id, user_id, event_type) DO NOTHING
             RETURNING post_id'
        );
        $insert->execute([
            'post_id' => $postId,
            'user_id' => $viewerUserId,
            'event_type' => $eventType,
        ]);
        $inserted = $insert->fetch() !== false;

        if ($inserted) {
            $counterColumn = $eventType === 'view' ? 'view_count' : 'interaction_count';
            $update = $pdo->prepare(
                'UPDATE posts
                 SET ' . $counterColumn . ' = ' . $counterColumn . ' + 1,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $update->execute(['id' => $postId]);
        }

        $fresh = $pdo->prepare(
            'SELECT view_count, interaction_count
             FROM posts
             WHERE id = :id
             LIMIT 1'
        );
        $fresh->execute(['id' => $postId]);
        $counts = $fresh->fetch();

        $pdo->commit();

        if ($inserted) {
            syncPostInteractionFromStatEvent($postId, $viewerUserId, $eventType);
        }

        return [
            'ok' => true,
            'view_count' => (int) ($counts['view_count'] ?? $post['view_count']),
            'interaction_count' => (int) ($counts['interaction_count'] ?? 0),
            'recorded' => $inserted,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}

function formatPostStatCount(int $count): string
{
    return formatEngagementCount($count);
}

/**
 * @return array{key: string, label: string, value: string, icon: string}
 */
function postStatMetric(string $key, string $label, int $count, string $icon): array
{
    return [
        'key' => $key,
        'label' => $label,
        'value' => formatPostStatCount($count),
        'icon' => $icon,
    ];
}

/**
 * @return array{ok: true, kind: string, title: string, is_owner: bool, metrics: list<array{key: string, label: string, value: string, icon: string}>}|array{ok: false, error: string}
 */
function fetchPostStatsDetail(int $postId, int $viewerUserId): array
{
    if ($postId < 1 || $viewerUserId < 1) {
        return ['ok' => false, 'error' => 'Invalid post.'];
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT p.id, p.user_id, p.reply_count, p.repost_count, p.like_count, p.view_count, p.interaction_count,
                ps.score
         FROM posts p
         LEFT JOIN post_scores ps ON ps.post_id = p.id
         WHERE p.id = :id
           AND p.is_deleted = FALSE
         LIMIT 1'
    );
    $stmt->execute(['id' => $postId]);
    $post = $stmt->fetch();

    if ($post === false) {
        return ['ok' => false, 'error' => 'Post not found.'];
    }

    $isOwner = (int) $post['user_id'] === $viewerUserId;
    $metrics = [
        postStatMetric('views', 'Views', (int) $post['view_count'], 'eye'),
        postStatMetric('likes', 'Likes', (int) $post['like_count'], 'heart'),
        postStatMetric('replies', 'Replies', (int) $post['reply_count'], 'message-circle'),
        postStatMetric('reposts', 'Reposts', (int) $post['repost_count'], 'repeat-2'),
        postStatMetric('interactions', 'Profile clicks', (int) ($post['interaction_count'] ?? 0), 'mouse-pointer-click'),
    ];

    if ($isOwner) {
        $score = $post['score'] ?? null;
        if ($score !== null && $score !== '') {
            $metrics[] = [
                'key' => 'score',
                'label' => 'Engagement score',
                'value' => rtrim(rtrim(number_format((float) $score, 2, '.', ''), '0'), '.'),
                'icon' => 'trending-up',
            ];
        }
    }

    return [
        'ok' => true,
        'kind' => 'post',
        'title' => 'Post stats',
        'is_owner' => $isOwner,
        'metrics' => $metrics,
    ];
}

/**
 * @return array{ok: true, kind: string, title: string, is_owner: bool, metrics: list<array{key: string, label: string, value: string, icon: string}>}|array{ok: false, error: string}
 */
function fetchReplyStatsDetail(int $replyId, int $viewerUserId): array
{
    if ($replyId < 1 || $viewerUserId < 1) {
        return ['ok' => false, 'error' => 'Invalid reply.'];
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT id, user_id, like_count, reply_count
         FROM post_replies
         WHERE id = :id
           AND is_deleted = FALSE
         LIMIT 1'
    );
    $stmt->execute(['id' => $replyId]);
    $reply = $stmt->fetch();

    if ($reply === false) {
        return ['ok' => false, 'error' => 'Reply not found.'];
    }

    $isOwner = (int) $reply['user_id'] === $viewerUserId;

    return [
        'ok' => true,
        'kind' => 'reply',
        'title' => 'Reply stats',
        'is_owner' => $isOwner,
        'metrics' => [
            postStatMetric('likes', 'Likes', (int) $reply['like_count'], 'heart'),
            postStatMetric('replies', 'Replies', (int) $reply['reply_count'], 'message-circle'),
        ],
    ];
}

function syncPostInteractionFromStatEvent(int $postId, int $userId, string $eventType): void
{
    $interactionType = $eventType === 'view' ? 'dwell' : 'profile_click';

    try {
        $pdo = createPdoConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO post_interactions (user_id, post_id, type, value)
             SELECT :user_id, :post_id, :type, 1
             WHERE NOT EXISTS (
                 SELECT 1
                 FROM post_interactions pi
                 WHERE pi.user_id = :user_id_check
                   AND pi.post_id = :post_id_check
                   AND pi.type = :type_check
             )'
        );
        $stmt->execute([
            'user_id' => $userId,
            'post_id' => $postId,
            'type' => $interactionType,
            'user_id_check' => $userId,
            'post_id_check' => $postId,
            'type_check' => $interactionType,
        ]);
    } catch (Throwable) {
        // post_interactions may not exist until migration 010 is applied.
    }
}
