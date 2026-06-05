<?php

declare(strict_types=1);

const SEO_DESCRIPTION_MAX_LENGTH = 155;
const SEO_TITLE_EXCERPT_MAX_LENGTH = 72;
const SEO_SITEMAP_POST_LIMIT = 5000;
const SEO_SITEMAP_PROFILE_LIMIT = 5000;
const SEO_SITEMAP_HASHTAG_LIMIT = 2000;
const SEO_POST_MIN_BODY_LENGTH = 30;

/** App-relative paths checked in order; add your 1200×630 share image as one of these. */
const SEO_DEFAULT_OG_IMAGE_CANDIDATES = [
    '/assets/img/og-share.jpg',
    '/assets/img/og-share.png',
    '/assets/img/og-share.webp',
];

function appAbsoluteUrl(string $uri = '/'): string
{
    $configured = getenv('APP_URL');
    if (is_string($configured) && trim($configured) !== '') {
        return appUrl($uri);
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $relative = appPaths()['url']($uri);

    if ($relative === '/') {
        return $scheme . '://' . $host . '/';
    }

    return $scheme . '://' . $host . $relative;
}

function seoTruncate(string $text, int $maxLength = SEO_DESCRIPTION_MAX_LENGTH): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

    if ($text === '') {
        return '';
    }

    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }

    $cut = mb_substr($text, 0, $maxLength - 1);
    $lastSpace = mb_strrpos($cut, ' ');

    if ($lastSpace !== false && $lastSpace > (int) ($maxLength * 0.6)) {
        $cut = mb_substr($cut, 0, $lastSpace);
    }

    return rtrim($cut, ".,;:!?") . '…';
}

function seoDefaultOgImageRelativePath(): string
{
    $projectRoot = dirname(__DIR__);

    foreach (SEO_DEFAULT_OG_IMAGE_CANDIDATES as $relativePath) {
        if (is_file($projectRoot . $relativePath)) {
            return $relativePath;
        }
    }

    return SEO_DEFAULT_OG_IMAGE_CANDIDATES[0];
}

function seoDefaultShareImageUrl(): string
{
    $configured = getenv('APP_OG_IMAGE');
    if (is_string($configured) && trim($configured) !== '') {
        $configured = trim($configured);

        if (preg_match('#^https?://#i', $configured) === 1) {
            return $configured;
        }

        return seoAbsoluteMediaUrl($configured);
    }

    return appAbsoluteUrl(seoDefaultOgImageRelativePath());
}

function seoAbsoluteMediaUrl(string $mediaUrl): string
{
    if ($mediaUrl === '') {
        return seoDefaultShareImageUrl();
    }

    if (preg_match('#^https?://#i', $mediaUrl) === 1) {
        return $mediaUrl;
    }

    return appAbsoluteUrl($mediaUrl[0] === '/' ? $mediaUrl : '/' . $mediaUrl);
}

function seoPostExcerpt(array $post): string
{
    $body = trim((string) ($post['body'] ?? ''));

    if ($body !== '') {
        return seoTruncate($body, SEO_TITLE_EXCERPT_MAX_LENGTH);
    }

    return __('meta.post_media_fallback');
}

function seoPostShareImage(array $post, callable $url): string
{
    $hydrated = hydrateFeedPostsWithMedia([$post]);
    $row = $hydrated[0] ?? $post;
    $media = postMediaPayloadItems($row);

    foreach ($media as $item) {
        if (!is_array($item)) {
            continue;
        }

        $type = (string) ($item['type'] ?? '');
        $itemUrl = (string) ($item['url'] ?? '');

        if ($itemUrl === '') {
            continue;
        }

        if ($type === '' || str_starts_with($type, 'image')) {
            return seoAbsoluteMediaUrl($itemUrl);
        }
    }

    $author = [
        'avatar_url' => (string) ($row['avatar_url'] ?? ''),
    ];

    return seoAbsoluteMediaUrl(userMediaUrl($author, 'avatar_url', $url));
}

/**
 * @return array<string, mixed>
 */
function seoNoindexPage(string $canonicalPath = ''): array
{
    return [
        'robots' => 'noindex, nofollow',
        'canonical_path' => $canonicalPath,
    ];
}

/**
 * @return array<string, mixed>
 */
function seoBuildWelcomePage(): array
{
    return [
        'robots' => 'index, follow',
        'description' => __('meta.welcome_description'),
        'canonical_path' => '/',
        'og_type' => 'website',
        'og_title' => __('meta.welcome_og_title'),
        'og_image' => seoDefaultShareImageUrl(),
    ];
}

