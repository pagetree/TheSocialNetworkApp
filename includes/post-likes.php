<?php

declare(strict_types=1);

/**
 * @param list<int> $postIds
 * @return list<int>
 */
function fetchLikedPostIdsForUser(int $userId, array $postIds): array
{
    if ($userId < 1 || $postIds === []) {
        return [];
    }

    $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds), static fn (int $id): bool => $id > 0)));
    if ($postIds === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($postIds), '?'));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT post_id
         FROM post_likes
         WHERE user_id = ?
           AND post_id IN (' . $placeholders . ')'
    );
    $stmt->execute(array_merge([$userId], $postIds));
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return is_array($rows)
        ? array_map('intval', $rows)
        : [];
}

function isPostLikedByUser(int $postId, int $userId): bool
{
    if ($postId < 1 || $userId < 1) {
        return false;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT 1
         FROM post_likes
         WHERE post_id = :post_id
           AND user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([
        'post_id' => $postId,
        'user_id' => $userId,
    ]);

    return $stmt->fetch() !== false;
}

/**
 * @return array{ok: true, liked: bool, like_count: int, like_label: string}|array{ok: false, error: string}
 */
function togglePostLike(int $postId, int $userId): array
{
    if ($postId < 1 || $userId < 1) {
        return ['ok' => false, 'error' => 'Invalid post.'];
    }

    $pdo = createPdoConnection();
    $pdo->beginTransaction();

    try {
        $postStmt = $pdo->prepare(
            'SELECT id, like_count
             FROM posts
             WHERE id = :id
               AND is_deleted = FALSE
             LIMIT 1'
        );
        $postStmt->execute(['id' => $postId]);
        $post = $postStmt->fetch();

        if ($post === false) {
            $pdo->rollBack();

            return ['ok' => false, 'error' => 'Post not found.'];
        }

        $existing = $pdo->prepare(
            'SELECT 1
             FROM post_likes
             WHERE post_id = :post_id
               AND user_id = :user_id
             LIMIT 1'
        );
        $existing->execute([
            'post_id' => $postId,
            'user_id' => $userId,
        ]);
        $hasLike = $existing->fetch() !== false;

        if ($hasLike) {
            $delete = $pdo->prepare(
                'DELETE FROM post_likes
                 WHERE post_id = :post_id
                   AND user_id = :user_id'
            );
            $delete->execute([
                'post_id' => $postId,
                'user_id' => $userId,
            ]);

            $update = $pdo->prepare(
                'UPDATE posts
                 SET like_count = GREATEST(0, like_count - 1),
                     updated_at = NOW()
                 WHERE id = :id
                 RETURNING like_count'
            );
            $update->execute(['id' => $postId]);
            $liked = false;
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO post_likes (post_id, user_id)
                 VALUES (:post_id, :user_id)'
            );
            $insert->execute([
                'post_id' => $postId,
                'user_id' => $userId,
            ]);

            $update = $pdo->prepare(
                'UPDATE posts
                 SET like_count = like_count + 1,
                     updated_at = NOW()
                 WHERE id = :id
                 RETURNING like_count'
            );
            $update->execute(['id' => $postId]);
            $liked = true;
        }

        $counts = $update->fetch();
        $pdo->commit();

        $likeCount = (int) ($counts['like_count'] ?? $post['like_count'] ?? 0);

        if ($liked) {
            notifyPostLike($postId, $userId);
        }

        return [
            'ok' => true,
            'liked' => $liked,
            'like_count' => $likeCount,
            'like_label' => formatEngagementCount($likeCount),
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}
