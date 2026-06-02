<?php

declare(strict_types=1);

const POST_BODY_MAX_LENGTH = 300;
const POST_LOCATION_MAX_LENGTH = 120;
const POST_FEED_DEFAULT_LIMIT = 50;

function sanitizePostText(string $value): string
{
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

    return trim($value);
}

function validatePostBody(string $body): ?string
{
    $body = sanitizePostText($body);

    if ($body === '') {
        return 'Write something before posting.';
    }

    if (mb_strlen($body) > POST_BODY_MAX_LENGTH) {
        return 'Post must be ' . POST_BODY_MAX_LENGTH . ' characters or less.';
    }

    return null;
}

function validatePostBodyForCreate(string $body, bool $hasMedia): ?string
{
    $body = sanitizePostText($body);

    if ($body === '' && !$hasMedia) {
        return 'Write something or add media before posting.';
    }

    if ($body !== '' && mb_strlen($body) > POST_BODY_MAX_LENGTH) {
        return 'Post must be ' . POST_BODY_MAX_LENGTH . ' characters or less.';
    }

    return null;
}

function validatePostLocationLabel(string $locationLabel): ?string
{
    $locationLabel = sanitizePostText($locationLabel);

    if (mb_strlen($locationLabel) > POST_LOCATION_MAX_LENGTH) {
        return 'Location must be ' . POST_LOCATION_MAX_LENGTH . ' characters or less.';
    }

    return null;
}

function normalizePostBody(string $body): string
{
    return sanitizePostText($body);
}

function normalizePostLocationLabel(string $locationLabel): ?string
{
    $locationLabel = sanitizePostText($locationLabel);

    return $locationLabel === '' ? null : $locationLabel;
}

/**
 * @return array<string, mixed>|null
 */
function createPost(int $userId, ?string $body, ?string $locationLabel = null): ?array
{
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO posts (user_id, body, location_label)
         VALUES (:user_id, :body, :location_label)
         RETURNING id, user_id, body, location_label,
                   reply_count, repost_count, like_count, view_count, interaction_count, created_at'
    );
    $stmt->execute([
        'user_id' => $userId,
        'body' => $body,
        'location_label' => $locationLabel,
    ]);
    $post = $stmt->fetch();

    return $post === false ? null : $post;
}

/**
 * @param list<array{url: string, media_type: string}> $mediaRecords
 */
function attachPostMediaRecords(int $postId, array $mediaRecords): void
{
    if ($mediaRecords === []) {
        return;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO post_media (post_id, media_url, media_type, sort_order)
         VALUES (:post_id, :media_url, :media_type, :sort_order)'
    );

    foreach ($mediaRecords as $sortOrder => $record) {
        $stmt->execute([
            'post_id' => $postId,
            'media_url' => $record['url'],
            'media_type' => $record['media_type'],
            'sort_order' => $sortOrder,
        ]);
    }
}

/**
 * @param list<int> $postIds
 * @return array<int, list<array<string, mixed>>>
 */
function fetchPostMediaGroupedByPostIds(array $postIds): array
{
    $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds), static fn (int $id): bool => $id > 0)));
    if ($postIds === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($postIds), '?'));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT id, post_id, media_url, media_type, sort_order, created_at
         FROM post_media
         WHERE post_id IN (' . $placeholders . ')
         ORDER BY post_id ASC, sort_order ASC, id ASC'
    );
    $stmt->execute($postIds);

    $grouped = [];
    while ($row = $stmt->fetch()) {
        $postId = (int) $row['post_id'];
        $grouped[$postId][] = $row;
    }

    return $grouped;
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function hydrateFeedPostsWithMedia(array $rows): array
{
    $postIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows);
    $grouped = fetchPostMediaGroupedByPostIds($postIds);

    foreach ($rows as &$row) {
        $postId = (int) ($row['id'] ?? 0);
        $row['media_items'] = $grouped[$postId] ?? [];
    }
    unset($row);

    return $rows;
}

function deletePostForUser(int $postId, int $userId): void
{
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'DELETE FROM posts
         WHERE id = :id
           AND user_id = :user_id'
    );
    $stmt->execute([
        'id' => $postId,
        'user_id' => $userId,
    ]);
}

/**
 * @return list<array<string, mixed>>
 */
function fetchFeedPosts(int $limit = POST_FEED_DEFAULT_LIMIT): array
{
    $limit = max(1, min($limit, 100));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT p.id, p.user_id, p.body, p.location_label,
                p.reply_count, p.repost_count, p.like_count, p.view_count, p.interaction_count, p.created_at,
                u.display_name, u.handle, u.avatar_url
         FROM posts p
         INNER JOIN users u ON u.id = p.user_id
         WHERE p.is_deleted = FALSE
           AND p.repost_of_post_id IS NULL
         ORDER BY p.created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    return is_array($rows) ? hydrateFeedPostsWithMedia($rows) : [];
}

