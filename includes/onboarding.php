<?php

declare(strict_types=1);

const ONBOARDING_MAX_INTERESTS = 10;
const ONBOARDING_SUGGESTION_LIMIT = 12;
const ONBOARDING_MAX_BULK_FOLLOWS = 20;

/**
 * @return list<array{key: string, label: string, path: string}>
 */
function onboardingSteps(): array
{
    return [
        ['key' => 'welcome', 'label' => __('onboarding.steps.welcome'), 'path' => '/onboarding/welcome'],
        ['key' => 'avatar', 'label' => __('onboarding.steps.avatar'), 'path' => '/onboarding/avatar'],
        ['key' => 'bio', 'label' => __('onboarding.steps.bio'), 'path' => '/onboarding/bio'],
        ['key' => 'interests', 'label' => __('onboarding.steps.interests'), 'path' => '/onboarding/interests'],
        ['key' => 'suggestions', 'label' => __('onboarding.steps.suggestions'), 'path' => '/onboarding/suggestions'],
    ];
}

function onboardingStepKeys(): array
{
    return array_column(onboardingSteps(), 'key');
}

/**
 * @return array{key: string, label: string, path: string}|null
 */
function onboardingPreviousStep(string $currentStep): ?array
{
    $steps = onboardingSteps();
    foreach ($steps as $index => $step) {
        if ($step['key'] === $currentStep) {
            return $index > 0 ? $steps[$index - 1] : null;
        }
    }

    return null;
}

function normalizeOnboardingStep(?string $step): string
{
    $step = strtolower(trim((string) $step));
    $keys = onboardingStepKeys();

    return in_array($step, $keys, true) ? $step : $keys[0];
}

/**
 * @param array<string, mixed>|null $user
 */
function userNeedsOnboarding(?array $user): bool
{
    if (!is_array($user)) {
        return false;
    }

    $completedAt = $user['onboarding_completed_at'] ?? null;

    return $completedAt === null || $completedAt === '';
}

/**
 * @return array<string, list<string>>
 */
function interestGroupCatalog(): array
{
    return [
        'Technology & Digital' => [
            'technology',
            'ai',
            'startups',
            'crypto',
            'content-creation',
            'gaming',
            'esports',
        ],
        'Arts & Entertainment' => [
            'design',
            'photography',
            'photography-video',
            'art',
            'writing',
            'music',
            'movies-tv',
            'anime',
            'comedy',
            'podcasts',
            'dance',
        ],
        'Sports & Outdoors' => [
            'sports',
            'fitness',
            'outdoors',
        ],
        'Food & Lifestyle' => [
            'travel',
            'food',
            'cooking',
            'fashion',
            'beauty',
            'home-garden',
            'cars',
            'pets',
            'family',
            'dating',
        ],
        'Learning & Society' => [
            'books',
            'science',
            'business',
            'finance',
            'career',
            'education',
            'history',
            'languages',
            'news',
            'politics',
        ],
        'Creativity & Hobbies' => [
            'diy',
            'crafts',
            'board-games',
        ],
        'Wellness & Community' => [
            'health',
            'environment',
            'volunteering',
            'spirituality',
        ],
    ];
}

/**
 * @param list<array{id: int, slug: string, label: string}> $interests
 * @return list<array{title: string, interests: list<array{id: int, slug: string, label: string}>}>
 */
function groupOnboardingInterests(array $interests): array
{
    $catalog = interestGroupCatalog();
    $slugToGroup = [];
    foreach ($catalog as $title => $slugs) {
        foreach ($slugs as $slug) {
            $slugToGroup[$slug] = $title;
        }
    }

    $buckets = [];
    foreach ($catalog as $title => $slugs) {
        $buckets[$title] = [];
    }
    $otherTitle = 'More interests';
    $other = [];

    foreach ($interests as $interest) {
        $slug = (string) ($interest['slug'] ?? '');
        $groupTitle = $slugToGroup[$slug] ?? null;
        if ($groupTitle !== null) {
            $buckets[$groupTitle][] = $interest;
            continue;
        }
        $other[] = $interest;
    }

    $grouped = [];
    foreach ($catalog as $title => $slugs) {
        if ($buckets[$title] === []) {
            continue;
        }
        $grouped[] = [
            'title' => translateInterestGroupTitle($title),
            'interests' => $buckets[$title],
        ];
    }

    if ($other !== []) {
        $grouped[] = [
            'title' => translateInterestGroupTitle($otherTitle),
            'interests' => $other,
        ];
    }

    return $grouped;
}

/**
 * @return list<array{id: int, slug: string, label: string}>
 */
