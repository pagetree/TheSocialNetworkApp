<?php

declare(strict_types=1);

const NOTIFICATION_TYPES = ['like', 'reply', 'repost', 'quote', 'follow'];

function createNotificationIfEligible(
    int $recipientUserId,
    int $actorUserId,
    string $type,
    ?int $postId = null,
    ?int $replyId = null
): void {
    if ($recipientUserId < 1 || $actorUserId < 1 || $recipientUserId === $actorUserId) {
        return;
    }

    if (!in_array($type, NOTIFICATION_TYPES, true)) {
        return;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (recipient_user_id, actor_user_id, type, post_id, reply_id)
         VALUES (:recipient_user_id, :actor_user_id, :type, :post_id, :reply_id)'
    );
    $stmt->execute([
        'recipient_user_id' => $recipientUserId,
        'actor_user_id' => $actorUserId,
        'type' => $type,
        'post_id' => $postId,
        'reply_id' => $replyId,
    ]);
}

function notifyPostLike(int $postId, int $actorUserId): void
{
    if ($postId < 1 || $actorUserId < 1) {
        return;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT user_id
         FROM posts
         WHERE id = :id
           AND is_deleted = FALSE
         LIMIT 1'
    );
    $stmt->execute(['id' => $postId]);
    $row = $stmt->fetch();

    if ($row === false) {
        return;
    }

    createNotificationIfEligible((int) $row['user_id'], $actorUserId, 'like', $postId);
}

function notifyPostRepost(int $originalPostId, int $actorUserId): void
{
    if ($originalPostId < 1 || $actorUserId < 1) {
        return;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT user_id
         FROM posts
         WHERE id = :id
           AND is_deleted = FALSE
         LIMIT 1'
    );
    $stmt->execute(['id' => $originalPostId]);
    $row = $stmt->fetch();

    if ($row === false) {
        return;
    }

    createNotificationIfEligible((int) $row['user_id'], $actorUserId, 'repost', $originalPostId);
}

function notifyPostQuote(int $quotedPostId, int $quotePostId, int $actorUserId): void
{
    if ($quotedPostId < 1 || $actorUserId < 1) {
        return;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT user_id
         FROM posts
         WHERE id = :id
           AND is_deleted = FALSE
         LIMIT 1'
    );
    $stmt->execute(['id' => $quotedPostId]);
    $row = $stmt->fetch();

    if ($row === false) {
        return;
    }

    createNotificationIfEligible((int) $row['user_id'], $actorUserId, 'quote', $quotePostId);
}

function notifyPostReply(int $conversationId, int $actorUserId, ?int $parentReplyId, int $replyId): void
{
    if ($conversationId < 1 || $actorUserId < 1 || $replyId < 1) {
        return;
    }

    $pdo = createPdoConnection();
    $recipientUserId = 0;

    if ($parentReplyId !== null && $parentReplyId > 0) {
        $stmt = $pdo->prepare(
            'SELECT user_id
             FROM post_replies
             WHERE id = :id
               AND is_deleted = FALSE
             LIMIT 1'
        );
        $stmt->execute(['id' => $parentReplyId]);
        $row = $stmt->fetch();
        $recipientUserId = $row !== false ? (int) $row['user_id'] : 0;
    } else {
        $stmt = $pdo->prepare(
            'SELECT user_id
             FROM posts
             WHERE id = :id
               AND is_deleted = FALSE
             LIMIT 1'
        );
        $stmt->execute(['id' => $conversationId]);
        $row = $stmt->fetch();
        $recipientUserId = $row !== false ? (int) $row['user_id'] : 0;
    }

    if ($recipientUserId < 1) {
        return;
    }

    createNotificationIfEligible($recipientUserId, $actorUserId, 'reply', $conversationId, $replyId);
}

function notifyUserFollow(int $followedUserId, int $followerUserId): void
{
    createNotificationIfEligible($followedUserId, $followerUserId, 'follow');
}

/**
 * @return list<array<string, mixed>>
 */
function fetchNotificationsForUser(int $userId, int $limit = 50, int $offset = 0): array
{
    if ($userId < 1) {
        return [];
    }

    $limit = max(1, min($limit, 100));
    $offset = max(0, $offset);

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT
            n.id,
            n.type,
            n.post_id,
            n.reply_id,
            n.is_read,
            n.created_at,
            u.id AS actor_id,
            u.username,
            u.display_name,
            u.handle,
            u.avatar_url
         FROM notifications n
         INNER JOIN users u ON u.id = n.actor_user_id
         WHERE n.recipient_user_id = :user_id
         ORDER BY n.created_at DESC, n.id DESC
         LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = [];
    while ($row = $stmt->fetch()) {
        $rows[] = $row;
    }

    return $rows;
}

function fetchUnreadNotificationCount(int $userId): int
{
    if ($userId < 1) {
        return 0;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)::int
         FROM notifications
         WHERE recipient_user_id = :user_id
           AND is_read = FALSE'
    );
    $stmt->execute(['user_id' => $userId]);
    $count = $stmt->fetchColumn();

    return is_numeric($count) ? (int) $count : 0;
}

function markAllNotificationsRead(int $userId): void
{
    if ($userId < 1) {
        return;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'UPDATE notifications
         SET is_read = TRUE
         WHERE recipient_user_id = :user_id
           AND is_read = FALSE'
    );
    $stmt->execute(['user_id' => $userId]);
}

/**
 * @param list<int> $notificationIds
 */
function markNotificationsRead(int $userId, array $notificationIds): void
{
    if ($userId < 1 || $notificationIds === []) {
        return;
    }

    $notificationIds = array_values(array_unique(array_filter(
        array_map('intval', $notificationIds),
        static fn (int $id): bool => $id > 0
    )));

    if ($notificationIds === []) {
        return;
    }

    $placeholders = implode(', ', array_fill(0, count($notificationIds), '?'));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'UPDATE notifications
         SET is_read = TRUE
         WHERE recipient_user_id = ?
           AND id IN (' . $placeholders . ')
           AND is_read = FALSE'
    );
    $stmt->execute(array_merge([$userId], $notificationIds));
}

/**
 * @param array<string, mixed> $notification
 */
function notificationMessage(array $notification): string
{
    $actorName = (string) ($notification['display_name'] ?? 'User');
    $type = (string) ($notification['type'] ?? '');

    return match ($type) {
        'like' => __('notifications.types.like', ['name' => $actorName]),
        'reply' => __('notifications.types.reply', ['name' => $actorName]),
        'repost' => __('notifications.types.repost', ['name' => $actorName]),
        'quote' => __('notifications.types.quote', ['name' => $actorName]),
        'follow' => __('notifications.types.follow', ['name' => $actorName]),
        default => __('notifications.types.generic', ['name' => $actorName]),
    };
}

/**
 * @param array<string, mixed> $notification
 */
function notificationTargetUrl(array $notification, callable $url): string
{
    $type = (string) ($notification['type'] ?? '');
    $postId = (int) ($notification['post_id'] ?? 0);

    if ($type === 'follow') {
        return profileUrlForUser([
            'username' => (string) ($notification['username'] ?? ''),
        ], $url);
    }

    if ($postId > 0) {
        return postUrl($postId, $url);
    }

    return profileUrlForUser([
        'username' => (string) ($notification['username'] ?? ''),
    ], $url);
}
