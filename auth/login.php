<?php

declare(strict_types=1);

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
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
    jsonResponse([
        'ok' => false,
        'error' => 'Invalid email/username or password.',
    ], 401);
    return;
}

loginUser($user);
jsonResponse(['ok' => true]);
