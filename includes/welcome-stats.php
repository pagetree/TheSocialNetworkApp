<?php

declare(strict_types=1);

/**
 * @return array{members: int, posts: int, replies: int, hashtags: int, likes: int}
 */
function fetchPublicPlatformStats(): array
{
    $defaults = [
        'members' => 0,
        'posts' => 0,
        'replies' => 0,
        'hashtags' => 0,
        'likes' => 0,
    ];

    try {
        $pdo = createPdoConnection();
        $hashtagsCountSql = hashtagsAreAvailable()
            ? '(SELECT COUNT(*)::int FROM hashtags)'
            : '0';
        $stmt = $pdo->query(
            'SELECT
                (SELECT COUNT(*)::int FROM users) AS members,
                (SELECT COUNT(*)::int FROM posts WHERE is_deleted = FALSE) AS posts,
                (SELECT COUNT(*)::int FROM post_replies) AS replies,
                ' . $hashtagsCountSql . ' AS hashtags,
                (SELECT COUNT(*)::int FROM post_likes) AS likes'
        );
        $row = $stmt->fetch();
        if ($row === false) {
            return $defaults;
        }

        return [
            'members' => max(0, (int) ($row['members'] ?? 0)),
            'posts' => max(0, (int) ($row['posts'] ?? 0)),
            'replies' => max(0, (int) ($row['replies'] ?? 0)),
            'hashtags' => max(0, (int) ($row['hashtags'] ?? 0)),
            'likes' => max(0, (int) ($row['likes'] ?? 0)),
        ];
    } catch (Throwable) {
        return $defaults;
    }
}

function formatPublicStatCount(int $count): string
{
    if ($count >= 1_000_000) {
        $value = $count / 1_000_000;

        return rtrim(rtrim(number_format($value, 1), '0'), '.') . 'M';
    }

    if ($count >= 10_000) {
        $value = $count / 1_000;

        return rtrim(rtrim(number_format($value, 1), '0'), '.') . 'K';
    }

    return number_format($count);
}

/**
 * @return list<string>
 */
function guestPublicPaths(): array
{
    return [
        '/',
        '/register',
        '/login',
    ];
}

/**
 * @param callable(string): string $url
 */
function guestAppRedirectUrlIfNeeded(string $path, callable $url): ?string
{
    if (isLoggedIn()) {
        return null;
    }

    if (in_array($path, guestPublicPaths(), true)) {
        return null;
    }

    return $url('/');
}