/**
 * @return array<string, mixed>|null
 */
function fetchPostById(int $postId): ?array
{
    if ($postId < 1) {
        return null;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT p.id, p.user_id, p.body, p.location_label,
                p.reply_count, p.repost_count, p.like_count, p.view_count, p.interaction_count, p.created_at,
                u.display_name, u.handle, u.avatar_url
         FROM posts p
         INNER JOIN users u ON u.id = p.user_id
         WHERE p.id = :id
           AND p.is_deleted = FALSE
         LIMIT 1'
    );
    $stmt->execute(['id' => $postId]);
    $row = $stmt->fetch();

    if ($row === false) {
        return null;
    }

    $hydrated = hydrateFeedPostsWithMedia([$row]);

    return $hydrated[0] ?? null;
}

function postUrl(int $postId, callable $url): string
{
    return $url('/post/' . $postId);
}

function formatPostTimeLabel(string $createdAt): string
{
    try {
        $created = new DateTimeImmutable($createdAt);
    } catch (Exception) {
        return '';
    }

    $now = new DateTimeImmutable('now');
    $seconds = $now->getTimestamp() - $created->getTimestamp();

    if ($seconds < 60) {
        return 'just now';
    }

    if ($seconds < 3600) {
        $minutes = max(1, (int) floor($seconds / 60));

        return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
    }

    if ($seconds < 86400) {
        $hours = max(1, (int) floor($seconds / 3600));

        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }

    if ($seconds < 604800) {
        $days = max(1, (int) floor($seconds / 86400));

        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }

    return $created->format('M j, Y');
}

function formatPostDetailDateLabel(string $createdAt): string
{
    try {
        $created = new DateTimeImmutable($createdAt);
    } catch (Exception) {
        return '';
    }

    return $created->format('M j, Y') . ' · ' . $created->format('g:i A');
}

function formatEngagementCount(int $count): string
{
    if ($count >= 1_000_000) {
        return rtrim(rtrim(number_format($count / 1_000_000, 1, '.', ''), '0'), '.') . 'M';
    }

    if ($count >= 1_000) {
        return rtrim(rtrim(number_format($count / 1_000, 1, '.', ''), '0'), '.') . 'K';
    }

    return (string) $count;
}

function postMediaPayloadItems(array $row): array
{
    $items = [];
    $mediaRows = $row['media_items'] ?? [];

    if (!is_array($mediaRows)) {
        return $items;
    }

    foreach ($mediaRows as $mediaRow) {
        if (!is_array($mediaRow)) {
            continue;
        }

        $items[] = [
            'id' => (int) ($mediaRow['id'] ?? 0),
            'url' => (string) ($mediaRow['media_url'] ?? ''),
            'type' => (string) ($mediaRow['media_type'] ?? ''),
            'sort_order' => (int) ($mediaRow['sort_order'] ?? 0),
        ];
    }

    return $items;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function postFeedPayload(array $row, callable $url): array
{
    $user = [
        'display_name' => (string) ($row['display_name'] ?? ''),
        'handle' => (string) ($row['handle'] ?? ''),
        'avatar_url' => (string) ($row['avatar_url'] ?? ''),
    ];
    $media = postMediaPayloadItems($row);

    return [
        'id' => (int) ($row['id'] ?? 0),
        'user_id' => (int) ($row['user_id'] ?? 0),
        'body' => (string) ($row['body'] ?? ''),
        'media' => $media,
        'location_label' => isset($row['location_label']) && $row['location_label'] !== null
            ? (string) $row['location_label']
            : null,
        'reply_count' => (int) ($row['reply_count'] ?? 0),
        'repost_count' => (int) ($row['repost_count'] ?? 0),
        'like_count' => (int) ($row['like_count'] ?? 0),
        'view_count' => (int) ($row['view_count'] ?? 0),
        'interaction_count' => (int) ($row['interaction_count'] ?? 0),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'time_label' => formatPostTimeLabel((string) ($row['created_at'] ?? '')),
        'detail_date_label' => formatPostDetailDateLabel((string) ($row['created_at'] ?? '')),
        'author' => [
            'display_name' => $user['display_name'],
            'handle' => $user['handle'],
            'avatar_url' => userMediaUrl($user, 'avatar_url', $url),
        ],
    ];
}

/**
 * @param array<string, mixed> $row
 */
function renderPostCard(array $row, callable $url, int $currentUserId = 0, bool $viewerLiked = false): void
{
    $post = postFeedPayload($row, $url);
    $post['post_url'] = postUrl((int) $post['id'], $url);
    $post['viewer_liked'] = $viewerLiked;
    require __DIR__ . '/posts/post-card.php';
}
