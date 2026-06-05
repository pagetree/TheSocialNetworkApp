<?php

declare(strict_types=1);

const HASHTAG_TAG_MAX_LENGTH = 50;
const HASHTAG_MAX_PER_CONTENT = 5;

/** URL-safe slug: lowercase letters, digits, underscore only. */
const HASHTAG_TAG_REGEX = '[a-z0-9_]{1,' . HASHTAG_TAG_MAX_LENGTH . '}';

function hashtagTagIsValid(string $tag): bool
{
    return $tag !== '' && preg_match('/^' . HASHTAG_TAG_REGEX . '$/', $tag) === 1;
}

function hashtagsAreAvailable(): bool
{
    static $available = null;

    if ($available !== null) {
        return $available;
    }

    try {
        $pdo = createPdoConnection();
        $pdo->query('SELECT 1 FROM hashtags LIMIT 1');
        $available = true;
    } catch (Throwable) {
        $available = false;
    }

    return $available;
}

function normalizeHashtagTag(string $raw): string
{
    $tag = strtolower(rtrim($raw, '._!?,'));
    if ($tag === '') {
        return '';
    }

    if (strlen($tag) > HASHTAG_TAG_MAX_LENGTH) {
        $tag = substr($tag, 0, HASHTAG_TAG_MAX_LENGTH);
    }

    return hashtagTagIsValid($tag) ? $tag : '';
}

/**
 * @return list<string>
 */
function extractHashtagTags(string $body): array
{
    if ($body === '') {
        return [];
    }

    $pattern = '/#(' . HASHTAG_TAG_REGEX . ')/i';
    if (!preg_match_all($pattern, $body, $matches)) {
        return [];
    }

    $tags = [];
    foreach ($matches[1] as $raw) {
        $tag = normalizeHashtagTag((string) $raw);
        if ($tag === '' || isset($tags[$tag])) {
            continue;
        }

        $tags[$tag] = true;
        if (count($tags) >= HASHTAG_MAX_PER_CONTENT) {
            break;
        }
    }

    return array_keys($tags);
}

function hashtagUrlPath(string $tag): string
{
    $tag = normalizeHashtagTag($tag);

    return $tag === '' ? '' : '/hashtag/' . $tag;
}

