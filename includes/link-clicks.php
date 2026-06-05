<?php

declare(strict_types=1);

/**
 * @return array{ok: true, recorded: bool}|array{ok: false, error: string}
 */
function recordLinkClick(
    int $viewerUserId,
    ?int $profileUserId = null,
    ?int $postId = null,
    ?int $replyId = null,
): array {
    if ($viewerUserId < 1) {
        return ['ok' => false, 'error' => 'Invalid user.'];
    }

    $profileUserId = $profileUserId !== null && $profileUserId > 0 ? $profileUserId : null;
    $postId = $postId !== null && $postId > 0 ? $postId : null;
    $replyId = $replyId !== null && $replyId > 0 ? $replyId : null;

    $sourceCount = ($profileUserId !== null ? 1 : 0)
        + ($postId !== null ? 1 : 0)
        + ($replyId !== null ? 1 : 0);

    if ($sourceCount !== 1) {
        return ['ok' => false, 'error' => 'Invalid request.'];
    }

    $pdo = createPdoConnection();
    $ownerUserId = 0;

    if ($replyId !== null) {
        $stmt = $pdo->prepare(
            'SELECT id, user_id
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

        $ownerUserId = (int) $reply['user_id'];
    } elseif ($postId !== null) {
        $stmt = $pdo->prepare(
            'SELECT id, user_id
             FROM posts
             WHERE id = :id
               AND is_deleted = FALSE
             LIMIT 1'
        );
        $stmt->execute(['id' => $postId]);
        $post = $stmt->fetch();

        if ($post === false) {
            return ['ok' => false, 'error' => 'Post not found.'];
        }

        $ownerUserId = (int) $post['user_id'];
    } else {
        $profileUser = fetchUserById((int) $profileUserId);
        if ($profileUser === null) {
            return ['ok' => false, 'error' => 'User not found.'];
        }

        if (!userProfileIsVisible($profileUser)) {
            return ['ok' => true, 'recorded' => false];
        }

        $websiteUrl = trim((string) ($profileUser['website_url'] ?? ''));
        if ($websiteUrl === '') {
            return ['ok' => true, 'recorded' => false];
        }

        $ownerUserId = (int) $profileUserId;
    }

    if ($ownerUserId < 1 || $ownerUserId === $viewerUserId) {
        return ['ok' => true, 'recorded' => false];
    }

    $insert = $pdo->prepare(
        'INSERT INTO link_click_events (owner_user_id, viewer_user_id, post_id, reply_id)
         VALUES (:owner_user_id, :viewer_user_id, :post_id, :reply_id)'
    );
    $insert->execute([
        'owner_user_id' => $ownerUserId,
        'viewer_user_id' => $viewerUserId,
        'post_id' => $postId,
        'reply_id' => $replyId,
    ]);

    return [
        'ok' => true,
        'recorded' => true,
    ];
}
