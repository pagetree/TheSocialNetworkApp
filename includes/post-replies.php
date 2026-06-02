<?php

declare(strict_types=1);

const POST_REPLY_MAX_LENGTH = 300;

function validatePostReplyBody(string $body): ?string
{
    $body = sanitizePostText($body);

    if ($body === '') {
        return 'Write a reply before posting.';
    }

    if (mb_strlen($body) > POST_REPLY_MAX_LENGTH) {
        return 'Reply must be ' . POST_REPLY_MAX_LENGTH . ' characters or less.';
    }

    return null;
}

/**
 * @return list<array<string, mixed>>
 */
function fetchPostReplies(int $conversationId): array
{
    if ($conversationId < 1) {
        return [];
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT r.id, r.conversation_id, r.user_id, r.parent_reply_id, r.body, r.like_count, r.reply_count, r.created_at,
                u.display_name, u.handle, u.avatar_url
         FROM post_replies r
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.conversation_id = :conversation_id
           AND r.is_deleted = FALSE
         ORDER BY r.created_at ASC'
    );
    $stmt->execute(['conversation_id' => $conversationId]);
    $rows = $stmt->fetchAll();

    return is_array($rows) ? $rows : [];
}

/**
 * @return array<string, mixed>|null
 */
function fetchPostReplyById(int $replyId, int $conversationId): ?array
{
    if ($replyId < 1 || $conversationId < 1) {
        return null;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT id, conversation_id, parent_reply_id
         FROM post_replies
         WHERE id = :id
           AND conversation_id = :conversation_id
           AND is_deleted = FALSE
         LIMIT 1'
    );
    $stmt->execute([
        'id' => $replyId,
        'conversation_id' => $conversationId,
    ]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

/**
 * @return array<string, mixed>|null
 */
function createPostReply(int $conversationId, int $userId, string $body, ?int $parentReplyId = null): ?array
{
    $pdo = createPdoConnection();
    $pdo->beginTransaction();

    try {
        $postCheck = $pdo->prepare(
            'SELECT id
             FROM posts
             WHERE id = :id
               AND is_deleted = FALSE
             LIMIT 1'
        );
        $postCheck->execute(['id' => $conversationId]);
        if ($postCheck->fetch() === false) {
            $pdo->rollBack();

            return null;
        }

        if ($parentReplyId !== null) {
            $parentCheck = $pdo->prepare(
                'SELECT id
                 FROM post_replies
                 WHERE id = :id
                   AND conversation_id = :conversation_id
                   AND is_deleted = FALSE
                 LIMIT 1'
            );
            $parentCheck->execute([
                'id' => $parentReplyId,
                'conversation_id' => $conversationId,
            ]);
            if ($parentCheck->fetch() === false) {
                $pdo->rollBack();

                return null;
            }
        }

        $insert = $pdo->prepare(
            'INSERT INTO post_replies (conversation_id, user_id, parent_reply_id, body)
             VALUES (:conversation_id, :user_id, :parent_reply_id, :body)
             RETURNING id, conversation_id, user_id, parent_reply_id, body, like_count, reply_count, created_at'
        );
        $insert->execute([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'parent_reply_id' => $parentReplyId,
            'body' => $body,
        ]);
        $reply = $insert->fetch();

        if ($reply === false) {
            $pdo->rollBack();

            return null;
        }

        if ($parentReplyId !== null) {
            $update = $pdo->prepare(
                'UPDATE post_replies
                 SET reply_count = reply_count + 1,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $update->execute(['id' => $parentReplyId]);
        } else {
            $update = $pdo->prepare(
                'UPDATE posts
                 SET reply_count = reply_count + 1,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $update->execute(['id' => $conversationId]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }

    $author = fetchUserById($userId);
    if ($author === null) {
        return is_array($reply) ? $reply : null;
    }

    $reply['display_name'] = $author['display_name'];
    $reply['handle'] = $author['handle'];
    $reply['avatar_url'] = $author['avatar_url'];

    return $reply;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function postReplyPayload(array $row, callable $url): array
{
    $user = [
        'display_name' => (string) ($row['display_name'] ?? ''),
        'handle' => (string) ($row['handle'] ?? ''),
        'avatar_url' => (string) ($row['avatar_url'] ?? ''),
    ];

    return [
        'id' => (int) ($row['id'] ?? 0),
        'parent_reply_id' => isset($row['parent_reply_id']) && $row['parent_reply_id'] !== null
            ? (int) $row['parent_reply_id']
            : null,
        'body' => (string) ($row['body'] ?? ''),
        'like_count' => (int) ($row['like_count'] ?? 0),
        'reply_count' => (int) ($row['reply_count'] ?? 0),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'time_label' => formatPostTimeLabel((string) ($row['created_at'] ?? '')),
        'author' => [
            'display_name' => $user['display_name'],
            'handle' => $user['handle'],
            'avatar_url' => userMediaUrl($user, 'avatar_url', $url),
        ],
    ];
}

/**
 * @param list<array<string, mixed>> $rows
 */
function renderPostReplyTree(array $rows, callable $url): void
{
    $children = [];

    foreach ($rows as $row) {
        $parentKey = isset($row['parent_reply_id']) && $row['parent_reply_id'] !== null
            ? (string) $row['parent_reply_id']
            : 'root';
        $children[$parentKey][] = $row;
    }

    $renderBranch = static function (string $parentKey, int $depth) use (&$renderBranch, $children, $url): void {
        foreach ($children[$parentKey] ?? [] as $row) {
            renderPostReplyItem($row, $url, $depth);
            $renderBranch((string) ($row['id'] ?? ''), $depth + 1);
        }
    };

    $renderBranch('root', 0);
}

/**
 * @param array<string, mixed> $row
 */
function renderPostReplyItem(array $row, callable $url, int $depth = 0): void
{
    $reply = postReplyPayload($row, $url);
    require __DIR__ . '/posts/post-reply-item.php';
}
