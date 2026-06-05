<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$appPaths = appPaths();
$path = $appPaths['path'];
$url = $appPaths['url'];

if ($path === '/favicon.ico') {
    $favicon = __DIR__ . '/assets/img/logo.png';
    if (is_file($favicon)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=604800');
        readfile($favicon);
        return;
    }
    http_response_code(404);
    return;
}

if ($path === '/health') {
    jsonResponse([
        'status' => 'ok',
        'service' => 'TheSocialNetworkApp',
    ]);
    return;
}

if ($path === '/robots.txt') {
    renderRobotsTxt();
    return;
}

if ($path === '/sitemap.xml') {
    renderSitemapXml();
    return;
}

if (preg_match('#^/lang/(en|es)/?$#', $path, $localeRouteMatch)) {
    setAppLocaleCookie($localeRouteMatch[1]);
    header('Location: ' . localeRedirectTarget());
    exit;
}

startAppSession();

if ($path === '/logout' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $payload = authPayloadFromRequest();
    if (!validateCsrfToken(extractCsrfToken($payload), 'logout')) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo __('api.session_expired');
        exit;
    }

    consumeCsrfToken(extractCsrfToken($payload), 'logout');
    logoutUser();
    header('Location: ' . $url('/'));
    exit;
}

if ($path === '/logout') {
    header('Location: ' . $url('/'));
    exit;
}

if ($path === '/auth/login' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/login.php';
    return;
}

if ($path === '/auth/register' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/register-action.php';
    return;
}

if ($path === '/auth/check-username' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    require __DIR__ . '/auth/check-username.php';
    return;
}

if ($path === '/auth/profile' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/profile-update.php';
    return;
}

if ($path === '/auth/onboarding/avatar' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/onboarding-avatar.php';
    return;
}

if ($path === '/auth/onboarding/bio' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/onboarding-bio.php';
    return;
}

if ($path === '/auth/onboarding/interests' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/onboarding-interests.php';
    return;
}

if ($path === '/auth/onboarding/follow' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/onboarding-follow.php';
    return;
}

if ($path === '/auth/onboarding/complete' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/onboarding-complete.php';
    return;
}

if ($path === '/posts/create' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/post-create.php';
    return;
}

if ($path === '/posts/stats' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/post-stats.php';
    return;
}

if ($path === '/posts/stats/detail' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/post-stats-detail.php';
    return;
}

if ($path === '/posts/reply' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/post-reply-create.php';
    return;
}

if ($path === '/posts/like' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/post-like.php';
    return;
}

if ($path === '/posts/remove' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/post-remove.php';
    return;
}

if ($path === '/content/report' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/content-report.php';
    return;
}

if ($path === '/users/follow' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/profile-follow.php';
    return;
}

if ($path === '/register') {
    if (isLoggedIn()) {
        $registerUser = getCurrentUser();
        $registerTarget = userNeedsOnboarding($registerUser)
            ? $url('/onboarding/welcome')
            : $url('/');
        header('Location: ' . $registerTarget);
        exit;
    }
    require __DIR__ . '/auth/register-page.php';
    return;
}

if ($path === '/login') {
    if (isLoggedIn()) {
        $loginUser = getCurrentUser();
        $loginTarget = userNeedsOnboarding($loginUser)
            ? $url('/onboarding/welcome')
            : $url('/');
        header('Location: ' . $loginTarget);
        exit;
    }
    require __DIR__ . '/auth/login-page.php';
    return;
}

if (preg_match('#^/onboarding(?:/(welcome|avatar|bio|interests|suggestions))?/?$#', $path, $onboardingRouteMatch)) {
    $onboardingStep = $onboardingRouteMatch[1] ?? 'welcome';
    require __DIR__ . '/includes/onboarding/page.php';
    return;
}

$onboardingRedirect = onboardingRedirectUrlIfNeeded($path, $url);
if (
    $onboardingRedirect !== null
    && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET'
) {
    header('Location: ' . $onboardingRedirect);
    exit;
}

$guestRedirect = guestAppRedirectUrlIfNeeded($path, $url);
if (
    $guestRedirect !== null
    && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET'
) {
    header('Location: ' . $guestRedirect);
    exit;
}

