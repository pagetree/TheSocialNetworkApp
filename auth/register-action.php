<?php

declare(strict_types=1);

$payload = authPayloadFromRequest();

$guardError = guardAuthRequest('auth.register', 'register', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$firstName = trim((string) ($payload['first_name'] ?? ''));
$lastName = trim((string) ($payload['last_name'] ?? ''));
$username = trim((string) ($payload['username'] ?? ''));
$email = strtolower(trim((string) ($payload['email'] ?? '')));
$password = (string) ($payload['password'] ?? '');

if ($firstName === '' || $lastName === '' || $username === '' || $email === '' || $password === '') {
    jsonResponse([
        'ok' => false,
        'error' => 'All fields are required.',
    ], 422);
    return;
}

$normalizedUsername = normalizeUsername($username);
$usernameCheck = checkUsernameAvailability($normalizedUsername);
if (!$usernameCheck['valid'] || !$usernameCheck['available']) {
    jsonResponse([
        'ok' => false,
        'error' => $usernameCheck['error'] ?? 'Choose a valid username.',
    ], 422);
    return;
}

$emailError = validateRegistrationEmail($email);
if ($emailError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $emailError,
    ], 422);
    return;
}

$passwordError = validateRegistrationPassword($password, $normalizedUsername, $email);
if ($passwordError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $passwordError,
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
    invalidateCsrfTokens('register');
} catch (Throwable $exception) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to create account right now.',
    ], 500);
    return;
}

jsonResponse(['ok' => true]);
