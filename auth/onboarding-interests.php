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
$guardError = guardAuthRequest('onboarding.interests', 'onboarding', $payload);
if ($guardError !== null) {
    jsonResponse(['ok' => false, 'error' => $guardError['error']], $guardError['status']);
    return;
}

$interestIds = $payload['interest_ids'] ?? [];
if (!is_array($interestIds)) {
    jsonResponse(['ok' => false, 'error' => 'Invalid interests.'], 422);
    return;
}

try {
    replaceUserInterests((int) $sessionUser['id'], $interestIds);
} catch (InvalidArgumentException $exception) {
    jsonResponse(['ok' => false, 'error' => $exception->getMessage()], 422);
    return;
} catch (Throwable) {
    jsonResponse(['ok' => false, 'error' => 'Unable to save interests.'], 500);
    return;
}

jsonResponse(['ok' => true]);