/**
 * @param array<string, mixed> $user
 * @return array<string, mixed>
 */
function seoBuildProfilePage(array $user, callable $url, bool $isPrivate, bool $isOwnProfile): array
{
    if ($isPrivate || $isOwnProfile) {
        return seoNoindexPage(profileUrlForUser($user, $url));
    }

    $username = (string) ($user['username'] ?? '');
    $displayName = (string) ($user['display_name'] ?? __('meta.profile_default_title'));
    $handle = (string) ($user['handle'] ?? '');
    $bio = trim((string) ($user['bio'] ?? ''));
    $canonicalPath = '/profile/' . rawurlencode($username);

    $description = $bio !== ''
        ? __('meta.profile_description', ['name' => $displayName, 'bio' => seoTruncate($bio)])
        : __('meta.profile_description_fallback', ['name' => $displayName]);

    $pageTitle = __('meta.profile_title_handle', [
        'name' => $displayName,
        'handle' => $handle !== '' ? $handle : '@' . $username,
    ]);

    $profileUrl = appAbsoluteUrl($canonicalPath);
    $avatar = seoAbsoluteMediaUrl(userMediaUrl($user, 'avatar_url', $url));

    $jsonLd = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $displayName,
            'url' => $profileUrl,
            'image' => $avatar,
            'description' => $bio !== '' ? seoTruncate($bio) : $description,
        ],
        seoJsonLdBreadcrumbs([
            ['name' => __('meta.site_name'), 'url' => appAbsoluteUrl('/')],
            ['name' => $displayName, 'url' => $profileUrl],
        ]),
    ];

    return [
        'page_title' => $pageTitle,
        'robots' => 'index, follow',
        'description' => seoTruncate($description),
        'canonical_path' => $canonicalPath,
        'og_type' => 'profile',
        'og_title' => __('meta.profile_og_title', ['name' => $displayName]),
        'og_image' => $avatar,
        'json_ld' => $jsonLd,
    ];
}

/**
 * @param array<string, mixed> $post
 * @return array<string, mixed>
 */
function seoBuildPostPage(array $post, callable $url): array
{
    $postId = (int) ($post['id'] ?? 0);
    $authorName = (string) ($post['display_name'] ?? '');
    $username = (string) ($post['username'] ?? '');
    $excerpt = seoPostExcerpt($post);
    $canonicalPath = '/post/' . $postId;
    $postAbsoluteUrl = appAbsoluteUrl($canonicalPath);
    $profilePath = $username !== '' ? '/profile/' . rawurlencode($username) : '/';
    $profileAbsoluteUrl = appAbsoluteUrl($profilePath);

    $pageTitle = __('meta.post_title_dynamic', [
        'excerpt' => $excerpt,
        'author' => $authorName,
    ]);

    $description = __('meta.post_description', [
        'author' => $authorName,
        'excerpt' => seoTruncate((string) ($post['body'] ?? ''), SEO_DESCRIPTION_MAX_LENGTH),
    ]);

    $createdAt = (string) ($post['created_at'] ?? '');
    $publishedIso = '';
    try {
        if ($createdAt !== '') {
            $publishedIso = (new DateTimeImmutable($createdAt))->format(DateTimeInterface::ATOM);
        }
    } catch (Exception) {
        $publishedIso = '';
    }

    $shareImage = seoPostShareImage($post, $url);

    $jsonLd = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'SocialMediaPosting',
            'headline' => $excerpt,
            'articleBody' => (string) ($post['body'] ?? ''),
            'author' => [
                '@type' => 'Person',
                'name' => $authorName,
                'url' => $profileAbsoluteUrl,
            ],
            'datePublished' => $publishedIso,
            'url' => $postAbsoluteUrl,
            'image' => $shareImage,
        ],
        seoJsonLdBreadcrumbs([
            ['name' => __('meta.site_name'), 'url' => appAbsoluteUrl('/')],
            ['name' => $authorName, 'url' => $profileAbsoluteUrl],
            ['name' => $excerpt, 'url' => $postAbsoluteUrl],
        ]),
    ];

    return [
        'page_title' => $pageTitle,
        'robots' => 'index, follow',
        'description' => seoTruncate($description),
        'canonical_path' => $canonicalPath,
        'og_type' => 'article',
        'og_title' => $pageTitle,
        'og_image' => $shareImage,
        'article_published_time' => $publishedIso,
        'article_author' => $authorName,
        'json_ld' => $jsonLd,
    ];
}

