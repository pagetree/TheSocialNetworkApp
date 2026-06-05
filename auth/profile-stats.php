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
$guardError = guardAuthRequest('profile.stats', 'profile_stats', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$profileUserId = (int) ($payload['profile_user_id'] ?? 0);
$eventType = (string) ($payload['event'] ?? '');

if ($profileUserId < 1) {
    jsonResponse([
        'ok' => false,
        'error' => 'Invalid user.',
    ], 422);
    return;
}

try {
    $result = recordProfileStat($profileUserId, (int) $sessionUser['id'], $eventType);
} catch (Throwable) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to record stat right now.',
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
