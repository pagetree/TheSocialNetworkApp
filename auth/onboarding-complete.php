<?php

declare(strict_types=1);

$sessionUser = getCurrentUser();
if ($sessionUser === null) {
    jsonResponse(['ok' => false, 'error' => 'You must be signed in.'], 401);
    return;
}

$payload = authPayloadFromRequest();
$guardError = guardAuthRequest('onboarding.complete', 'onboarding', $payload);
if ($guardError !== null) {
    jsonResponse(['ok' => false, 'error' => $guardError['error']], $guardError['status']);
    return;
}

try {
    $updatedUser = completeUserOnboarding((int) $sessionUser['id']);
} catch (Throwable) {
    jsonResponse(['ok' => false, 'error' => 'Unable to finish onboarding.'], 500);
    return;
}

if ($updatedUser === null) {
    jsonResponse(['ok' => false, 'error' => 'Unable to finish onboarding.'], 500);
    return;
}

loginUser($updatedUser);
invalidateCsrfTokens('onboarding');
$appPaths = appPaths();

jsonResponse([
    'ok' => true,
    'redirect_url' => $appPaths['url']('/'),
]);
