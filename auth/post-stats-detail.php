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
$guardError = guardAuthRequest('posts.stats.detail', 'post_stats', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$viewerUserId = (int) $sessionUser['id'];
$replyId = (int) ($payload['reply_id'] ?? 0);
$postId = (int) ($payload['post_id'] ?? 0);

try {
    if ($replyId > 0) {
        $result = fetchReplyStatsDetail($replyId, $viewerUserId);
    } elseif ($postId > 0) {
        $result = fetchPostStatsDetail($postId, $viewerUserId);
    } else {
        jsonResponse([
            'ok' => false,
            'error' => 'Invalid post.',
        ], 422);
        return;
    }
} catch (Throwable) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to load stats right now.',
    ], 500);
    return;
}

if (!$result['ok']) {
    jsonResponse([
        'ok' => false,
        'error' => $result['error'],
    ], 422);
    return;
}

jsonResponse([
    'ok' => true,
    'kind' => $result['kind'],
    'title' => $result['title'],
    'is_owner' => $result['is_owner'],
    'metrics' => $result['metrics'],
]);
