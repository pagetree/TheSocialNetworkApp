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
$hasMedia = postMediaHasUpload('media');

if ($conversationId < 1) {
    jsonResponse([
        'ok' => false,
        'error' => 'Invalid post.',
    ], 422);
    return;
}

$error = validatePostReplyForCreate($body, $hasMedia);
if ($error !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $error,
    ], 422);
    return;
}

$mediaFiles = [];
if ($hasMedia) {
    $mediaValidation = validatePostMediaUploads('media');
    if (!$mediaValidation['ok']) {
        jsonResponse([
            'ok' => false,
            'error' => $mediaValidation['error'],
        ], 422);
        return;
    }

    $mediaFiles = $mediaValidation['files'];
}

$userId = (int) $sessionUser['id'];
$normalizedBody = normalizePostBody($body);
$bodyForDb = $normalizedBody === '' ? '' : $normalizedBody;
$parentReplyIdForDb = $parentReplyId > 0 ? $parentReplyId : null;

try {
    $reply = createPostReply($conversationId, $userId, $bodyForDb, $parentReplyIdForDb);
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

$replyId = (int) $reply['id'];
$uploadedMedia = [];

if ($mediaFiles !== []) {
    foreach ($mediaFiles as $mediaFile) {
        $mediaUpload = r2UploadPostReplyMediaFile($userId, $conversationId, $replyId, $mediaFile);
        if (!$mediaUpload['ok']) {
            foreach ($uploadedMedia as $uploaded) {
                r2DeleteObjectByUrl($uploaded['url']);
            }
            deletePostReplyForUser($replyId, $userId);
            jsonResponse([
                'ok' => false,
                'error' => $mediaUpload['error'],
            ], 422);
            return;
        }

        $uploadedMedia[] = [
            'url' => $mediaUpload['url'],
            'media_type' => $mediaUpload['media_type'],
        ];
    }

    try {
        attachPostReplyMediaRecords($replyId, $uploadedMedia);
    } catch (Throwable) {
        foreach ($uploadedMedia as $uploaded) {
            r2DeleteObjectByUrl($uploaded['url']);
        }
        deletePostReplyForUser($replyId, $userId);
        jsonResponse([
            'ok' => false,
            'error' => 'Unable to post reply right now.',
        ], 500);
        return;
    }

    $reply['media_items'] = array_map(
        static fn (array $item, int $sortOrder): array => [
            'id' => 0,
            'media_url' => $item['url'],
            'media_type' => $item['media_type'],
            'sort_order' => $sortOrder,
        ],
        $uploadedMedia,
        array_keys($uploadedMedia)
    );
}

if ($bodyForDb !== '') {
    syncPostReplyHashtags($replyId, $bodyForDb);
}

$appPaths = appPaths();
jsonResponse([
    'ok' => true,
    'reply' => postReplyPayload($reply, $appPaths['url']),
]);
