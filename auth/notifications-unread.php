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

jsonResponse([
    'ok' => true,
    'unread_count' => fetchUnreadNotificationCount((int) $sessionUser['id']),
]);
