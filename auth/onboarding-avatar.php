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

$payload = $_POST;
$guardError = guardAuthRequest('onboarding.avatar', 'onboarding', $payload);
if ($guardError !== null) {
    jsonResponse(['ok' => false, 'error' => $guardError['error']], $guardError['status']);
    return;
}

$userId = (int) $sessionUser['id'];
$currentUser = fetchUserById($userId) ?? $sessionUser;
$avatarUrl = (string) ($currentUser['avatar_url'] ?? '');
$presetUrl = trim((string) ($payload['preset_avatar_url'] ?? ''));

if ($presetUrl !== '') {
    if (!isAllowedOnboardingAvatarUrl($presetUrl)) {
        jsonResponse(['ok' => false, 'error' => 'Choose a valid profile photo.'], 422);
        return;
    }

    $avatarUrl = $presetUrl;
} else {
    $avatarUpload = r2UploadUserFile($userId, 'avatar');
    if (!$avatarUpload['ok']) {
        jsonResponse([
            'ok' => false,
            'error' => $avatarUpload['error'] ?? 'Upload a profile photo.',
        ], 422);
        return;
    }

    r2DeleteObjectByUrl($avatarUrl === '' ? null : $avatarUrl);
    $avatarUrl = $avatarUpload['url'];
}

try {
    $updatedUser = updateUserOnboardingAvatar($userId, $avatarUrl);
} catch (Throwable) {
    jsonResponse(['ok' => false, 'error' => 'Unable to save profile photo.'], 500);
    return;
}

if ($updatedUser === null) {
    jsonResponse(['ok' => false, 'error' => 'Unable to save profile photo.'], 500);
    return;
}

loginUser($updatedUser);
$appPaths = appPaths();

jsonResponse([
    'ok' => true,
    'user' => userProfilePayload($updatedUser, $appPaths['url']),
]);
