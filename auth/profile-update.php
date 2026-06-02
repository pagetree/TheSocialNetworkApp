<?php

declare(strict_types=1);

$sessionUser = getCurrentUser();
if ($sessionUser === null) {
    jsonResponse([
        'ok' => false,
        'error' => 'You must be signed in.',
    ], 401);
    return;
}

$payload = $_POST;
$guardError = guardAuthRequest('profile.update', 'profile_edit', $payload);
if ($guardError !== null) {
    jsonResponse([
        'ok' => false,
        'error' => $guardError['error'],
    ], $guardError['status']);
    return;
}

$displayName = (string) ($payload['display_name'] ?? '');
$bio = (string) ($payload['bio'] ?? '');
$location = (string) ($payload['location'] ?? '');
$website = (string) ($payload['website'] ?? '');
$dateOfBirth = (string) ($payload['date_of_birth'] ?? '');

$errors = [
    validateProfileDisplayName($displayName),
    validateProfileBio($bio),
    validateProfileLocation($location),
    validateProfileWebsite($website),
    validateProfileDateOfBirth($dateOfBirth),
];

foreach ($errors as $error) {
    if ($error !== null) {
        jsonResponse([
            'ok' => false,
            'error' => $error,
        ], 422);
        return;
    }
}

$currentUser = fetchUserById((int) $sessionUser['id']) ?? $sessionUser;
$userId = (int) $currentUser['id'];
$avatarUrl = (string) ($currentUser['avatar_url'] ?? '');
$coverUrl = (string) ($currentUser['cover_url'] ?? '');

$avatarUpload = r2UploadUserFile($userId, 'avatar');
if (!$avatarUpload['ok'] && ($avatarUpload['error'] ?? '') !== 'No file uploaded.') {
    jsonResponse([
        'ok' => false,
        'error' => $avatarUpload['error'],
    ], 422);
    return;
}
if ($avatarUpload['ok']) {
    r2DeleteObjectByUrl($avatarUrl === '' ? null : $avatarUrl);
    $avatarUrl = $avatarUpload['url'];
}

$coverUpload = r2UploadUserFile($userId, 'cover_image');
if (!$coverUpload['ok'] && ($coverUpload['error'] ?? '') !== 'No file uploaded.') {
    jsonResponse([
        'ok' => false,
        'error' => $coverUpload['error'],
    ], 422);
    return;
}
if ($coverUpload['ok']) {
    r2DeleteObjectByUrl($coverUrl === '' ? null : $coverUrl);
    $coverUrl = $coverUpload['url'];
}

try {
    $updatedUser = updateUserProfile($userId, [
        'display_name' => sanitizeProfileText($displayName),
        'bio' => sanitizeProfileText($bio) === '' ? null : sanitizeProfileText($bio),
        'location' => sanitizeProfileText($location) === '' ? null : sanitizeProfileText($location),
        'website_url' => normalizeProfileWebsite($website),
        'date_of_birth' => normalizeProfileDateOfBirth($dateOfBirth),
        'avatar_url' => $avatarUrl === '' ? null : $avatarUrl,
        'cover_url' => $coverUrl === '' ? null : $coverUrl,
    ]);
} catch (Throwable $exception) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to update profile right now.',
    ], 500);
    return;
}

if ($updatedUser === null) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to update profile right now.',
    ], 500);
    return;
}

loginUser($updatedUser);
invalidateCsrfTokens('profile_edit');

$appPaths = appPaths();
jsonResponse([
    'ok' => true,
    'user' => userProfilePayload($updatedUser, $appPaths['url']),
]);
