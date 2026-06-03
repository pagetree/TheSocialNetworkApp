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
if (!is_array($userIds)) {
    jsonResponse(['ok' => false, 'error' => 'Invalid accounts.'], 422);
    return;
}

try {
    $followedCount = followUsersOnboarding((int) $sessionUser['id'], $userIds);
} catch (InvalidArgumentException $exception) {
    jsonResponse(['ok' => false, 'error' => $exception->getMessage()], 422);
    return;
} catch (Throwable) {
    jsonResponse(['ok' => false, 'error' => 'Unable to follow accounts.'], 500);
    return;
}

jsonResponse([
    'ok' => true,
    'followed_count' => $followedCount,
]);
