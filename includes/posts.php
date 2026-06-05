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

function validatePostBodyForCreate(string $body, bool $hasMedia, bool $hasQuotedPost = false): ?string
{
    $body = sanitizePostText($body);

    if ($body === '' && !$hasMedia && !$hasQuotedPost) {
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
function resolveQuotedPostId(int $postId): ?int
{
    if ($postId < 1) {
        return null;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT id, repost_of_post_id
         FROM posts
         WHERE id = :id
           AND is_deleted = FALSE
         LIMIT 1'
    );
    $stmt->execute(['id' => $postId]);
    $post = $stmt->fetch();

    if ($post === false) {
        return null;
    }

    $repostOf = (int) ($post['repost_of_post_id'] ?? 0);

    return $repostOf > 0 ? $repostOf : (int) ($post['id'] ?? 0);
}

function incrementQuotedPostCount(int $quotedPostId, ?PDO $pdo = null): void
{
    if ($quotedPostId < 1) {
        return;
    }

    $pdo = $pdo ?? createPdoConnection();
    $stmt = $pdo->prepare(
        'UPDATE posts
         SET quote_count = quote_count + 1,
             updated_at = NOW()
         WHERE id = :id
           AND is_deleted = FALSE'
    );
    $stmt->execute(['id' => $quotedPostId]);
}

function decrementQuotedPostCount(int $quotedPostId, ?PDO $pdo = null): void
{
    if ($quotedPostId < 1) {
        return;
    }

    $pdo = $pdo ?? createPdoConnection();
    $stmt = $pdo->prepare(
        'UPDATE posts
         SET quote_count = GREATEST(quote_count - 1, 0),
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute(['id' => $quotedPostId]);
}

function createPost(int $userId, ?string $body, ?string $locationLabel = null, ?int $quotedPostId = null): ?array
{
    $pdo = createPdoConnection();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO posts (user_id, body, location_label, quoted_post_id)
             VALUES (:user_id, :body, :location_label, :quoted_post_id)
             RETURNING id, user_id, body, location_label, quoted_post_id,
                       reply_count, repost_count, quote_count, like_count, view_count, interaction_count, created_at'
        );
        $stmt->execute([
            'user_id' => $userId,
            'body' => $body,
            'location_label' => $locationLabel,
            'quoted_post_id' => $quotedPostId,
        ]);
        $post = $stmt->fetch();

        if ($post === false) {
            $pdo->rollBack();

            return null;
        }

        if ($quotedPostId !== null && $quotedPostId > 0) {
            incrementQuotedPostCount($quotedPostId, $pdo);
        }

        $pdo->commit();

        if ($quotedPostId !== null && $quotedPostId > 0 && is_array($post)) {
            notifyPostQuote($quotedPostId, (int) ($post['id'] ?? 0), $userId);
        }

        return $post;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
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
function postContentPostId(array $row): int
{
    $repostOf = (int) ($row['repost_of_post_id'] ?? 0);

    return $repostOf > 0 ? $repostOf : (int) ($row['id'] ?? 0);
}

function hydrateFeedPostsWithMedia(array $rows): array
{
    $postIds = [];
    foreach ($rows as $row) {
        $postIds[] = (int) ($row['id'] ?? 0);
        $repostOf = (int) ($row['repost_of_post_id'] ?? 0);
        if ($repostOf > 0) {
            $postIds[] = $repostOf;
        }
        $quotedPostId = (int) ($row['quoted_post_id'] ?? 0);
        if ($quotedPostId > 0) {
            $postIds[] = $quotedPostId;
        }
    }
    $grouped = fetchPostMediaGroupedByPostIds($postIds);

    foreach ($rows as &$row) {
        $postId = (int) ($row['id'] ?? 0);
        $row['media_items'] = $grouped[$postId] ?? [];
        $repostOf = (int) ($row['repost_of_post_id'] ?? 0);
        if ($repostOf > 0) {
            $row['repost_media_items'] = $grouped[$repostOf] ?? [];
        }
        $quotedPostId = (int) ($row['quoted_post_id'] ?? 0);
        if ($quotedPostId > 0) {
            $row['quote_media_items'] = $grouped[$quotedPostId] ?? [];
        }
    }
    unset($row);

    return $rows;
}

function deletePostForUser(int $postId, int $userId): void
{
    $hashtagIds = fetchHashtagIdsForPost($postId);
    $pdo = createPdoConnection();
    $quotedStmt = $pdo->prepare(
        'SELECT quoted_post_id
         FROM posts
         WHERE id = :id
           AND user_id = :user_id
         LIMIT 1'
    );
    $quotedStmt->execute([
        'id' => $postId,
        'user_id' => $userId,
    ]);
    $quotedRow = $quotedStmt->fetch();
    $quotedPostId = $quotedRow !== false ? (int) ($quotedRow['quoted_post_id'] ?? 0) : 0;

    $stmt = $pdo->prepare(
        'DELETE FROM posts
         WHERE id = :id
           AND user_id = :user_id'
    );
    $stmt->execute([
        'id' => $postId,
        'user_id' => $userId,
    ]);

    if ($stmt->rowCount() > 0 && $quotedPostId > 0) {
        decrementQuotedPostCount($quotedPostId);
    }

    recomputeHashtagPostCounts($hashtagIds);
}

/**
 * @return list<array<string, mixed>>
 */
function fetchFeedPosts(int $limit = POST_FEED_DEFAULT_LIMIT): array
{
    $limit = max(1, min($limit, 100));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT p.id, p.user_id, p.body, p.location_label, p.repost_of_post_id, p.quoted_post_id,
                p.reply_count, p.repost_count, p.quote_count, p.like_count, p.view_count, p.interaction_count, p.created_at,
                u.display_name, u.handle, u.username, u.avatar_url,
                orig.id AS orig_id, orig.user_id AS orig_user_id, orig.body AS orig_body,
                orig.location_label AS orig_location_label,
                orig.reply_count AS orig_reply_count, orig.repost_count AS orig_repost_count,
                orig.quote_count AS orig_quote_count,
                orig.like_count AS orig_like_count, orig.view_count AS orig_view_count,
                orig.interaction_count AS orig_interaction_count, orig.created_at AS orig_created_at,
                orig_u.display_name AS orig_display_name, orig_u.handle AS orig_handle,
                orig_u.username AS orig_username, orig_u.avatar_url AS orig_avatar_url,
                quote.id AS quote_id, quote.user_id AS quote_user_id, quote.body AS quote_body,
                quote.location_label AS quote_location_label, quote.created_at AS quote_created_at,
                quote_u.display_name AS quote_display_name, quote_u.handle AS quote_handle,
                quote_u.username AS quote_username, quote_u.avatar_url AS quote_avatar_url
         FROM posts p
         INNER JOIN users u ON u.id = p.user_id
         LEFT JOIN posts orig ON orig.id = p.repost_of_post_id AND orig.is_deleted = FALSE
         LEFT JOIN users orig_u ON orig_u.id = orig.user_id
         LEFT JOIN posts quote ON quote.id = p.quoted_post_id AND quote.is_deleted = FALSE
         LEFT JOIN users quote_u ON quote_u.id = quote.user_id
         WHERE p.is_deleted = FALSE
           AND (p.repost_of_post_id IS NULL OR orig.id IS NOT NULL)
           AND (p.quoted_post_id IS NULL OR quote.id IS NOT NULL)
         ORDER BY p.created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    return is_array($rows) ? hydrateFeedPostsWithMedia($rows) : [];
}

/**
 * @return list<array<string, mixed>>
 */
function fetchPostsByUserId(int $userId, int $limit = POST_FEED_DEFAULT_LIMIT): array
{
    if ($userId < 1) {
        return [];
    }

    $limit = max(1, min($limit, 100));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT p.id, p.user_id, p.body, p.location_label, p.repost_of_post_id, p.quoted_post_id,
                p.reply_count, p.repost_count, p.quote_count, p.like_count, p.view_count, p.interaction_count, p.created_at,
                u.display_name, u.handle, u.username, u.avatar_url,
                orig.id AS orig_id, orig.user_id AS orig_user_id, orig.body AS orig_body,
                orig.location_label AS orig_location_label,
                orig.reply_count AS orig_reply_count, orig.repost_count AS orig_repost_count,
                orig.quote_count AS orig_quote_count,
                orig.like_count AS orig_like_count, orig.view_count AS orig_view_count,
                orig.interaction_count AS orig_interaction_count, orig.created_at AS orig_created_at,
                orig_u.display_name AS orig_display_name, orig_u.handle AS orig_handle,
                orig_u.username AS orig_username, orig_u.avatar_url AS orig_avatar_url,
                quote.id AS quote_id, quote.user_id AS quote_user_id, quote.body AS quote_body,
                quote.location_label AS quote_location_label, quote.created_at AS quote_created_at,
                quote_u.display_name AS quote_display_name, quote_u.handle AS quote_handle,
                quote_u.username AS quote_username, quote_u.avatar_url AS quote_avatar_url
         FROM posts p
         INNER JOIN users u ON u.id = p.user_id
         LEFT JOIN posts orig ON orig.id = p.repost_of_post_id AND orig.is_deleted = FALSE
         LEFT JOIN users orig_u ON orig_u.id = orig.user_id
         LEFT JOIN posts quote ON quote.id = p.quoted_post_id AND quote.is_deleted = FALSE
         LEFT JOIN users quote_u ON quote_u.id = quote.user_id
         WHERE p.user_id = :user_id
           AND p.is_deleted = FALSE
           AND (p.repost_of_post_id IS NULL OR orig.id IS NOT NULL)
           AND (p.quoted_post_id IS NULL OR quote.id IS NOT NULL)
         ORDER BY p.created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    return is_array($rows) ? hydrateFeedPostsWithMedia($rows) : [];
}

function fetchUserPostCount(int $userId): int
{
    if ($userId < 1) {
        return 0;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)::int
         FROM posts p
         LEFT JOIN posts orig ON orig.id = p.repost_of_post_id AND orig.is_deleted = FALSE
         LEFT JOIN posts quote ON quote.id = p.quoted_post_id AND quote.is_deleted = FALSE
         WHERE p.user_id = :user_id
           AND p.is_deleted = FALSE
           AND (p.repost_of_post_id IS NULL OR orig.id IS NOT NULL)
           AND (p.quoted_post_id IS NULL OR quote.id IS NOT NULL)'
    );
    $stmt->execute(['user_id' => $userId]);

    return max(0, (int) $stmt->fetchColumn());
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
                u.display_name, u.handle, u.username, u.avatar_url
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
    $isRepost = (int) ($row['repost_of_post_id'] ?? 0) > 0 && (int) ($row['orig_id'] ?? 0) > 0;
    $contentRow = $row;

    if ($isRepost) {
        $contentRow = [
            'id' => (int) ($row['orig_id'] ?? 0),
            'user_id' => (int) ($row['orig_user_id'] ?? 0),
            'body' => (string) ($row['orig_body'] ?? ''),
            'location_label' => $row['orig_location_label'] ?? null,
            'reply_count' => (int) ($row['orig_reply_count'] ?? 0),
            'repost_count' => (int) ($row['orig_repost_count'] ?? 0),
            'quote_count' => (int) ($row['orig_quote_count'] ?? 0),
            'like_count' => (int) ($row['orig_like_count'] ?? 0),
            'view_count' => (int) ($row['orig_view_count'] ?? 0),
            'interaction_count' => (int) ($row['orig_interaction_count'] ?? 0),
            'created_at' => (string) ($row['orig_created_at'] ?? ''),
            'display_name' => (string) ($row['orig_display_name'] ?? ''),
            'handle' => (string) ($row['orig_handle'] ?? ''),
            'username' => (string) ($row['orig_username'] ?? ''),
            'avatar_url' => (string) ($row['orig_avatar_url'] ?? ''),
            'media_items' => is_array($row['repost_media_items'] ?? null) ? $row['repost_media_items'] : [],
        ];
    }

    $user = [
        'display_name' => (string) ($contentRow['display_name'] ?? ''),
        'handle' => (string) ($contentRow['handle'] ?? ''),
        'avatar_url' => (string) ($contentRow['avatar_url'] ?? ''),
    ];
    $media = postMediaPayloadItems($contentRow);

    $payload = [
        'id' => (int) ($contentRow['id'] ?? 0),
        'user_id' => (int) ($contentRow['user_id'] ?? 0),
        'body' => (string) ($contentRow['body'] ?? ''),
        'media' => $media,
        'location_label' => isset($contentRow['location_label']) && $contentRow['location_label'] !== null
            ? (string) $contentRow['location_label']
            : null,
        'reply_count' => (int) ($contentRow['reply_count'] ?? 0),
        'repost_count' => (int) ($contentRow['repost_count'] ?? 0),
        'quote_count' => (int) ($contentRow['quote_count'] ?? 0),
        'like_count' => (int) ($contentRow['like_count'] ?? 0),
        'view_count' => (int) ($contentRow['view_count'] ?? 0),
        'interaction_count' => (int) ($contentRow['interaction_count'] ?? 0),
        'created_at' => (string) ($contentRow['created_at'] ?? ''),
        'time_label' => formatPostTimeLabel((string) ($contentRow['created_at'] ?? '')),
        'detail_date_label' => formatPostDetailDateLabel((string) ($contentRow['created_at'] ?? '')),
        'author' => [
            'display_name' => $user['display_name'],
            'handle' => $user['handle'],
            'username' => (string) ($contentRow['username'] ?? ''),
            'avatar_url' => userMediaUrl($user, 'avatar_url', $url),
            'profile_url' => profileUrlForUser([
                'username' => (string) ($contentRow['username'] ?? ''),
            ], $url),
        ],
        'is_repost' => $isRepost,
        'repost_entry_id' => $isRepost ? (int) ($row['id'] ?? 0) : null,
        'reposter' => null,
    ];

    if ($isRepost) {
        $reposter = [
            'display_name' => (string) ($row['display_name'] ?? ''),
            'handle' => (string) ($row['handle'] ?? ''),
            'avatar_url' => (string) ($row['avatar_url'] ?? ''),
            'username' => (string) ($row['username'] ?? ''),
        ];
        $payload['reposter'] = [
            'display_name' => $reposter['display_name'],
            'handle' => $reposter['handle'],
            'username' => $reposter['username'],
            'avatar_url' => userMediaUrl($reposter, 'avatar_url', $url),
            'profile_url' => profileUrlForUser([
                'username' => $reposter['username'],
            ], $url),
        ];
    }

    $quotedPostId = (int) ($row['quoted_post_id'] ?? 0);
    if ($quotedPostId > 0 && (int) ($row['quote_id'] ?? 0) > 0) {
        $quoteRow = [
            'id' => (int) ($row['quote_id'] ?? 0),
            'body' => (string) ($row['quote_body'] ?? ''),
            'location_label' => $row['quote_location_label'] ?? null,
            'created_at' => (string) ($row['quote_created_at'] ?? ''),
            'display_name' => (string) ($row['quote_display_name'] ?? ''),
            'handle' => (string) ($row['quote_handle'] ?? ''),
            'username' => (string) ($row['quote_username'] ?? ''),
            'avatar_url' => (string) ($row['quote_avatar_url'] ?? ''),
            'media_items' => is_array($row['quote_media_items'] ?? null) ? $row['quote_media_items'] : [],
        ];
        $quoteUser = [
            'display_name' => (string) ($quoteRow['display_name'] ?? ''),
            'handle' => (string) ($quoteRow['handle'] ?? ''),
            'avatar_url' => (string) ($quoteRow['avatar_url'] ?? ''),
        ];
        $quoteId = (int) ($quoteRow['id'] ?? 0);
        $payload['quoted_post'] = [
            'id' => $quoteId,
            'body' => (string) ($quoteRow['body'] ?? ''),
            'media' => postMediaPayloadItems($quoteRow),
            'created_at' => (string) ($quoteRow['created_at'] ?? ''),
            'post_url' => postUrl($quoteId, $url),
            'author' => [
                'display_name' => $quoteUser['display_name'],
                'handle' => $quoteUser['handle'],
                'username' => (string) ($quoteRow['username'] ?? ''),
                'avatar_url' => userMediaUrl($quoteUser, 'avatar_url', $url),
                'profile_url' => profileUrlForUser([
                    'username' => (string) ($quoteRow['username'] ?? ''),
                ], $url),
            ],
        ];
    } else {
        $payload['quoted_post'] = null;
    }

    return $payload;
}

/**
 * @param array<string, mixed> $row
 */
function renderPostCard(
    array $row,
    callable $url,
    int $currentUserId = 0,
    bool $viewerLiked = false,
    bool $viewerReposted = false
): void {
    $post = postFeedPayload($row, $url);
    $post['post_url'] = postUrl((int) $post['id'], $url);
    $post['viewer_liked'] = $viewerLiked;
    $post['viewer_reposted'] = $viewerReposted;
    require __DIR__ . '/posts/post-card.php';
}
