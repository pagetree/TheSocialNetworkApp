<?php

declare(strict_types=1);

const APP_DEFAULT_LOCALE = 'en';
const APP_LOCALE_COOKIE = 'app_lang';
const APP_LOCALE_COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

/** @var list<string> */
const APP_SUPPORTED_LOCALES = ['en', 'es'];

/** @var array<string, string> */
const APP_LOCALE_LABELS = [
    'en' => 'English',
    'es' => 'Español',
];

/** @var array<string, array<string, mixed>> */
$appTranslationCache = [];

/** @var string|null */
$appActiveLocale = null;

function normalizeAppLocale(?string $locale): ?string
{
    $locale = strtolower(trim((string) $locale));

    if ($locale === '') {
        return null;
    }

    if (str_contains($locale, '-')) {
        $locale = explode('-', $locale, 2)[0];
    }

    if ($locale === 'es') {
        return 'es';
    }

    if ($locale === 'en') {
        return 'en';
    }

    return null;
}

function detectLocaleFromAcceptLanguage(): string
{
    $header = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    if ($header === '') {
        return APP_DEFAULT_LOCALE;
    }

    foreach (explode(',', $header) as $part) {
        $tag = trim(explode(';', $part, 2)[0]);
        $normalized = normalizeAppLocale($tag);
        if ($normalized !== null) {
            return $normalized;
        }
    }

    return APP_DEFAULT_LOCALE;
}

function readAppLocaleCookie(): ?string
{
    $raw = $_COOKIE[APP_LOCALE_COOKIE] ?? null;

    return is_string($raw) ? normalizeAppLocale($raw) : null;
}

function setAppLocaleCookie(string $locale): void
{
    $locale = normalizeAppLocale($locale) ?? APP_DEFAULT_LOCALE;
    $secure = str_starts_with(strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['HTTPS'] ?? '')), 'https')
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    setcookie(APP_LOCALE_COOKIE, $locale, [
        'expires' => time() + APP_LOCALE_COOKIE_MAX_AGE,
        'path' => '/',
        'secure' => $secure,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);

    $_COOKIE[APP_LOCALE_COOKIE] = $locale;
}

function initAppLocale(): void
{
    global $appActiveLocale;

    if (isset($_GET['lang'])) {
        $requested = normalizeAppLocale((string) $_GET['lang']);
        if ($requested !== null) {
            setAppLocaleCookie($requested);
            $appActiveLocale = $requested;

            return;
        }
    }

    $cookieLocale = readAppLocaleCookie();
    if ($cookieLocale !== null) {
        $appActiveLocale = $cookieLocale;

        return;
    }

    $appActiveLocale = detectLocaleFromAcceptLanguage();
}

function appLocale(): string
{
    global $appActiveLocale;

    if (!is_string($appActiveLocale) || $appActiveLocale === '') {
        initAppLocale();
    }

    return $appActiveLocale ?? APP_DEFAULT_LOCALE;
}

function appHtmlLang(): string
{
    return appLocale();
}

/**
 * @return array<string, mixed>
 */
function loadLocaleDictionary(string $locale): array
{
    global $appTranslationCache;

    if (isset($appTranslationCache[$locale])) {
        return $appTranslationCache[$locale];
    }

    $path = dirname(__DIR__) . '/lang/' . $locale . '.php';
    if (!is_file($path)) {
        $appTranslationCache[$locale] = [];

        return [];
    }

    /** @var array<string, mixed> $dictionary */
    $dictionary = require $path;
    $appTranslationCache[$locale] = $dictionary;

    return $dictionary;
}

/**
 * @param array<string, mixed> $dictionary
 */
function translationLookup(array $dictionary, string $key): ?string
{
    if (array_key_exists($key, $dictionary) && is_string($dictionary[$key])) {
        return $dictionary[$key];
    }

    $segments = explode('.', $key);
    $node = $dictionary;

    foreach ($segments as $segment) {
        if (!is_array($node) || !array_key_exists($segment, $node)) {
            return null;
        }

        $node = $node[$segment];
    }

    return is_string($node) ? $node : null;
}

/**
 * @param array<string, string|int|float> $replacements
 */
