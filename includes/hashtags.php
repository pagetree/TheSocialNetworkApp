<?php

declare(strict_types=1);

const HASHTAG_TAG_MAX_LENGTH = 50;
const HASHTAG_MAX_PER_CONTENT = 5;

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

    if (mb_strlen($tag) > HASHTAG_TAG_MAX_LENGTH) {
        $tag = mb_substr($tag, 0, HASHTAG_TAG_MAX_LENGTH);
    }

    if (!preg_match('/^[\p{L}\p{N}_]+$/u', $tag)) {
        return '';
    }

    return $tag;
}

/**
 * @return list<string>
 */
function extractHashtagTags(string $body): array
{
    if ($body === '') {
        return [];
    }

    if (!preg_match_all('/#([\p{L}\p{N}_]{1,' . HASHTAG_TAG_MAX_LENGTH . '})/u', $body, $matches)) {
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
    return '/hashtag/' . $tag;
}

function formatPostBodyHtml(string $body, callable $url): string
{
    if ($body === '') {
        return '';
    }

    $pattern = '/#([\p{L}\p{N}_]{1,' . HASHTAG_TAG_MAX_LENGTH . '})/u';
    if (!preg_match_all($pattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
        return htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
    }

    $html = '';
    $byteOffset = 0;

    foreach ($matches[0] as $index => $fullMatch) {
        $matchBytes = (string) $fullMatch[0];
        $matchByteStart = (int) $fullMatch[1];

        if ($matchByteStart > $byteOffset) {
            $html .= htmlspecialchars(substr($body, $byteOffset, $matchByteStart - $byteOffset), ENT_QUOTES, 'UTF-8');
        }

        $tag = normalizeHashtagTag((string) $matches[1][$index][0]);
        if ($tag === '') {
            $html .= htmlspecialchars($matchBytes, ENT_QUOTES, 'UTF-8');
        } else {
            $href = htmlspecialchars($url(hashtagUrlPath($tag)), ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars('#' . $tag, ENT_QUOTES, 'UTF-8');
            $html .= '<a href="' . $href . '" class="post-hashtag">' . $label . '</a>';
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
    } catch (Throwable) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

function syncPostReplyHashtags(int $replyId, string $body): void
{
    if ($replyId < 1 || !hashtagsAreAvailable()) {
        return;
    }

    $tags = extractHashtagTags($body);

    try {
        $pdo = createPdoConnection();
        $pdo->beginTransaction();

        $delete = $pdo->prepare('DELETE FROM post_reply_hashtags WHERE post_reply_id = :post_reply_id');
        $delete->execute(['post_reply_id' => $replyId]);

        if ($tags !== []) {
            $hashtagIds = upsertHashtagIds($tags, $pdo);
            $link = $pdo->prepare(
                'INSERT INTO post_reply_hashtags (post_reply_id, hashtag_id)
                 VALUES (:post_reply_id, :hashtag_id)
                 ON CONFLICT DO NOTHING'
            );

            foreach ($hashtagIds as $hashtagId) {
                $link->execute([
                    'post_reply_id' => $replyId,
                    'hashtag_id' => $hashtagId,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
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
        'post_count' => (int) $row['post_count'],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function fetchPostsByHashtag(string $tag, int $limit = POST_FEED_DEFAULT_LIMIT): array
{
    $tag = normalizeHashtagTag($tag);
    if ($tag === '' || !hashtagsAreAvailable()) {
        return [];
    }

    $limit = max(1, min($limit, 100));
    $pdo = createPdoConnection();
    $stmt = $pdo->prepare(
        'SELECT p.id, p.user_id, p.body, p.location_label,
                p.reply_count, p.repost_count, p.like_count, p.view_count, p.interaction_count, p.created_at,
                u.display_name, u.handle, u.username, u.avatar_url
         FROM posts p
         INNER JOIN post_hashtags ph ON ph.post_id = p.id
         INNER JOIN hashtags h ON h.id = ph.hashtag_id
         INNER JOIN users u ON u.id = p.user_id
         WHERE h.tag = :tag
           AND p.is_deleted = FALSE
           AND p.repost_of_post_id IS NULL
         ORDER BY p.created_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':tag', $tag);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    return is_array($rows) ? hydrateFeedPostsWithMedia($rows) : [];
}
