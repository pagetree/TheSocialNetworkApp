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
function createPost(int $userId, string $body, ?string $locationLabel = null): ?array
{
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO posts (user_id, body, location_label)
         VALUES (:user_id, :body, :location_label)
         RETURNING id, user_id, body, media_url, media_type, location_label,
                   reply_count, repost_count, like_count, view_count, created_at'
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
 * @return list<array<string, mixed>>
 */
function fetchFeedPosts(int $limit = POST_FEED_DEFAULT_LIMIT): array
{
    $limit = max(1, min($limit, 100));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT p.id, p.user_id, p.body, p.media_url, p.media_type, p.location_label,
                p.reply_count, p.repost_count, p.like_count, p.view_count, p.created_at,
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

    return is_array($rows) ? $rows : [];
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

    return [
        'id' => (int) ($row['id'] ?? 0),
        'body' => (string) ($row['body'] ?? ''),
        'media_url' => isset($row['media_url']) && $row['media_url'] !== null
            ? (string) $row['media_url']
            : null,
        'media_type' => isset($row['media_type']) && $row['media_type'] !== null
            ? (string) $row['media_type']
            : null,
        'location_label' => isset($row['location_label']) && $row['location_label'] !== null
            ? (string) $row['location_label']
            : null,
        'reply_count' => (int) ($row['reply_count'] ?? 0),
        'repost_count' => (int) ($row['repost_count'] ?? 0),
        'like_count' => (int) ($row['like_count'] ?? 0),
        'view_count' => (int) ($row['view_count'] ?? 0),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'time_label' => formatPostTimeLabel((string) ($row['created_at'] ?? '')),
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
function renderPostCard(array $row, callable $url): void
{
    $post = postFeedPayload($row, $url);
    require __DIR__ . '/posts/post-card.php';
}