if (!isLoggedIn() && $path === '/' && isset($_GET['login'])) {
    header('Location: ' . $url('/login'));
    exit;
}

if (!isLoggedIn() && $path === '/') {
    require __DIR__ . '/auth/welcome-page.php';
    return;
}

if ($path === '/profile.php') {
    header('Location: ' . $url('/profile'), true, 301);
    exit;
}

if (preg_match('#^/profile(?:/([a-z0-9_]+))?/?$#i', $path, $profileRouteMatch)) {
    $currentUser = getCurrentUser();
    $isLoggedIn = $currentUser !== null;
    $loginCsrfToken = $isLoggedIn ? '' : createCsrfToken('login');
    $profileUser = null;
    $profileCsrfToken = '';
    $profileFollowCsrfToken = '';
    $profileFollowUserId = 0;
    $isOwnProfile = false;
    $viewerFollowsProfile = false;
    $profileSlug = isset($profileRouteMatch[1]) ? normalizeUsername((string) $profileRouteMatch[1]) : '';
    $profileIsPrivate = false;

    if ($profileSlug === '') {
        if (!$isLoggedIn) {
            header('Location: ' . $url('/'));
            exit;
        }

        $freshUser = fetchUserById((int) $currentUser['id']);
        if ($freshUser !== null) {
            loginUser($freshUser);
            $profileUser = $freshUser;
        } else {
            $profileUser = $currentUser;
        }
        $isOwnProfile = true;
        $profileIsPrivate = false;
        $profileCsrfToken = createCsrfToken('profile_edit');
        $postStatsCsrfToken = createCsrfToken('post_stats');
        $currentUserId = (int) $currentUser['id'];
    } else {
        $profileUser = fetchUserByUsername($profileSlug);
        if ($profileUser === null) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            $pageTitle = __('meta.profile_not_found_title');
            $pageSeo = seoNoindexPage('/profile/' . rawurlencode($profileSlug));
            $activeNav = 'profile';
            $mainClass = 'profile-page';
            $postStatsCsrfToken = $isLoggedIn ? createCsrfToken('post_stats') : '';
            $currentUserId = $isLoggedIn ? (int) $currentUser['id'] : 0;
            $pageScripts = [];

            require __DIR__ . '/includes/layout/head.php';
            require __DIR__ . '/includes/layout/content-area-start.php';
            echo '<p class="profile-not-found">' . __e('errors.profile_not_found') . '</p>';
            require __DIR__ . '/includes/layout/content-area-end.php';
            return;
        }

        $profileUserId = (int) ($profileUser['id'] ?? 0);
        $isOwnProfile = $isLoggedIn && $profileUserId === (int) $currentUser['id'];
        if ($isOwnProfile) {
            header('Location: ' . $url('/profile'));
            exit;
        }

        $profileIsPrivate = !userProfileIsVisible($profileUser);

        if ($isLoggedIn) {
            $postStatsCsrfToken = createCsrfToken('post_stats');
            $currentUserId = (int) $currentUser['id'];
            if (!$profileIsPrivate) {
                $profileFollowCsrfToken = createCsrfToken('profile_follow');
                $profileFollowUserId = $profileUserId;
                $viewerFollowsProfile = isUserFollowedBy($currentUserId, $profileUserId);
            }
        } else {
            $postStatsCsrfToken = '';
            $currentUserId = 0;
        }
    }

    $profilePosts = [];
    $profileLikedPostIds = [];
    $postLikeCsrfToken = '';
    $replyCsrfToken = '';
    $showFeedReplyModal = false;

    if (is_array($profileUser) && !$profileIsPrivate) {
        $profilePosts = fetchPostsByUserId((int) ($profileUser['id'] ?? 0));

        if ($isLoggedIn) {
            $postLikeCsrfToken = createCsrfToken('post_like');
            $replyCsrfToken = createCsrfToken('post_reply');
            $showFeedReplyModal = true;

            if ($profilePosts !== []) {
                $profileLikedPostIds = array_flip(fetchLikedPostIdsForUser(
                    $currentUserId,
                    array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $profilePosts)
                ));
            }
        }
    }

    $composerAvatarUrl = $isLoggedIn && is_array($currentUser)
        ? userMediaUrl($currentUser, 'avatar_url', $url)
        : 'https://pub-a912eacf8fe9461083def05076743bb3.r2.dev/assets/romeo-leaupepe-su-a-70gb9CHBX4g-unsplash.jpg';
    $showPostComposerModal = $isLoggedIn;
    $postCsrfToken = $isLoggedIn ? createCsrfToken('post_create') : '';

    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/profile.php';
    return;
}

