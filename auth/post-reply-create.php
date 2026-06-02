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
$guardError = guardAuthRequest('posts.reply', 'post_reply', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$conversationId = (int) ($payload['post_id'] ?? $payload['conversation_id'] ?? 0);
$parentReplyId = (int) ($payload['parent_reply_id'] ?? 0);
$body = (string) ($payload['body'] ?? '');

if ($conversationId < 1) {
    jsonResponse([
        'ok' => false,
        'error' => 'Invalid post.',
    ], 422);
    return;
}

$error = validatePostReplyBody($body);
if ($error !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $error,
    ], 422);
    return;
}

$userId = (int) $sessionUser['id'];
$normalizedBody = normalizePostBody($body);
$parentReplyIdForDb = $parentReplyId > 0 ? $parentReplyId : null;

try {
    $reply = createPostReply($conversationId, $userId, $normalizedBody, $parentReplyIdForDb);
} catch (Throwable) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to post reply right now.',
    ], 500);
    return;
}

if ($reply === null) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to post reply right now.',
    ], 500);
    return;
}

$appPaths = appPaths();
jsonResponse([
    'ok' => true,
    'reply' => postReplyPayload($reply, $appPaths['url']),
]);