function applyTranslationReplacements(string $text, array $replacements): string
{
    foreach ($replacements as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }

    return $text;
}

/**
 * @param array<string, string|int|float> $replacements
 */
function __(string $key, array $replacements = []): string
{
    $locale = appLocale();
    $text = translationLookup(loadLocaleDictionary($locale), $key);

    if ($text === null && $locale !== APP_DEFAULT_LOCALE) {
        $text = translationLookup(loadLocaleDictionary(APP_DEFAULT_LOCALE), $key);
    }

    if ($text === null) {
        return $key;
    }

    return applyTranslationReplacements($text, $replacements);
}

/**
 * @param array<string, string|int|float> $replacements
 */
function __e(string $key, array $replacements = []): string
{
    return htmlspecialchars(__($key, $replacements), ENT_QUOTES, 'UTF-8');
}

function __n(string $key, int $count, array $replacements = []): string
{
    $replacements['count'] = $count;

    return __($count === 1 ? $key : $key . '_plural', $replacements);
}

function translateInterestGroupTitle(string $title): string
{
    $map = [
        'Technology & Digital' => 'onboarding.interest_groups.technology_digital',
        'Arts & Entertainment' => 'onboarding.interest_groups.arts_entertainment',
        'Sports & Outdoors' => 'onboarding.interest_groups.sports_outdoors',
        'Food & Lifestyle' => 'onboarding.interest_groups.food_lifestyle',
        'Learning & Society' => 'onboarding.interest_groups.learning_society',
        'Creativity & Hobbies' => 'onboarding.interest_groups.creativity_hobbies',
        'Wellness & Community' => 'onboarding.interest_groups.wellness_community',
        'More interests' => 'onboarding.interest_groups.more_interests',
    ];

    $translationKey = $map[$title] ?? null;

    return $translationKey !== null ? __($translationKey) : $title;
}

/**
 * Maps legacy English API/UI messages to translation keys.
 */