if (preg_match('#^/hashtag/([a-z0-9_]{1,50})/?$#i', $path, $hashtagRouteMatch)) {
    $hashtagTag = parseHashtagTagFromUrl((string) $hashtagRouteMatch[1]);
    if ($hashtagTag === '') {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        $pageTitle = __('meta.hashtag_invalid_title');
        $pageSeo = seoNoindexPage('/hashtag/' . rawurlencode((string) $hashtagRouteMatch[1]));
        $activeNav = 'explore';
        $contentPageTitle = __('nav.page_hashtags');
        $mainClass = 'app-content hashtag-page';
        $currentUser = getCurrentUser();
        $isLoggedIn = $currentUser !== null;
        $loginCsrfToken = $isLoggedIn ? '' : createCsrfToken('login');
        $currentUserId = $isLoggedIn ? (int) $currentUser['id'] : 0;
        $pageScripts = [];

        require __DIR__ . '/includes/layout/head.php';
        require __DIR__ . '/includes/layout/content-area-start.php';
        echo '<p class="hashtag-page-empty">' . __e('errors.hashtag_invalid') . '</p>';
        require __DIR__ . '/includes/layout/content-area-end.php';
        return;
    }

    $hashtagMeta = fetchHashtagByTag($hashtagTag);
    $hashtagPosts = fetchPostsByHashtag($hashtagTag);
    $hashtagPostCount = $hashtagMeta !== null
        ? (int) $hashtagMeta['post_count']
        : count($hashtagPosts);
    $currentUser = getCurrentUser();
    $isLoggedIn = $currentUser !== null;
    $loginCsrfToken = $isLoggedIn ? '' : createCsrfToken('login');
    $postStatsCsrfToken = $isLoggedIn ? createCsrfToken('post_stats') : '';
    $postLikeCsrfToken = $isLoggedIn ? createCsrfToken('post_like') : '';
    $replyCsrfToken = $isLoggedIn ? createCsrfToken('post_reply') : '';
    $showFeedReplyModal = $isLoggedIn;
    $currentUserId = $isLoggedIn ? (int) $currentUser['id'] : 0;
    $hashtagLikedPostIds = [];

    if ($isLoggedIn && $hashtagPosts !== []) {
        $hashtagLikedPostIds = array_flip(fetchLikedPostIdsForUser(
            $currentUserId,
            array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $hashtagPosts)
        ));
    }

    $composerAvatarUrl = $isLoggedIn && is_array($currentUser)
        ? userMediaUrl($currentUser, 'avatar_url', $url)
        : 'https://pub-a912eacf8fe9461083def05076743bb3.r2.dev/assets/romeo-leaupepe-su-a-70gb9CHBX4g-unsplash.jpg';

    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    $pageSeo = seoBuildHashtagPage($hashtagTag, $hashtagPostCount);
    $pageTitle = seoApplyPageTitle($pageSeo, __('meta.hashtag_title', ['tag' => $hashtagTag]));
    $activeNav = 'explore';
    $contentPageTitle = __('nav.page_hashtags');
    $mainClass = 'app-content hashtag-page';
    $pageScripts = [];
    if ($showFeedReplyModal) {
        $pageScripts[] = '/assets/js/reply-media-picker.js';
        $pageScripts[] = '/assets/js/feed-reply-modal.js';
    }

    require __DIR__ . '/includes/layout/head.php';
    require __DIR__ . '/includes/layout/content-area-start.php';
    require __DIR__ . '/hashtag.php';
    require __DIR__ . '/includes/layout/content-area-end.php';
    return;
}

