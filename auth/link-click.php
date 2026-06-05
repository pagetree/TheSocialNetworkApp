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
$guardError = guardAuthRequest('links.click', 'link_click', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$postId = (int) ($payload['post_id'] ?? 0);
$replyId = (int) ($payload['reply_id'] ?? 0);
$profileUserId = (int) ($payload['profile_user_id'] ?? 0);

try {
    $result = recordLinkClick(
        (int) $sessionUser['id'],
        $profileUserId > 0 ? $profileUserId : null,
        $postId > 0 ? $postId : null,
        $replyId > 0 ? $replyId : null,
    );
} catch (Throwable) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to record link click right now.',
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
    'recorded' => $result['recorded'],
]);