function translateUserMessage(string $message): string
{
    static $catalog = null;

    if ($catalog === null) {
        $catalog = [
            'You must be signed in.' => 'api.sign_in_required',
            'Session expired. Refresh the page and try again.' => 'api.session_expired',
            'Session expired. Refresh and try again.' => 'api.session_expired_short',
            'Too many attempts. Try again later.' => 'api.rate_limited',
            'Unable to process request.' => 'api.request_failed',
            'Email or username and password are required.' => 'auth.errors.credentials_required',
            'Unable to sign in.' => 'auth.errors.sign_in_failed',
            'Unable to sign in right now.' => 'auth.errors.sign_in_unavailable',
            'Invalid email/username or password.' => 'auth.errors.invalid_credentials',
            'All fields are required.' => 'auth.errors.all_fields_required',
            'Email or username is already taken.' => 'auth.errors.email_username_taken',
            'Unable to create account.' => 'auth.errors.register_failed',
            'Unable to create account right now.' => 'auth.errors.register_unavailable',
            'Username is required.' => 'auth.username_status.required',
            'Username must be at least 3 characters (letters, numbers, underscore).' => 'auth.username_status.min_length',
            'Username must be 50 characters or less.' => 'auth.username_status.too_long',
            'Username is already taken.' => 'auth.username_status.taken',
            'Username is not valid.' => 'auth.username_status.invalid',
            'Unable to check username right now.' => 'auth.username_status.check_failed',
            'Onboarding is already complete.' => 'api.onboarding_complete',
            'Unable to finish onboarding.' => 'api.finish_onboarding_failed',
            'Unable to save bio.' => 'api.save_bio_failed',
            'Unable to save profile photo.' => 'api.save_avatar_failed',
            'Unable to save interests.' => 'api.save_interests_failed',
            'Invalid interests.' => 'api.invalid_interests',
            'Invalid accounts.' => 'api.invalid_accounts',
            'Unable to update follows.' => 'api.update_follows_failed',
            'Invalid post.' => 'api.invalid_post',
            'Invalid stat event.' => 'api.invalid_stat_event',
            'Invalid user.' => 'api.invalid_user',
            'Invalid request.' => 'api.invalid_request',
            'Unable to update like right now.' => 'api.like_failed',
            'Unable to submit report right now.' => 'api.report_failed',
            'Unable to record stat right now.' => 'api.stats_failed',
            'Unable to update profile right now.' => 'api.profile_update_failed',
            'Unable to update follow right now.' => 'api.follow_update_failed',
            'You cannot follow yourself.' => 'api.follow_self',
            'User not found.' => 'api.user_not_found',
            'Unable to post reply right now.' => 'api.post_reply_failed',
            'Specify either a post or a reply to remove.' => 'api.specify_remove_target',
            'Write a reply or add media before posting.' => 'reply.errors.body_or_media_required',
            'Write a reply before posting.' => 'reply.errors.body_required',
            'Write something before posting.' => 'composer.errors.body_required',
            'Write something or add media before posting.' => 'composer.errors.body_or_media_required',
            'Invalid report target.' => 'report.errors.invalid_target',
            'Choose a reason for your report.' => 'report.errors.reason_required',
            'Please describe the issue.' => 'report.errors.details_required',
            'This content is no longer available.' => 'api.content_unavailable',
            'You cannot report your own content.' => 'api.report_own_content',
            'You already submitted a report for this.' => 'api.report_duplicate',
            'Post not found.' => 'errors.post_not_found',
            'Reply not found.' => 'reply.errors.not_found',
            'Invalid reply.' => 'reply.errors.invalid',
            'Unable to load stats right now.' => 'stats.errors.load_failed',
            'Unable to remove right now.' => 'post.errors.remove_failed',
            'Unable to remove post right now.' => 'post.errors.remove_post_failed',
            'Unable to remove reply right now.' => 'post.errors.remove_reply_failed',
            'Unable to create post.' => 'composer.errors.create_failed',
            'Unable to create post right now.' => 'composer.errors.create_unavailable',
            'Name is required.' => 'profile.errors.name_required',
            'Enter a valid website URL.' => 'profile.errors.website_invalid',
            'Website must use http or https.' => 'profile.errors.website_scheme',
            'Enter a valid date of birth.' => 'profile.errors.dob_invalid',
            'Date of birth cannot be in the future.' => 'profile.errors.dob_future',
            'You must be at least 13 years old.' => 'profile.errors.dob_min_age',
            'No media uploaded.' => 'media.no_upload',
            'Media file is too large.' => 'media.file_too_large',
            'Media upload failed.' => 'media.upload_failed',
            'Invalid media upload.' => 'media.invalid_upload',
            'Invalid media file.' => 'media.invalid_file',
            'Unsupported media type.' => 'common.unsupported_media',
            'Media type does not match file contents.' => 'media.type_mismatch',
            'No file uploaded.' => 'media.no_file',
            'Image is too large.' => 'media.image_too_large',
            'Image upload failed.' => 'media.image_upload_failed',
            'Invalid upload.' => 'media.invalid_upload_generic',
            'Unsupported image type.' => 'media.unsupported_image_type',
            'Invalid image file.' => 'media.invalid_image_file',
            'Image must be 5 MB or smaller.' => 'media.image_max_5mb',
            'Image type does not match file contents.' => 'media.image_type_mismatch',
            'Unable to read uploaded file.' => 'media.read_failed',
            'File storage is not configured.' => 'media.storage_not_configured',
            'Unable to upload image right now.' => 'media.upload_image_failed',
            'Unable to upload media right now.' => 'media.upload_media_failed',
            'Add either images or a video, not both.' => 'common.mixed_media',
        ];
    }

    $trimmed = trim($message);
    if ($trimmed === '') {
        return $message;
    }

    $key = $catalog[$trimmed] ?? null;
    if ($key !== null) {
        return __($key);
    }

    if (preg_match('/^Additional details must be (\d+) characters or fewer\.$/', $trimmed, $matches) === 1) {
        return __('report.errors.details_too_long', ['max' => $matches[1]]);
    }

    if (preg_match('/^Post must be (\d+) characters or less\.$/', $trimmed, $matches) === 1) {
        return __('composer.errors.too_long', ['max' => $matches[1]]);
    }

    if (preg_match('/^Reply must be (\d+) characters or less\.$/', $trimmed, $matches) === 1) {
        return __('reply.errors.too_long', ['max' => $matches[1]]);
    }

    if (preg_match('/^Location must be (\d+) characters or less\.$/', $trimmed, $matches) === 1) {
        return __('api.location_too_long', ['max' => $matches[1]]);
    }

    if (preg_match('/^Posts can include at most (\d+) video\.$/', $trimmed, $matches) === 1) {
        return __('common.max_video', ['max' => $matches[1]]);
    }

    if (preg_match('/^Posts can include at most (\d+) images\.$/', $trimmed, $matches) === 1) {
        return __('common.max_images', ['max' => $matches[1]]);
    }

    if (preg_match('/^Media must be (.+) or smaller\.$/', $trimmed, $matches) === 1) {
        return __('media.must_be_limit', ['limit' => $matches[1]]);
    }

    if (preg_match('/^Choose up to (\d+) interests\.$/', $trimmed, $matches) === 1) {
        return __('api.interests_max', ['max' => $matches[1]]);
    }

    if (preg_match('/^Name must be (\d+) characters or less\.$/', $trimmed, $matches) === 1) {
        return __('profile.errors.name_too_long', ['max' => $matches[1]]);
    }

    if (preg_match('/^Bio must be (\d+) characters or less\.$/', $trimmed, $matches) === 1) {
        return __('profile.errors.bio_too_long', ['max' => $matches[1]]);
    }

    if (preg_match('/^Location must be (\d+) characters or less\.$/', $trimmed, $matches) === 1) {
        return __('profile.errors.location_too_long', ['max' => $matches[1]]);
    }

    if (preg_match('/^Website must be (\d+) characters or less\.$/', $trimmed, $matches) === 1) {
        return __('profile.errors.website_too_long', ['max' => $matches[1]]);
    }

    return $message;
}

