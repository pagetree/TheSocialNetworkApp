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
$guardError = guardAuthRequest('posts.like', 'post_like', $payload);
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
    $result = togglePostLike($postId, (int) $sessionUser['id']);
} catch (Throwable) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to update like right now.',
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
    'liked' => $result['liked'],
    'like_count' => $result['like_count'],
    'like_label' => $result['like_label'],
]);
