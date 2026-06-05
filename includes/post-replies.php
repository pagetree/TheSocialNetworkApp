<?php

declare(strict_types=1);

const POST_REPLY_MAX_LENGTH = 300;

function validatePostReplyBody(string $body): ?string
{
    return validatePostReplyForCreate($body, false);
}

function validatePostReplyForCreate(string $body, bool $hasMedia): ?string
{
    $body = sanitizePostText($body);

    if ($body === '' && !$hasMedia) {
        return __('reply.errors.body_or_media_required');
    }

    if ($body !== '' && mb_strlen($body) > POST_REPLY_MAX_LENGTH) {
        return __('reply.errors.too_long', ['max' => POST_REPLY_MAX_LENGTH]);
    }

    return null;
}

/**
 * @param list<array{url: string, media_type: string}> $mediaRecords
 */
function attachPostReplyMediaRecords(int $replyId, array $mediaRecords): void
{
    if ($mediaRecords === []) {
        return;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO post_reply_media (reply_id, media_url, media_type, sort_order)
         VALUES (:reply_id, :media_url, :media_type, :sort_order)'
    );

    foreach ($mediaRecords as $sortOrder => $record) {
        $stmt->execute([
            'reply_id' => $replyId,
            'media_url' => $record['url'],
            'media_type' => $record['media_type'],
            'sort_order' => $sortOrder,
        ]);
    }
}

/**
 * @param list<int> $replyIds
 * @return array<int, list<array<string, mixed>>>
 */
function fetchPostReplyMediaGroupedByReplyIds(array $replyIds): array
{
    $replyIds = array_values(array_unique(array_filter(array_map('intval', $replyIds), static fn (int $id): bool => $id > 0)));
    if ($replyIds === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($replyIds), '?'));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT id, reply_id, media_url, media_type, sort_order, created_at
         FROM post_reply_media
         WHERE reply_id IN (' . $placeholders . ')
         ORDER BY reply_id ASC, sort_order ASC, id ASC'
    );
    $stmt->execute($replyIds);

    $grouped = [];
    while ($row = $stmt->fetch()) {
        $replyId = (int) $row['reply_id'];
        $grouped[$replyId][] = $row;
    }

    return $grouped;
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function hydratePostRepliesWithMedia(array $rows): array
{
    $replyIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows);
    $grouped = fetchPostReplyMediaGroupedByReplyIds($replyIds);

    foreach ($rows as &$row) {
        $replyId = (int) ($row['id'] ?? 0);
        $row['media_items'] = $grouped[$replyId] ?? [];
    }
    unset($row);

    return $rows;
}

function deletePostReplyForUser(int $replyId, int $userId): void
{
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'DELETE FROM post_replies
         WHERE id = :id
           AND user_id = :user_id'
    );
    $stmt->execute([
        'id' => $replyId,
        'user_id' => $userId,
    ]);
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

    return is_array($rows) ? hydratePostRepliesWithMedia($rows) : [];
}

/**
 * @param list<int> $conversationIds
 * @return array<int, list<array<string, mixed>>>
 */
function fetchPostRepliesGroupedByConversationIds(array $conversationIds): array
{
    $conversationIds = array_values(array_unique(array_filter(
        array_map('intval', $conversationIds),
        static fn (int $id): bool => $id > 0
    )));

    if ($conversationIds === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($conversationIds), '?'));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT r.id, r.conversation_id, r.user_id, r.parent_reply_id, r.body, r.like_count, r.reply_count, r.created_at,
                u.display_name, u.handle, u.avatar_url
         FROM post_replies r
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.conversation_id IN (' . $placeholders . ')
           AND r.is_deleted = FALSE
         ORDER BY r.conversation_id ASC, r.created_at ASC'
    );
    $stmt->execute($conversationIds);
    $rows = $stmt->fetchAll();

    if (!is_array($rows)) {
        return [];
    }

    $rows = hydratePostRepliesWithMedia($rows);
    $grouped = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $conversationId = (int) ($row['conversation_id'] ?? 0);
        if ($conversationId < 1) {
            continue;
        }

        $grouped[$conversationId][] = $row;
    }

    return $grouped;
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

    if (is_array($reply)) {
        notifyPostReply(
            $conversationId,
            $userId,
            $parentReplyId,
            (int) ($reply['id'] ?? 0)
        );
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

    $body = (string) ($row['body'] ?? '');

    return [
        'id' => (int) ($row['id'] ?? 0),
        'parent_reply_id' => isset($row['parent_reply_id']) && $row['parent_reply_id'] !== null
            ? (int) $row['parent_reply_id']
            : null,
        'body' => $body,
        'body_html' => $body !== '' ? formatPostBodyHtml($body, $url) : '',
        'media' => postMediaPayloadItems($row),
        'like_count' => (int) ($row['like_count'] ?? 0),
        'reply_count' => (int) ($row['reply_count'] ?? 0),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'time_label' => formatPostTimeLabel((string) ($row['created_at'] ?? '')),
        'author' => [
            'user_id' => (int) ($row['user_id'] ?? 0),
            'display_name' => $user['display_name'],
            'handle' => $user['handle'],
            'avatar_url' => userMediaUrl($user, 'avatar_url', $url),
        ],
        'user_id' => (int) ($row['user_id'] ?? 0),
    ];
}

/**
 * @param list<array<string, mixed>> $rows
 */
function renderPostReplyTree(array $rows, callable $url, int $currentUserId = 0, int $conversationId = 0): void
{
    $children = [];

    foreach ($rows as $row) {
        $parentId = isset($row['parent_reply_id']) ? (int) $row['parent_reply_id'] : 0;
        $parentKey = $parentId > 0 ? (string) $parentId : 'root';
        $children[$parentKey][] = $row;
    }

    if ($conversationId < 1 && $rows !== []) {
        $conversationId = (int) ($rows[0]['conversation_id'] ?? 0);
    }

    $renderSubtree = static function (array $row, int $depth) use (&$renderSubtree, $children, $url, $currentUserId, $conversationId): void {
        renderPostReplyItem($row, $url, $depth, $currentUserId, $conversationId);

        foreach ($children[(string) ($row['id'] ?? '')] ?? [] as $childRow) {
            $renderSubtree($childRow, $depth + 1);
        }
    };

    foreach ($children['root'] ?? [] as $rootRow) {
        echo '<div class="post-reply-thread">';
        $renderSubtree($rootRow, 0);
        echo '</div>';
    }
}

/**
 * @param array<string, mixed> $row
 */
function renderPostReplyItem(array $row, callable $url, int $depth = 0, int $currentUserId = 0, int $conversationId = 0): void
{
    $reply = postReplyPayload($row, $url);
    $menuConversationId = $conversationId > 0 ? $conversationId : (int) ($row['conversation_id'] ?? 0);
    require __DIR__ . '/posts/post-reply-item.php';
}
