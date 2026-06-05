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

$payload = $_POST;
$guardError = guardAuthRequest('posts.create', 'post_create', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$body = (string) ($payload['body'] ?? '');
$locationLabel = (string) ($payload['location_label'] ?? '');
$hasMedia = postMediaHasUpload('media');
$quotedPostId = (int) ($payload['quoted_post_id'] ?? 0);
$resolvedQuotedPostId = $quotedPostId > 0 ? resolveQuotedPostId($quotedPostId) : null;

if ($quotedPostId > 0 && $resolvedQuotedPostId === null) {
    jsonResponse([
        'ok' => false,
        'error' => 'Post not found.',
    ], 404);
    return;
}

$hasQuotedPost = $resolvedQuotedPostId !== null;

$errors = [
    validatePostBodyForCreate($body, $hasMedia, $hasQuotedPost),
    validatePostLocationLabel($locationLabel),
];

foreach ($errors as $error) {
    if ($error !== null) {
        jsonResponse([
            'ok' => false,
            'error' => $error,
        ], 422);
        return;
    }
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
$bodyForDb = $normalizedBody === '' ? null : $normalizedBody;
$normalizedLocation = normalizePostLocationLabel($locationLabel);

try {
    $post = createPost($userId, $bodyForDb, $normalizedLocation, $resolvedQuotedPostId);
} catch (Throwable) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to create post right now.',
    ], 500);
    return;
}

if ($post === null) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to create post right now.',
    ], 500);
    return;
}

$postId = (int) $post['id'];
$uploadedMedia = [];

if ($mediaFiles !== []) {
    foreach ($mediaFiles as $mediaFile) {
        $mediaUpload = r2UploadPostMediaFile($userId, $postId, $mediaFile);
        if (!$mediaUpload['ok']) {
            foreach ($uploadedMedia as $uploaded) {
                r2DeleteObjectByUrl($uploaded['url']);
            }
            deletePostForUser($postId, $userId);
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
        attachPostMediaRecords($postId, $uploadedMedia);
    } catch (Throwable) {
        foreach ($uploadedMedia as $uploaded) {
            r2DeleteObjectByUrl($uploaded['url']);
        }
        deletePostForUser($postId, $userId);
        jsonResponse([
            'ok' => false,
            'error' => 'Unable to create post right now.',
        ], 500);
        return;
    }

    $post['media_items'] = array_map(
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

$author = fetchUserById($userId);
if ($author === null) {
    if ($uploadedMedia !== []) {
        foreach ($uploadedMedia as $uploaded) {
            r2DeleteObjectByUrl($uploaded['url']);
        }
    }
    deletePostForUser($postId, $userId);
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to create post right now.',
    ], 500);
    return;
}

$post['display_name'] = $author['display_name'];
$post['handle'] = $author['handle'];
$post['avatar_url'] = $author['avatar_url'];

if ($bodyForDb !== null) {
    syncPostHashtags($postId, $bodyForDb);
    notifyPostMentions($postId, $userId, $bodyForDb);
}

consumeCsrfToken(extractCsrfToken($payload), 'post_create');

$appPaths = appPaths();
$response = [
    'ok' => true,
    'post' => postFeedPayload($post, $appPaths['url']),
];

if ($resolvedQuotedPostId !== null && $resolvedQuotedPostId > 0) {
    $pdo = createPdoConnection();
    $countStmt = $pdo->prepare(
        'SELECT quote_count
         FROM posts
         WHERE id = :id
         LIMIT 1'
    );
    $countStmt->execute(['id' => $resolvedQuotedPostId]);
    $countRow = $countStmt->fetch();
    $quoteCount = (int) ($countRow['quote_count'] ?? 0);
    $response['quoted_post_id'] = $resolvedQuotedPostId;
    $response['quote_count'] = $quoteCount;
    $response['quote_label'] = formatEngagementCount($quoteCount);
}

jsonResponse($response);
