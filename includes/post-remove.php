<?php

declare(strict_types=1);

/**
 * @param list<string> $urls
 */
function purgePostMediaUrlsFromR2(array $urls): void
{
    foreach ($urls as $mediaUrl) {
        if (!is_string($mediaUrl) || $mediaUrl === '') {
            continue;
        }

        try {
            r2DeleteObjectByUrl($mediaUrl);
        } catch (Throwable) {
            error_log('R2 media delete failed for URL: ' . $mediaUrl);
        }
    }
}

/**
 * @return list<string>
 */
function collectPostMediaUrls(int $postId): array
{
    if ($postId < 1) {
        return [];
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT media_url
         FROM post_media
         WHERE post_id = :post_id'
    );
    $stmt->execute(['post_id' => $postId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return is_array($rows)
        ? array_values(array_filter(array_map('strval', $rows), static fn (string $url): bool => $url !== ''))
        : [];
}

/**
 * @return list<string>
 */
function collectReplyMediaUrlsForReplyIds(array $replyIds): array
{
    $replyIds = array_values(array_unique(array_filter(array_map('intval', $replyIds), static fn (int $id): bool => $id > 0)));
    if ($replyIds === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($replyIds), '?'));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT media_url
         FROM post_reply_media
         WHERE reply_id IN (' . $placeholders . ')'
    );
    $stmt->execute($replyIds);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return is_array($rows)
        ? array_values(array_filter(array_map('strval', $rows), static fn (string $url): bool => $url !== ''))
        : [];
}

/**
 * @return list<int>
 */
