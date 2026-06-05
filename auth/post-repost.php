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
$guardError = guardAuthRequest('posts.repost', 'post_repost', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$postId = (int) ($payload['post_id'] ?? 0);
if ($postId < 1) {
    jsonResponse([
        'ok' => false,
        'error' => 'Invalid post.',
    ], 422);
    return;
}

try {
    $result = createPostRepost((int) $sessionUser['id'], $postId);
} catch (Throwable) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to repost right now.',
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
    'repost_count' => $result['repost_count'],
    'repost_label' => $result['repost_label'],
    'repost_entry_id' => $result['repost_entry_id'],
]);