/**
 * @return array<string, mixed>
 */
function seoBuildHashtagPage(string $tag, int $postCount): array
{
    $canonicalPath = hashtagUrlPath($tag);
    $pageTitle = __('meta.hashtag_title_count', [
        'tag' => $tag,
        'count' => (string) $postCount,
    ]);
    $description = __('meta.hashtag_description', ['tag' => $tag]);
    $pageUrl = appAbsoluteUrl($canonicalPath);

    $jsonLd = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => '#' . $tag,
            'url' => $pageUrl,
            'description' => seoTruncate($description),
        ],
        seoJsonLdBreadcrumbs([
            ['name' => __('meta.site_name'), 'url' => appAbsoluteUrl('/')],
            ['name' => '#' . $tag, 'url' => $pageUrl],
        ]),
    ];

    return [
        'page_title' => $pageTitle,
        'robots' => 'index, follow',
        'description' => seoTruncate($description),
        'canonical_path' => $canonicalPath,
        'og_type' => 'website',
        'og_title' => $pageTitle,
        'og_image' => seoDefaultShareImageUrl(),
        'json_ld' => $jsonLd,
    ];
}

/**
 * @param list<array{name: string, url: string}> $items
 * @return array<string, mixed>
 */
function seoJsonLdBreadcrumbs(array $items): array
{
    $elements = [];
    $position = 1;

    foreach ($items as $item) {
        $elements[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $item['name'],
            'item' => $item['url'],
        ];
        $position++;
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $elements,
    ];
}

/**
 * @param array<string, mixed>|null $pageSeo
 */
