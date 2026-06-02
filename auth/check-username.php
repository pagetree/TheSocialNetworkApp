<?php

declare(strict_types=1);

$rateLimitError = enforceRateLimit('auth.check_username');
if ($rateLimitError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $rateLimitError['error'],
    ], $rateLimitError['status']);
    return;
}

$username = trim((string) ($_GET['username'] ?? ''));

try {
    $result = checkUsernameAvailability($username);
} catch (Throwable $exception) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to check username right now.',
    ], 500);
    return;
}

jsonResponse([
    'ok' => true,
    'valid' => $result['valid'],
    'available' => $result['available'],
    'username' => $result['username'],
    'error' => $result['error'],
]);
