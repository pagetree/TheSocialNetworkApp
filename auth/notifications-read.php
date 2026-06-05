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
$guardError = guardAuthRequest('notifications.read', 'notifications_read', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$userId = (int) $sessionUser['id'];
$markAll = !empty($payload['mark_all']);

if ($markAll) {
    markAllNotificationsRead($userId);
} else {
    $notificationIds = $payload['notification_ids'] ?? [];
    if (!is_array($notificationIds)) {
        jsonResponse([
            'ok' => false,
            'error' => 'Invalid notification list.',
        ], 422);
        return;
    }

    markNotificationsRead($userId, $notificationIds);
}

jsonResponse([
    'ok' => true,
    'unread_count' => fetchUnreadNotificationCount($userId),
]);