function renderPageSeoHeadTags(string $pageTitle, ?array $pageSeo = null): void
{
    $pageSeo = is_array($pageSeo) ? $pageSeo : [];
    $robots = (string) ($pageSeo['robots'] ?? 'index, follow');
    $description = trim((string) ($pageSeo['description'] ?? __('meta.site_description')));
    $canonicalPath = (string) ($pageSeo['canonical_path'] ?? appRequestPath());
    if ($canonicalPath === '') {
        $canonicalPath = '/';
    }
    $canonicalUrl = appAbsoluteUrl($canonicalPath);
    $ogTitle = trim((string) ($pageSeo['og_title'] ?? $pageTitle));
    $ogType = (string) ($pageSeo['og_type'] ?? 'website');
    $ogImage = (string) ($pageSeo['og_image'] ?? '');
    if ($ogImage === '') {
        $ogImage = seoDefaultShareImageUrl();
    }
    $twitterCard = (string) ($pageSeo['twitter_card'] ?? 'summary_large_image');
    $siteName = __('meta.site_name');

    echo '    <meta name="robots" content="' . htmlspecialchars($robots, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    if ($description !== '') {
        echo '    <meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    echo '    <link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta property="og:site_name" content="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta property="og:title" content="' . htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    if ($description !== '') {
        echo '    <meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    echo '    <meta property="og:type" content="' . htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta property="og:url" content="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta property="og:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta name="twitter:card" content="' . htmlspecialchars($twitterCard, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '    <meta name="twitter:title" content="' . htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    if ($description !== '') {
        echo '    <meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    echo '    <meta name="twitter:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '">' . "\n";

    $published = (string) ($pageSeo['article_published_time'] ?? '');
    if ($published !== '') {
        echo '    <meta property="article:published_time" content="' . htmlspecialchars($published, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    $articleAuthor = (string) ($pageSeo['article_author'] ?? '');
    if ($articleAuthor !== '') {
        echo '    <meta property="article:author" content="' . htmlspecialchars($articleAuthor, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    $jsonLdBlocks = $pageSeo['json_ld'] ?? [];
    if (is_array($jsonLdBlocks)) {
        foreach ($jsonLdBlocks as $block) {
            if (!is_array($block) || $block === []) {
                continue;
            }
            $encoded = json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                continue;
            }
            echo '    <script type="application/ld+json">' . $encoded . '</script>' . "\n";
        }
    }
}

function renderRobotsTxt(): void
{
    $sitemapUrl = appAbsoluteUrl('/sitemap.xml');
    $lines = [
        'User-agent: *',
        'Allow: /profile/',
        'Allow: /post/',
        'Allow: /hashtag/',
        '',
        'Disallow: /auth/',
        'Disallow: /onboarding',
        'Disallow: /login',
        'Disallow: /register',
        '',
        'Sitemap: ' . $sitemapUrl,
    ];

    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    echo implode("\n", $lines) . "\n";
}

/**
 * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
 */
function fetchSitemapEntries(): array
{
    $entries = [];
    $entries[] = [
        'loc' => appAbsoluteUrl('/'),
        'lastmod' => gmdate('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '1.0',
    ];

    try {
        $pdo = createPdoConnection();
    } catch (Throwable) {
        return $entries;
    }

    $profileStmt = $pdo->prepare(
        'SELECT username, created_at
         FROM users
         WHERE is_visible = TRUE
           AND username IS NOT NULL
           AND TRIM(username) <> \'\'
         ORDER BY created_at DESC
         LIMIT ' . SEO_SITEMAP_PROFILE_LIMIT
    );
    $profileStmt->execute();
    while ($row = $profileStmt->fetch()) {
        if ($row === false) {
            break;
        }
        $username = (string) ($row['username'] ?? '');
        if ($username === '') {
            continue;
        }
        $entries[] = [
            'loc' => appAbsoluteUrl('/profile/' . rawurlencode($username)),
            'lastmod' => seoFormatSitemapDate((string) ($row['created_at'] ?? '')),
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ];
    }

    $postStmt = $pdo->prepare(
        'SELECT p.id, p.created_at
         FROM posts p
         INNER JOIN users u ON u.id = p.user_id
         WHERE p.is_deleted = FALSE
           AND u.is_visible = TRUE
           AND (
             CHAR_LENGTH(TRIM(COALESCE(p.body, \'\'))) >= ' . SEO_POST_MIN_BODY_LENGTH . '
             OR EXISTS (SELECT 1 FROM post_media pm WHERE pm.post_id = p.id LIMIT 1)
           )
         ORDER BY p.created_at DESC
         LIMIT ' . SEO_SITEMAP_POST_LIMIT
    );
    $postStmt->execute();
    while ($row = $postStmt->fetch()) {
        if ($row === false) {
            break;
        }
        $postId = (int) ($row['id'] ?? 0);
        if ($postId < 1) {
            continue;
        }
        $entries[] = [
            'loc' => appAbsoluteUrl('/post/' . $postId),
            'lastmod' => seoFormatSitemapDate((string) ($row['created_at'] ?? '')),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];
    }

    if (hashtagsAreAvailable()) {
        $hashtagStmt = $pdo->prepare(
            'SELECT tag
             FROM hashtags
             WHERE post_count > 0
             ORDER BY post_count DESC, tag ASC
             LIMIT ' . SEO_SITEMAP_HASHTAG_LIMIT
        );
        $hashtagStmt->execute();
        while ($row = $hashtagStmt->fetch()) {
            if ($row === false) {
                break;
            }
            $tag = (string) ($row['tag'] ?? '');
            if ($tag === '') {
                continue;
            }
            $entries[] = [
                'loc' => appAbsoluteUrl(hashtagUrlPath($tag)),
                'lastmod' => gmdate('Y-m-d'),
                'changefreq' => 'daily',
                'priority' => '0.6',
            ];
        }
    }

    return $entries;
}

function seoFormatSitemapDate(string $timestamp): string
{
    if ($timestamp === '') {
        return gmdate('Y-m-d');
    }

    try {
        return (new DateTimeImmutable($timestamp))->format('Y-m-d');
    } catch (Exception) {
        return gmdate('Y-m-d');
    }
}

function renderSitemapXml(): void
{
    $entries = fetchSitemapEntries();

    http_response_code(200);
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($entries as $entry) {
        echo '  <url>' . "\n";
        echo '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>' . "\n";
        echo '    <lastmod>' . htmlspecialchars($entry['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</lastmod>' . "\n";
        echo '    <changefreq>' . htmlspecialchars($entry['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</changefreq>' . "\n";
        echo '    <priority>' . htmlspecialchars($entry['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</priority>' . "\n";
        echo '  </url>' . "\n";
    }

    echo '</urlset>' . "\n";
}

/**
 * Apply page_title from SEO bundle when present.
 *
 * @param array<string, mixed> $pageSeo
 */
function seoApplyPageTitle(array $pageSeo, string $fallbackTitle): string
{
    $title = trim((string) ($pageSeo['page_title'] ?? ''));

    return $title !== '' ? $title : $fallbackTitle;
}