function localeSwitchUrl(string $locale): string
{
    $locale = normalizeAppLocale($locale) ?? APP_DEFAULT_LOCALE;

    return appPaths()['url']('/lang/' . $locale);
}

function localeRedirectTarget(): string
{
    $paths = appPaths();
    $url = $paths['url'];
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');

    if ($referer !== '') {
        $refererPath = parse_url($referer, PHP_URL_PATH);
        if (is_string($refererPath) && $refererPath !== '') {
            $basePath = $paths['base'];
            $appPath = $refererPath;
            if ($basePath !== '' && str_starts_with($refererPath, $basePath)) {
                $appPath = substr($refererPath, strlen($basePath)) ?: '/';
            }

            $appPath = normalizeAppPath($appPath);
            if (!str_starts_with($appPath, '/lang/')) {
                return $url($appPath);
            }
        }
    }

    return $url('/');
}

/**
 * @return array<string, string>
 */
/**
 * @param array<string, mixed> $node
 * @return array<string, string>
 */
function flattenTranslationTree(array $node, string $prefix = ''): array
{
    $translations = [];

    foreach ($node as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

        if (is_array($value)) {
            $translations = array_merge($translations, flattenTranslationTree($value, $path));
            continue;
        }

        if (is_string($value)) {
            $translations[$path] = $value;
        }
    }

    return $translations;
}

function contentReportReasonLabel(string $code): string
{
    return __('report.reasons.' . strtolower(trim($code)));
}

function appJsTranslations(): array
{
    $locale = appLocale();
    $dictionary = loadLocaleDictionary($locale);
    $jsTree = $dictionary['js'] ?? [];
    $translations = is_array($jsTree) ? flattenTranslationTree($jsTree) : [];

    if ($locale === APP_DEFAULT_LOCALE) {
        return $translations;
    }

    $fallbackTree = loadLocaleDictionary(APP_DEFAULT_LOCALE)['js'] ?? [];
    if (!is_array($fallbackTree)) {
        return $translations;
    }

    return array_merge(flattenTranslationTree($fallbackTree), $translations);
}

function renderAppI18nScript(): void
{
    $payload = json_encode([
        'locale' => appLocale(),
        'defaultLocale' => APP_DEFAULT_LOCALE,
        'strings' => appJsTranslations(),
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    echo '<script>window.APP_I18N=' . $payload . ';</script>' . "\n";
}
