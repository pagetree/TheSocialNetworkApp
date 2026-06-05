<?php

declare(strict_types=1);

/**
 * @return 'view'|null
 */
function normalizeProfileStatEventType(string $eventType): ?string
{
    $eventType = strtolower(trim($eventType));

    return $eventType === 'view' ? 'view' : null;
}

/**
 * @return array{ok: true, recorded: bool}|array{ok: false, error: string}
 */
function recordProfileStat(int $profileUserId, int $viewerUserId, string $eventType): array
{
    $eventType = normalizeProfileStatEventType($eventType);
    if ($eventType === null) {
        return ['ok' => false, 'error' => 'Invalid stat event.'];
    }

    if ($profileUserId < 1 || $viewerUserId < 1) {
        return ['ok' => false, 'error' => 'Invalid user.'];
    }

    if ($profileUserId === $viewerUserId) {
        return ['ok' => true, 'recorded' => false];
    }

    $profileUser = fetchUserById($profileUserId);
    if ($profileUser === null) {
        return ['ok' => false, 'error' => 'User not found.'];
    }

    if (!userProfileIsVisible($profileUser)) {
        return ['ok' => true, 'recorded' => false];
    }

    $pdo = createPdoConnection();
    $insert = $pdo->prepare(
        'INSERT INTO profile_stat_events (profile_user_id, viewer_user_id, event_type)
         VALUES (:profile_user_id, :viewer_user_id, :event_type)
         ON CONFLICT (profile_user_id, viewer_user_id, event_type) DO NOTHING
         RETURNING profile_user_id'
    );
    $insert->execute([
        'profile_user_id' => $profileUserId,
        'viewer_user_id' => $viewerUserId,
        'event_type' => $eventType,
    ]);

    return [
        'ok' => true,
        'recorded' => $insert->fetch() !== false,
    ];
}