if (preg_match('#^/post/(\d+)/?$#', $path, $postRouteMatch)) {
    $postId = (int) $postRouteMatch[1];
    $post = fetchPostById($postId);

    if ($post === null) {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        $pageTitle = __('meta.post_not_found_title');
        $pageSeo = seoNoindexPage('/post/' . $postId);
        $activeNav = 'explore';
        $mainClass = 'app-content post-detail-page';
        $currentUser = getCurrentUser();
        $isLoggedIn = $currentUser !== null;
        $loginCsrfToken = $isLoggedIn ? '' : createCsrfToken('login');
        $postStatsCsrfToken = $isLoggedIn ? createCsrfToken('post_stats') : '';
        $currentUserId = $isLoggedIn ? (int) $currentUser['id'] : 0;
        $pageScripts = [];

        require __DIR__ . '/includes/layout/head.php';
        require __DIR__ . '/includes/layout/content-area-start.php';
        echo '<p class="post-detail-empty">' . __e('errors.post_not_found') . '</p>';
        require __DIR__ . '/includes/layout/content-area-end.php';
        return;
    }

    $currentUser = getCurrentUser();
    $isLoggedIn = $currentUser !== null;
    $loginCsrfToken = $isLoggedIn ? '' : createCsrfToken('login');
    $postStatsCsrfToken = $isLoggedIn ? createCsrfToken('post_stats') : '';
    $postLikeCsrfToken = $isLoggedIn ? createCsrfToken('post_like') : '';
    $currentUserId = $isLoggedIn ? (int) $currentUser['id'] : 0;
    $pageScripts = ['/assets/js/post-reply-composer.js'];

    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/post.php';
    return;
}

$currentUser = getCurrentUser();
$isLoggedIn = $currentUser !== null;
$loginCsrfToken = $isLoggedIn ? '' : createCsrfToken('login');
$postCsrfToken = $isLoggedIn ? createCsrfToken('post_create') : '';
$postStatsCsrfToken = $isLoggedIn ? createCsrfToken('post_stats') : '';
$postLikeCsrfToken = $isLoggedIn ? createCsrfToken('post_like') : '';
$replyCsrfToken = $isLoggedIn ? createCsrfToken('post_reply') : '';
$showFeedReplyModal = $isLoggedIn;
$currentUserId = $isLoggedIn ? (int) $currentUser['id'] : 0;
$feedPosts = fetchFeedPosts();
$likedPostIds = [];
if ($isLoggedIn && $feedPosts !== []) {
    $likedPostIds = array_flip(fetchLikedPostIdsForUser(
        $currentUserId,
        array_map(static fn (array $feedPost): int => (int) ($feedPost['id'] ?? 0), $feedPosts)
    ));
}
$composerAvatarUrl = $isLoggedIn
    ? userMediaUrl($currentUser, 'avatar_url', $url)
    : 'https://pub-a912eacf8fe9461083def05076743bb3.r2.dev/assets/romeo-leaupepe-su-a-70gb9CHBX4g-unsplash.jpg';

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');

$pageTitle = __('meta.feed_title');
$pageSeo = seoNoindexPage('/');
$activeNav = 'explore';
$mainClass = 'app-content';
$showPostComposerModal = $isLoggedIn;
$pageScripts = ['/assets/js/post-composer.js'];
if ($showFeedReplyModal) {
    $pageScripts[] = '/assets/js/reply-media-picker.js';
    $pageScripts[] = '/assets/js/feed-reply-modal.js';
}

require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/content-area-start.php';
?>
<?php if ($showPostComposerModal) {
    require __DIR__ . '/includes/posts/post-composer-modal.php';
} else {
    require __DIR__ . '/includes/posts/feed-post-composer.php';
} ?>

                    <div class="post-feed" id="post-feed">
<?php foreach ($feedPosts as $feedPost) {
    renderPostCard(
        $feedPost,
        $url,
        $currentUserId,
        isset($likedPostIds[(int) ($feedPost['id'] ?? 0)])
    );
} ?>
                    </div>
<?php
require __DIR__ . '/includes/layout/content-area-end.php';