function formatPostBodyHtml(string $body, callable $url): string
{
    if ($body === '') {
        return '';
    }

    $pattern = '/(?<![a-z0-9_])@(' . MENTION_HANDLE_REGEX . ')(?![a-z0-9_])|#(' . HASHTAG_TAG_REGEX . ')/i';
    if (!preg_match_all($pattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
        return htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
    }

    $mentionUsers = fetchUsersByMentionHandles(extractMentionHandles($body));
    $html = '';
    $byteOffset = 0;

    foreach ($matches[0] as $index => $fullMatch) {
        $matchBytes = (string) $fullMatch[0];
        $matchByteStart = (int) $fullMatch[1];
        $mentionRaw = (string) ($matches[1][$index][0] ?? '');
        $hashtagRaw = (string) ($matches[2][$index][0] ?? '');

        if ($matchByteStart > $byteOffset) {
            $html .= htmlspecialchars(substr($body, $byteOffset, $matchByteStart - $byteOffset), ENT_QUOTES, 'UTF-8');
        }

        if ($mentionRaw !== '') {
            $handle = normalizeMentionHandle($mentionRaw);
            $mentionedUser = $handle !== '' ? ($mentionUsers[$handle] ?? null) : null;
            if ($mentionedUser === null) {
                $html .= htmlspecialchars($matchBytes, ENT_QUOTES, 'UTF-8');
            } else {
                $href = htmlspecialchars(profileUrlForUser($mentionedUser, $url), ENT_QUOTES, 'UTF-8');
                $label = htmlspecialchars($handle, ENT_QUOTES, 'UTF-8');
                $html .= '<a href="' . $href . '" class="post-mention">' . $label . '</a>';
            }
        } else {
            $tag = normalizeHashtagTag($hashtagRaw);
            $path = $tag !== '' ? hashtagUrlPath($tag) : '';
            if ($path === '') {
                $html .= htmlspecialchars($matchBytes, ENT_QUOTES, 'UTF-8');
            } else {
                $href = htmlspecialchars($url($path), ENT_QUOTES, 'UTF-8');
                $label = htmlspecialchars('#' . $tag, ENT_QUOTES, 'UTF-8');
                $html .= '<a href="' . $href . '" class="post-hashtag">' . $label . '</a>';
            }
        }

        $byteOffset = $matchByteStart + strlen($matchBytes);
    }

    if ($byteOffset < strlen($body)) {
        $html .= htmlspecialchars(substr($body, $byteOffset), ENT_QUOTES, 'UTF-8');
    }

    return $html;
}

/**
 * @param list<string> $tags
 * @return list<int>
 */
function upsertHashtagIds(array $tags, ?PDO $pdo = null): array
{
    $tags = array_values(array_filter($tags, static fn (string $tag): bool => hashtagTagIsValid($tag)));
    if ($tags === []) {
        return [];
    }

    $pdo = $pdo ?? createPdoConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO hashtags (tag, last_used_at)
         VALUES (:tag, NOW())
         ON CONFLICT (tag)
         DO UPDATE SET last_used_at = NOW()
         RETURNING id'
    );

    $ids = [];
    foreach ($tags as $tag) {
        $stmt->execute(['tag' => $tag]);
        $row = $stmt->fetch();
        if ($row !== false) {
            $ids[] = (int) $row['id'];
        }
    }

    return $ids;
}

/**
 * @param list<int> $hashtagIds
 */
function recomputeHashtagPostCounts(array $hashtagIds): void
{
    $hashtagIds = array_values(array_unique(array_filter(array_map('intval', $hashtagIds), static fn (int $id): bool => $id > 0)));
    if ($hashtagIds === [] || !hashtagsAreAvailable()) {
        return;
    }

    $placeholders = implode(', ', array_fill(0, count($hashtagIds), '?'));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'UPDATE hashtags h
         SET post_count = (
             SELECT COUNT(*)::int
             FROM post_hashtags ph
             WHERE ph.hashtag_id = h.id
         )
         WHERE h.id IN (' . $placeholders . ')'
    );
    $stmt->execute($hashtagIds);
}

/**
 * @return list<int>
 */
