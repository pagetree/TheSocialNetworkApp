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
$guardError = guardAuthRequest('onboarding.bio', 'onboarding', $payload);
if ($guardError !== null) {
    jsonResponse(['ok' => false, 'error' => $guardError['error']], $guardError['status']);
    return;
}

$bio = (string) ($payload['bio'] ?? '');

try {
    $updatedUser = updateUserOnboardingBio((int) $sessionUser['id'], $bio);
} catch (InvalidArgumentException $exception) {
    jsonResponse(['ok' => false, 'error' => $exception->getMessage()], 422);
    return;
} catch (Throwable) {
    jsonResponse(['ok' => false, 'error' => 'Unable to save bio.'], 500);
    return;
}

if ($updatedUser === null) {
    jsonResponse(['ok' => false, 'error' => 'Unable to save bio.'], 500);
    return;
}

loginUser($updatedUser);
$appPaths = appPaths();

jsonResponse([
    'ok' => true,
    'user' => userProfilePayload($updatedUser, $appPaths['url']),
]);
