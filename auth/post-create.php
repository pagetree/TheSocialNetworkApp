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

if (array_key_exists('media_url', $payload) || array_key_exists('media_type', $payload)) {
    jsonResponse([
        'ok' => false,
        'error' => 'Media uploads are not available yet.',
    ], 422);
    return;
}

$errors = [
    validatePostBody($body),
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

$userId = (int) $sessionUser['id'];
$normalizedBody = normalizePostBody($body);
$normalizedLocation = normalizePostLocationLabel($locationLabel);

try {
    $post = createPost($userId, $normalizedBody, $normalizedLocation);
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

$author = fetchUserById($userId);
if ($author === null) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to create post right now.',
    ], 500);
    return;
}

$post['display_name'] = $author['display_name'];
$post['handle'] = $author['handle'];
$post['avatar_url'] = $author['avatar_url'];

$appPaths = appPaths();
jsonResponse([
    'ok' => true,
    'post' => postFeedPayload($post, $appPaths['url']),
]);