function fetchHashtagIdsForPost(int $postId): array
{
    if ($postId < 1 || !hashtagsAreAvailable()) {
        return [];
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT hashtag_id
         FROM post_hashtags
         WHERE post_id = :post_id'
    );
    $stmt->execute(['post_id' => $postId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return is_array($rows)
        ? array_values(array_map('intval', $rows))
        : [];
}

function logHashtagSyncFailure(string $operation, Throwable $exception, array $context = []): void
{
    $contextJson = $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
    error_log('Hashtag ' . $operation . ' failed: ' . $exception->getMessage() . $contextJson);
}

function syncPostHashtags(int $postId, ?string $body): void
{
    if ($postId < 1 || !hashtagsAreAvailable()) {
        return;
    }

    $tags = extractHashtagTags((string) $body);
    $previousIds = fetchHashtagIdsForPost($postId);
    $newIds = [];

    try {
        $pdo = createPdoConnection();
        $pdo->beginTransaction();

        $delete = $pdo->prepare('DELETE FROM post_hashtags WHERE post_id = :post_id');
        $delete->execute(['post_id' => $postId]);

        if ($tags !== []) {
            $newIds = upsertHashtagIds($tags, $pdo);
            $link = $pdo->prepare(
                'INSERT INTO post_hashtags (post_id, hashtag_id)
                 VALUES (:post_id, :hashtag_id)
                 ON CONFLICT DO NOTHING'
            );

            foreach ($newIds as $hashtagId) {
                $link->execute([
                    'post_id' => $postId,
                    'hashtag_id' => $hashtagId,
                ]);
            }
        }

        $pdo->commit();
        recomputeHashtagPostCounts(array_merge($previousIds, $newIds));
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        logHashtagSyncFailure('syncPostHashtags', $exception, ['post_id' => $postId]);
    }
}

/**
 * @return array{tag: string, post_count: int}|null
 */
function fetchHashtagByTag(string $tag): ?array
{
    $tag = normalizeHashtagTag($tag);
    if ($tag === '' || !hashtagsAreAvailable()) {
        return null;
    }

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT tag, post_count
         FROM hashtags
         WHERE tag = :tag
         LIMIT 1'
    );
    $stmt->execute(['tag' => $tag]);
    $row = $stmt->fetch();

    if ($row === false) {
        return null;
    }

    return [
        'tag' => (string) $row['tag'],
        'post_count' => max(0, (int) $row['post_count']),
    ];
}

function fetchPostsByHashtag(string $tag, int $limit = POST_FEED_DEFAULT_LIMIT): array
{
    $tag = normalizeHashtagTag($tag);
    if ($tag === '' || !hashtagsAreAvailable()) {
        return [];
    }

    $limit = max(1, min($limit, 100));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT p.id, p.user_id, p.body, p.location_label, p.quoted_post_id,
                p.reply_count, p.repost_count, p.quote_count, p.like_count, p.view_count, p.interaction_count, p.created_at,
                u.display_name, u.handle, u.username, u.avatar_url,
                quote.id AS quote_id, quote.user_id AS quote_user_id, quote.body AS quote_body,
                quote.location_label AS quote_location_label, quote.created_at AS quote_created_at,
                quote_u.display_name AS quote_display_name, quote_u.handle AS quote_handle,
                quote_u.username AS quote_username, quote_u.avatar_url AS quote_avatar_url
         FROM posts p
         INNER JOIN post_hashtags ph ON ph.post_id = p.id
         INNER JOIN hashtags h ON h.id = ph.hashtag_id
         INNER JOIN users u ON u.id = p.user_id
         LEFT JOIN posts quote ON quote.id = p.quoted_post_id AND quote.is_deleted = FALSE
         LEFT JOIN users quote_u ON quote_u.id = quote.user_id
         WHERE h.tag = :tag
           AND p.is_deleted = FALSE
           AND p.repost_of_post_id IS NULL
           AND (p.quoted_post_id IS NULL OR quote.id IS NOT NULL)
         ORDER BY p.created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':tag', $tag);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    return is_array($rows) ? hydrateFeedPostsWithMedia($rows) : [];
}

const SIDEBAR_TOP_HASHTAGS_LIMIT = 5;

/**
 * @return list<array{tag: string, post_count: int}>
 */
function fetchTopHashtagsByPostCount(int $limit = SIDEBAR_TOP_HASHTAGS_LIMIT): array
{
    if (!hashtagsAreAvailable()) {
        return [];
    }

    $limit = max(1, min($limit, SIDEBAR_TOP_HASHTAGS_LIMIT));

    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT tag, post_count
         FROM hashtags
         WHERE post_count > 0
         ORDER BY post_count DESC, tag ASC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    if (!is_array($rows)) {
        return [];
    }

    $hashtags = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $tag = normalizeHashtagTag((string) ($row['tag'] ?? ''));
        if ($tag === '') {
            continue;
        }

        $hashtags[] = [
            'tag' => $tag,
            'post_count' => max(0, (int) ($row['post_count'] ?? 0)),
        ];
    }

    return $hashtags;
}

/**
 * Parse a URL path segment from /hashtag/{segment}.
 */
function parseHashtagTagFromUrl(string $segment): string
{
    return normalizeHashtagTag(strtolower($segment));
}
