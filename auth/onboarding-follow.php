<?php

declare(strict_types=1);

$sessionUser = getCurrentUser();
if ($sessionUser === null) {
    jsonResponse(['ok' => false, 'error' => 'You must be signed in.'], 401);
    return;
}

if (userNeedsOnboarding($sessionUser) === false) {
    jsonResponse(['ok' => false, 'error' => 'Onboarding is already complete.'], 409);
    return;
}

$payload = authPayloadFromRequest();
$guardError = guardAuthRequest('onboarding.follow', 'onboarding', $payload);
if ($guardError !== null) {
    jsonResponse(['ok' => false, 'error' => $guardError['error']], $guardError['status']);
    return;
}

$userIds = $payload['user_ids'] ?? [];
$unfollowUserIds = $payload['unfollow_user_ids'] ?? [];
if (!is_array($userIds) || !is_array($unfollowUserIds)) {
    jsonResponse(['ok' => false, 'error' => 'Invalid accounts.'], 422);
    return;
}

try {
    $unfollowedCount = unfollowUsersOnboarding((int) $sessionUser['id'], $unfollowUserIds);
    $followedCount = followUsersOnboarding((int) $sessionUser['id'], $userIds);
} catch (InvalidArgumentException $exception) {
    jsonResponse(['ok' => false, 'error' => $exception->getMessage()], 422);
    return;
} catch (Throwable) {
    jsonResponse(['ok' => false, 'error' => 'Unable to update follows.'], 500);
    return;
}

jsonResponse([
    'ok' => true,
    'followed_count' => $followedCount,
    'unfollowed_count' => $unfollowedCount,
]);
