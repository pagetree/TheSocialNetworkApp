<?php

declare(strict_types=1);

const PROFILE_BIO_MAX_LENGTH = 300;
const PROFILE_DISPLAY_NAME_MAX_LENGTH = 100;
const PROFILE_LOCATION_MAX_LENGTH = 100;
const PROFILE_WEBSITE_MAX_LENGTH = 255;

/**
 * @return list<string>
 */
function userSessionColumns(): array
{
    return [
        'id',
        'username',
        'display_name',
        'handle',
        'email',
        'avatar_url',
        'cover_url',
        'bio',
        'location',
        'website_url',
        'date_of_birth',
        'created_at',
    ];
}

function userSessionSelectSql(): string
{
    return implode(', ', userSessionColumns());
}

function sanitizeProfileText(string $value): string
{
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

    return trim($value);
}

function validateProfileDisplayName(string $displayName): ?string
{
    $displayName = sanitizeProfileText($displayName);

    if ($displayName === '') {
        return 'Name is required.';
    }

    if (mb_strlen($displayName) > PROFILE_DISPLAY_NAME_MAX_LENGTH) {
        return 'Name must be ' . PROFILE_DISPLAY_NAME_MAX_LENGTH . ' characters or less.';
    }

    return null;
}

function validateProfileBio(string $bio): ?string
{
    $bio = sanitizeProfileText($bio);

    if (mb_strlen($bio) > PROFILE_BIO_MAX_LENGTH) {
        return 'Bio must be ' . PROFILE_BIO_MAX_LENGTH . ' characters or less.';
    }

    return null;
}

function validateProfileLocation(string $location): ?string
{
    $location = sanitizeProfileText($location);

    if (mb_strlen($location) > PROFILE_LOCATION_MAX_LENGTH) {
        return 'Location must be ' . PROFILE_LOCATION_MAX_LENGTH . ' characters or less.';
    }

    return null;
}

function validateProfileWebsite(string $website): ?string
{
    $website = sanitizeProfileText($website);

    if ($website === '') {
        return null;
    }

    if (mb_strlen($website) > PROFILE_WEBSITE_MAX_LENGTH) {
        return 'Website must be ' . PROFILE_WEBSITE_MAX_LENGTH . ' characters or less.';
    }

    if (!preg_match('#^https?://#i', $website)) {
        $website = 'https://' . $website;
    }

    if (!filter_var($website, FILTER_VALIDATE_URL)) {
        return 'Enter a valid website URL.';
    }

    $parts = parse_url($website);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return 'Website must use http or https.';
    }

    return null;
}

function normalizeProfileWebsite(string $website): ?string
{
    $website = sanitizeProfileText($website);

    if ($website === '') {
        return null;
    }

    if (!preg_match('#^https?://#i', $website)) {
        $website = 'https://' . $website;
    }

    $parts = parse_url($website);
    if ($parts === false) {
        return null;
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }

    return $website;
}

function validateProfileDateOfBirth(string $dateOfBirth): ?string
{
    $dateOfBirth = sanitizeProfileText($dateOfBirth);

    if ($dateOfBirth === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $dateOfBirth);
    $errors = DateTimeImmutable::getLastErrors();
    if (
        $date === false
        || ($errors['warning_count'] ?? 0) > 0
        || ($errors['error_count'] ?? 0) > 0
        || $date->format('Y-m-d') !== $dateOfBirth
    ) {
        return 'Enter a valid date of birth.';
    }

    $today = new DateTimeImmutable('today');
    if ($date > $today) {
        return 'Date of birth cannot be in the future.';
    }

    $minimumAge = $today->modify('-13 years');
    if ($date > $minimumAge) {
        return 'You must be at least 13 years old.';
    }

    $maximumAge = $today->modify('-120 years');
    if ($date < $maximumAge) {
        return 'Enter a valid date of birth.';
    }

    return null;
}

function normalizeProfileDateOfBirth(string $dateOfBirth): ?string
{
    $dateOfBirth = sanitizeProfileText($dateOfBirth);

    if ($dateOfBirth === '') {
        return null;
    }

    return validateProfileDateOfBirth($dateOfBirth) === null ? $dateOfBirth : null;
}

function isExternalMediaUrl(?string $url): bool
{
    if ($url === null || $url === '') {
        return false;
    }

    return preg_match('#^https?://#i', $url) === 1;
}

/**
 * @param array<string, mixed>|null $user
 */
function userMediaUrl(?array $user, string $field, callable $url): string
{
    $defaults = [
        'avatar_url' => 'https://pub-a912eacf8fe9461083def05076743bb3.r2.dev/assets/romeo-leaupepe-su-a-70gb9CHBX4g-unsplash.jpg',
        'cover_url' => 'https://pub-a912eacf8fe9461083def05076743bb3.r2.dev/assets/gayatri-malhotra-QTEk16LzWSI-unsplash.jpg',
    ];

    $stored = is_array($user) ? (string) ($user[$field] ?? '') : '';

    if ($stored === '') {
        return $defaults[$field] ?? '';
    }

    if (isExternalMediaUrl($stored)) {
        return $stored;
    }

    return $url($stored);
}

function formatProfileJoinedDate(?string $createdAt): string
{
    if ($createdAt === null || $createdAt === '') {
        return '';
    }

    try {
        $date = new DateTimeImmutable($createdAt);
    } catch (Exception) {
        return '';
    }

    return 'Joined ' . $date->format('F Y');
}

function formatProfileBirthdayLabel(?string $dateOfBirth): string
{
    if ($dateOfBirth === null || $dateOfBirth === '') {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $dateOfBirth);
    if ($date === false) {
        return '';
    }

    return 'Born ' . $date->format('F j, Y');
}

function websiteDisplayLabel(?string $websiteUrl): string
{
    if ($websiteUrl === null || $websiteUrl === '') {
        return '';
    }

    return (string) preg_replace('#^https?://#i', '', $websiteUrl);
}
