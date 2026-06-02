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
