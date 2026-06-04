<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var string $onboardingStep */

$currentUser = getCurrentUser();
if ($currentUser === null) {
    header('Location: ' . $url('/'));
    exit;
}

if (!userNeedsOnboarding($currentUser)) {
    header('Location: ' . $url('/'));
    exit;
}

$onboardingStep = normalizeOnboardingStep($onboardingStep ?? '');
$onboardingCsrfToken = createCsrfToken('onboarding');
$onboardingInterests = fetchActiveInterests();
$userInterestIds = fetchUserInterestIds((int) $currentUser['id']);
$onboardingSuggestions = fetchOnboardingSuggestions((int) $currentUser['id']);
$suggestionIds = array_map(
    static fn (array $row): int => (int) ($row['id'] ?? 0),
    $onboardingSuggestions
);
$followedSuggestionIds = fetchFollowedUserIdsAmong((int) $currentUser['id'], $suggestionIds);

$stepFile = __DIR__ . '/steps/' . $onboardingStep . '.php';
if (!is_file($stepFile)) {
    http_response_code(404);
    return;
}

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');

$pageTitle = __('meta.onboarding_title');
$activeNav = 'explore';
$mainClass = 'onboarding-main';
$onboardingLayout = true;
$isLoggedIn = true;
$pageScripts = ['/assets/js/onboarding.js'];

require dirname(__DIR__) . '/layout/head.php';
require dirname(__DIR__) . '/layout/content-area-start.php';
require $stepFile;
require dirname(__DIR__) . '/layout/content-area-end.php';
