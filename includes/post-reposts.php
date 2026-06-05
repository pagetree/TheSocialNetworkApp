<?php

declare(strict_types=1);

/**
 * @param list<int> $postIds Original post ids.
 * @return list<int>
 */
function fetchRepostedPostIdsForUser(int $userId, array $postIds): array
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
        'SELECT repost_of_post_id
         FROM posts
         WHERE user_id = ?
           AND repost_of_post_id IN (' . $placeholders . ')
           AND is_deleted = FALSE'
    );
    $stmt->execute(array_merge([$userId], $postIds));
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return is_array($rows)
        ? array_map('intval', $rows)
        : [];
}

/**
 * @return array{ok: true, repost_count: int, repost_label: string, repost_entry_id: int}|array{ok: false, error: string}
 */
function createPostRepost(int $userId, int $postId): array
{
    if ($userId < 1 || $postId < 1) {
        return ['ok' => false, 'error' => 'Invalid post.'];
    }

    $pdo = createPdoConnection();
    $pdo->beginTransaction();

    try {
        $postStmt = $pdo->prepare(
            'SELECT id, user_id, repost_of_post_id, repost_count
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

        $originalPostId = (int) ($post['repost_of_post_id'] ?? 0);
        if ($originalPostId > 0) {
            $postId = $originalPostId;
            $postStmt->execute(['id' => $postId]);
            $post = $postStmt->fetch();
            if ($post === false) {
                $pdo->rollBack();

                return ['ok' => false, 'error' => 'Post not found.'];
            }
        }

        $insert = $pdo->prepare(
            'INSERT INTO posts (user_id, repost_of_post_id)
             VALUES (:user_id, :repost_of_post_id)
             RETURNING id'
        );
        $insert->execute([
            'user_id' => $userId,
            'repost_of_post_id' => $postId,
        ]);
        $repostEntry = $insert->fetch();
        $repostEntryId = (int) ($repostEntry['id'] ?? 0);

        $update = $pdo->prepare(
            'UPDATE posts
             SET repost_count = repost_count + 1,
                 updated_at = NOW()
             WHERE id = :id
             RETURNING repost_count'
        );
        $update->execute(['id' => $postId]);
        $counts = $update->fetch();

        $pdo->commit();

        $repostCount = (int) ($counts['repost_count'] ?? $post['repost_count'] ?? 0);

        notifyPostRepost($postId, $userId);

        return [
            'ok' => true,
            'repost_count' => $repostCount,
            'repost_label' => formatEngagementCount($repostCount),
            'repost_entry_id' => $repostEntryId,
        ];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}
