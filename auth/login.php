<?php

declare(strict_types=1);

$payload = authPayloadFromRequest();

$guardError = guardAuthRequest('auth.login', 'login', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$identifier = trim((string) ($payload['identifier'] ?? ''));
$password = (string) ($payload['password'] ?? '');

if ($identifier === '' || $password === '') {
    jsonResponse([
        'ok' => false,
        'error' => 'Email or username and password are required.',
    ], 422);
    return;
}

try {
    $user = attemptLogin($identifier, $password);
} catch (Throwable $exception) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to sign in right now.',
    ], 500);
    return;
}

if ($user === null) {
    logSecurityEvent('login_failed', ['identifier' => $identifier]);

    jsonResponse([
        'ok' => false,
        'error' => 'Invalid email/username or password.',
    ], 401);
    return;
}

loginUser($user);
invalidateCsrfTokens('login');
jsonResponse(['ok' => true]);
