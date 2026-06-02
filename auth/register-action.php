<?php

declare(strict_types=1);

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$firstName = trim((string) ($payload['first_name'] ?? ''));
$lastName = trim((string) ($payload['last_name'] ?? ''));
$username = trim((string) ($payload['username'] ?? ''));
$email = trim((string) ($payload['email'] ?? ''));
$password = (string) ($payload['password'] ?? '');
$passwordConfirm = (string) ($payload['password_confirm'] ?? '');

if ($firstName === '' || $lastName === '' || $username === '' || $email === '' || $password === '') {
    jsonResponse([
        'ok' => false,
        'error' => 'All fields are required.',
    ], 422);
    return;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse([
        'ok' => false,
        'error' => 'Enter a valid email address.',
    ], 422);
    return;
}

$normalizedUsername = normalizeUsername($username);
if ($normalizedUsername === '' || strlen($normalizedUsername) < 3) {
    jsonResponse([
        'ok' => false,
        'error' => 'Username must be at least 3 characters (letters, numbers, underscore).',
    ], 422);
    return;
}

if (strlen($password) < 8) {
    jsonResponse([
        'ok' => false,
        'error' => 'Password must be at least 8 characters.',
    ], 422);
    return;
}

if ($password !== $passwordConfirm) {
    jsonResponse([
        'ok' => false,
        'error' => 'Passwords do not match.',
    ], 422);
    return;
}

try {
    if (userExistsByEmailOrUsername($email, $normalizedUsername)) {
        jsonResponse([
            'ok' => false,
            'error' => 'Email or username is already taken.',
        ], 409);
        return;
    }

    $user = registerUser($firstName, $lastName, $normalizedUsername, $email, $password);
    loginUser($user);
} catch (Throwable $exception) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to create account right now.',
    ], 500);
    return;
}

jsonResponse(['ok' => true]);
