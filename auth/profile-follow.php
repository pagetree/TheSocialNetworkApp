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
$guardError = guardAuthRequest('users.follow', 'profile_follow', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$userId = (int) ($payload['user_id'] ?? 0);
if ($userId < 1) {
    jsonResponse([
        'ok' => false,
        'error' => 'Invalid user.',
    ], 422);
    return;
}

try {
    $result = toggleUserFollow((int) $sessionUser['id'], $userId);
} catch (Throwable) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to update follow right now.',
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

$counts = fetchUserFollowCounts($userId);

jsonResponse([
    'ok' => true,
    'following' => $result['following'],
    'followers_count' => $counts['followers'],
    'followers_label' => formatEngagementCount($counts['followers']),
]);
