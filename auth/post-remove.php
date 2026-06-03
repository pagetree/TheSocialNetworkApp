<?php

declare(strict_types=1);

$sessionUser = getCurrentUser();
if ($sessionUser === null) {
    jsonResponse([
        'ok' => false,
        'error' => 'You must be signed in.',
    ], 401);
    return;
}

$payload = authPayloadFromRequest();
$guardError = guardAuthRequest('posts.remove', 'post_remove', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$postId = (int) ($payload['post_id'] ?? 0);
$replyId = (int) ($payload['reply_id'] ?? 0);
$userId = (int) $sessionUser['id'];

if ($postId > 0 && $replyId > 0) {
    jsonResponse([
        'ok' => false,
        'error' => 'Specify either a post or a reply to remove.',
    ], 422);
    return;
}

if ($postId > 0) {
    $result = removePostForUser($postId, $userId);
    if (!$result['ok']) {
        jsonResponse([
            'ok' => false,
            'error' => $result['error'],
        ], $result['status']);
        return;
    }

    jsonResponse([
        'ok' => true,
        'removed' => 'post',
        'post_id' => $postId,
    ]);
    return;
}

if ($replyId > 0) {
    $result = removePostReplyForUser($replyId, $userId);
    if (!$result['ok']) {
        jsonResponse([
            'ok' => false,
            'error' => $result['error'],
        ], $result['status']);
        return;
    }

    jsonResponse([
        'ok' => true,
        'removed' => 'reply',
        'reply_id' => $replyId,
        'conversation_id' => $result['conversation_id'],
    ]);
    return;
}

jsonResponse([
    'ok' => false,
    'error' => 'Invalid request.',
], 422);