function fetchActiveInterests(): array
{
    $pdo = createPdoConnection();
    $stmt = $pdo->query(
        'SELECT id, slug, label
         FROM interests
         WHERE is_active = TRUE
         ORDER BY sort_order ASC, label ASC'
    );

    $interests = [];
    while ($row = $stmt->fetch()) {
        $interests[] = [
            'id' => (int) $row['id'],
            'slug' => (string) $row['slug'],
            'label' => (string) $row['label'],
        ];
    }

    return $interests;
}

/**
 * @return list<int>
 */
function fetchUserInterestIds(int $userId): array
{
    if ($userId < 1) {
        return [];
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT interest_id
         FROM user_interests
         WHERE user_id = :user_id
         ORDER BY interest_id ASC'
    );
    $stmt->execute(['user_id' => $userId]);

    $ids = [];
    while ($row = $stmt->fetch()) {
        $ids[] = (int) $row['interest_id'];
    }

    return $ids;
}

/**
 * @param list<int> $interestIds
 */
function replaceUserInterests(int $userId, array $interestIds): void
{
    if ($userId < 1) {
        throw new InvalidArgumentException('Invalid user.');
    }

    $interestIds = array_values(array_unique(array_filter(
        array_map('intval', $interestIds),
        static fn (int $id): bool => $id > 0
    )));

    if (count($interestIds) > ONBOARDING_MAX_INTERESTS) {
        throw new InvalidArgumentException(
            'Choose up to ' . ONBOARDING_MAX_INTERESTS . ' interests.'
        );
    }

    $pdo = createPdoConnection();

    if ($interestIds !== []) {
        $placeholders = implode(', ', array_fill(0, count($interestIds), '?'));
        $validStmt = $pdo->prepare(
            'SELECT id FROM interests WHERE is_active = TRUE AND id IN (' . $placeholders . ')'
        );
        $validStmt->execute($interestIds);
        $validIds = [];
        while ($row = $validStmt->fetch()) {
            $validIds[] = (int) $row['id'];
        }
        sort($validIds);
        $interestIds = $validIds;
    }

    $pdo->beginTransaction();
    try {
        $delete = $pdo->prepare('DELETE FROM user_interests WHERE user_id = :user_id');
        $delete->execute(['user_id' => $userId]);

        if ($interestIds !== []) {
            $insert = $pdo->prepare(
                'INSERT INTO user_interests (user_id, interest_id)
                 VALUES (:user_id, :interest_id)'
            );
            foreach ($interestIds as $interestId) {
                $insert->execute([
                    'user_id' => $userId,
                    'interest_id' => $interestId,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

/**
 * @return array<string, mixed>|null
 */
function updateUserOnboardingAvatar(int $userId, string $avatarUrl): ?array
{
    $currentUser = fetchUserById($userId);
    if ($currentUser === null) {
        return null;
    }

    $displayName = (string) ($currentUser['display_name'] ?? '');

    return updateUserProfile($userId, [
        'display_name' => $displayName,
        'bio' => $currentUser['bio'] ?? null,
        'location' => $currentUser['location'] ?? null,
        'website_url' => $currentUser['website_url'] ?? null,
        'date_of_birth' => $currentUser['date_of_birth'] ?? null,
        'avatar_url' => $avatarUrl === '' ? null : $avatarUrl,
        'cover_url' => $currentUser['cover_url'] ?? null,
        'is_visible' => userProfileIsVisible($currentUser),
    ]);
}

/**
 * @return array<string, mixed>|null
 */
function updateUserOnboardingBio(int $userId, string $bio): ?array
{
    $bioError = validateProfileBio($bio);
    if ($bioError !== null) {
        throw new InvalidArgumentException($bioError);
    }

    $currentUser = fetchUserById($userId);
    if ($currentUser === null) {
        return null;
    }

    $sanitizedBio = sanitizeProfileText($bio);

    return updateUserProfile($userId, [
        'display_name' => (string) ($currentUser['display_name'] ?? ''),
        'bio' => $sanitizedBio === '' ? null : $sanitizedBio,
        'location' => $currentUser['location'] ?? null,
        'website_url' => $currentUser['website_url'] ?? null,
        'date_of_birth' => $currentUser['date_of_birth'] ?? null,
        'avatar_url' => $currentUser['avatar_url'] ?? null,
        'cover_url' => $currentUser['cover_url'] ?? null,
        'is_visible' => userProfileIsVisible($currentUser),
    ]);
}

/**
 * @return list<array<string, mixed>>
 */
function fetchOnboardingSuggestions(int $userId, int $limit = ONBOARDING_SUGGESTION_LIMIT): array
{
    if ($userId < 1) {
        return [];
    }

    $limit = max(1, min($limit, ONBOARDING_SUGGESTION_LIMIT));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT
            u.id,
            u.username,
            u.display_name,
            u.handle,
            u.avatar_url,
            u.bio,
            COALESCE(shared.shared_count, 0) AS shared_interests,
            COALESCE(followers.follower_count, 0) AS follower_count
         FROM users u
         LEFT JOIN LATERAL (
             SELECT COUNT(*)::int AS shared_count
             FROM user_interests ui_viewer
             INNER JOIN user_interests ui_target
                 ON ui_target.interest_id = ui_viewer.interest_id
                AND ui_target.user_id = u.id
             WHERE ui_viewer.user_id = :viewer_id
         ) shared ON TRUE
         LEFT JOIN LATERAL (
             SELECT COUNT(*)::int AS follower_count
             FROM user_follows uf
             WHERE uf.following_id = u.id
         ) followers ON TRUE
         WHERE u.id <> :viewer_id
           AND u.is_visible = TRUE
           AND u.onboarding_completed_at IS NOT NULL
         ORDER BY shared.shared_count DESC, followers.follower_count DESC, u.created_at DESC
         LIMIT :limit'
    );
    $stmt->execute([
        'viewer_id' => $userId,
        'limit' => $limit,
    ]);

    $suggestions = [];
    while ($row = $stmt->fetch()) {
        $suggestions[] = $row;
    }

    return $suggestions;
}

/**
 * @param list<int> $targetUserIds
 */
function unfollowUsersOnboarding(int $followerId, array $targetUserIds): int
{
    $targetUserIds = array_values(array_unique(array_filter(
        array_map('intval', $targetUserIds),
        static fn (int $id): bool => $id > 0 && $id !== $followerId
    )));

    if ($targetUserIds === []) {
        return 0;
    }

    if (count($targetUserIds) > ONBOARDING_MAX_BULK_FOLLOWS) {
        throw new InvalidArgumentException(
            'You can update up to ' . ONBOARDING_MAX_BULK_FOLLOWS . ' accounts at once.'
        );
    }

    $unfollowed = 0;
    foreach ($targetUserIds as $targetUserId) {
        if (!isUserFollowedBy($followerId, $targetUserId)) {
            continue;
        }

        $result = toggleUserFollow($followerId, $targetUserId);
        if ($result['ok'] && !($result['following'] ?? true)) {
            $unfollowed++;
        }
    }

    return $unfollowed;
}

/**
 * @param list<int> $targetUserIds
 */
function followUsersOnboarding(int $followerId, array $targetUserIds): int
{
    $targetUserIds = array_values(array_unique(array_filter(
        array_map('intval', $targetUserIds),
        static fn (int $id): bool => $id > 0 && $id !== $followerId
    )));

    if ($targetUserIds === []) {
        return 0;
    }

    if (count($targetUserIds) > ONBOARDING_MAX_BULK_FOLLOWS) {
        throw new InvalidArgumentException(
            'You can follow up to ' . ONBOARDING_MAX_BULK_FOLLOWS . ' accounts at once.'
        );
    }

    $followed = 0;
    foreach ($targetUserIds as $targetUserId) {
        if (isUserFollowedBy($followerId, $targetUserId)) {
            continue;
        }

        $result = toggleUserFollow($followerId, $targetUserId);
        if ($result['ok'] && ($result['following'] ?? false)) {
            $followed++;
        }
    }

    return $followed;
}

/**
 * @return array<string, mixed>|null
 */
function completeUserOnboarding(int $userId): ?array
{
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'UPDATE users
         SET onboarding_completed_at = NOW(),
             updated_at = NOW()
         WHERE id = :id
           AND onboarding_completed_at IS NULL
         RETURNING ' . userSessionSelectSql()
    );
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if ($user !== false) {
        return $user;
    }

    return fetchUserById($userId);
}

/**
 * @param callable(string): string $url
 */
function onboardingRedirectUrlIfNeeded(string $path, callable $url): ?string
{
    if (!isLoggedIn()) {
        return null;
    }

    $user = getCurrentUser();
    if (!userNeedsOnboarding($user)) {
        return null;
    }

    if (str_starts_with($path, '/onboarding')) {
        return null;
    }

    if (
        str_starts_with($path, '/auth/')
        || $path === '/health'
        || $path === '/favicon.ico'
    ) {
        return null;
    }

    return $url('/onboarding/welcome');
}