function fetchActiveReplyIdsForConversation(int $conversationId): array
{
    if ($conversationId < 1) {
        return [];
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT id
         FROM post_replies
         WHERE conversation_id = :conversation_id
           AND is_deleted = FALSE'
    );
    $stmt->execute(['conversation_id' => $conversationId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return is_array($rows)
        ? array_values(array_map('intval', $rows))
        : [];
}

/**
 * @return array{ok: true}|array{ok: false, error: string, status: int}
 */
function removePostForUser(int $postId, int $userId): array
{
    if ($postId < 1 || $userId < 1) {
        return ['ok' => false, 'error' => 'Invalid post.', 'status' => 422];
    }

    $pdo = createPdoConnection();
    $ownerCheck = $pdo->prepare(
        'SELECT id, quoted_post_id
         FROM posts
         WHERE id = :id
           AND user_id = :user_id
           AND is_deleted = FALSE
         LIMIT 1'
    );
    $ownerCheck->execute([
        'id' => $postId,
        'user_id' => $userId,
    ]);
    $ownedPost = $ownerCheck->fetch();

    if ($ownedPost === false) {
        return ['ok' => false, 'error' => 'Post not found.', 'status' => 404];
    }

    $quotedPostId = (int) ($ownedPost['quoted_post_id'] ?? 0);

    $hashtagIds = fetchHashtagIdsForPost($postId);
    $replyIds = fetchActiveReplyIdsForConversation($postId);
    $mediaUrls = array_merge(
        collectPostMediaUrls($postId),
        collectReplyMediaUrlsForReplyIds($replyIds)
    );

    try {
        $pdo->beginTransaction();

        if ($replyIds !== []) {
            $replyPlaceholders = implode(', ', array_fill(0, count($replyIds), '?'));
            $softDeleteReplies = $pdo->prepare(
                'UPDATE post_replies
                 SET is_deleted = TRUE,
                     deleted_at = NOW(),
                     updated_at = NOW()
                 WHERE id IN (' . $replyPlaceholders . ')'
            );
            $softDeleteReplies->execute($replyIds);
        }

        $softDeletePost = $pdo->prepare(
            'UPDATE posts
             SET is_deleted = TRUE,
                 deleted_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id
               AND user_id = :user_id'
        );
        $softDeletePost->execute([
            'id' => $postId,
            'user_id' => $userId,
        ]);

        if ($softDeletePost->rowCount() < 1) {
            $pdo->rollBack();

            return ['ok' => false, 'error' => 'Post not found.', 'status' => 404];
        }

        $pdo->prepare('DELETE FROM post_media WHERE post_id = :post_id')->execute(['post_id' => $postId]);

        if ($replyIds !== []) {
            $replyPlaceholders = implode(', ', array_fill(0, count($replyIds), '?'));
            $pdo->prepare(
                'DELETE FROM post_reply_media WHERE reply_id IN (' . $replyPlaceholders . ')'
            )->execute($replyIds);
        }

        $pdo->prepare('DELETE FROM post_hashtags WHERE post_id = :post_id')->execute(['post_id' => $postId]);

        if ($quotedPostId > 0) {
            $pdo->prepare(
                'UPDATE posts
                 SET quote_count = GREATEST(quote_count - 1, 0),
                     updated_at = NOW()
                 WHERE id = :id'
            )->execute(['id' => $quotedPostId]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('removePostForUser failed: ' . $exception->getMessage());

        return ['ok' => false, 'error' => 'Unable to remove post right now.', 'status' => 500];
    }

    purgePostMediaUrlsFromR2($mediaUrls);
    recomputeHashtagPostCounts($hashtagIds);

    return ['ok' => true];
}

/**
 * @return array{ok: true, conversation_id: int}|array{ok: false, error: string, status: int}
 */
function removePostReplyForUser(int $replyId, int $userId): array
{
    if ($replyId < 1 || $userId < 1) {
        return ['ok' => false, 'error' => 'Invalid reply.', 'status' => 422];
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT id, conversation_id, parent_reply_id
         FROM post_replies
         WHERE id = :id
           AND user_id = :user_id
           AND is_deleted = FALSE
         LIMIT 1'
    );
    $stmt->execute([
        'id' => $replyId,
        'user_id' => $userId,
    ]);
    $reply = $stmt->fetch();

    if ($reply === false) {
        return ['ok' => false, 'error' => 'Reply not found.', 'status' => 404];
    }

    $conversationId = (int) $reply['conversation_id'];
    $parentReplyId = isset($reply['parent_reply_id']) && $reply['parent_reply_id'] !== null
        ? (int) $reply['parent_reply_id']
        : null;

    $mediaUrls = collectReplyMediaUrlsForReplyIds([$replyId]);

    try {
        $pdo->beginTransaction();

        $softDelete = $pdo->prepare(
            'UPDATE post_replies
             SET is_deleted = TRUE,
                 deleted_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id
               AND user_id = :user_id'
        );
        $softDelete->execute([
            'id' => $replyId,
            'user_id' => $userId,
        ]);

        if ($softDelete->rowCount() < 1) {
            $pdo->rollBack();

            return ['ok' => false, 'error' => 'Reply not found.', 'status' => 404];
        }

        if ($parentReplyId !== null && $parentReplyId > 0) {
            $pdo->prepare(
                'UPDATE post_replies
                 SET reply_count = GREATEST(reply_count - 1, 0),
                     updated_at = NOW()
                 WHERE id = :id'
            )->execute(['id' => $parentReplyId]);
        } else {
            $pdo->prepare(
                'UPDATE posts
                 SET reply_count = GREATEST(reply_count - 1, 0),
                     updated_at = NOW()
                 WHERE id = :id'
            )->execute(['id' => $conversationId]);
        }

        $pdo->prepare('DELETE FROM post_reply_media WHERE reply_id = :reply_id')->execute(['reply_id' => $replyId]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('removePostReplyForUser failed: ' . $exception->getMessage());

        return ['ok' => false, 'error' => 'Unable to remove reply right now.', 'status' => 500];
    }

    purgePostMediaUrlsFromR2($mediaUrls);

    return [
        'ok' => true,
        'conversation_id' => $conversationId,
    ];
}
