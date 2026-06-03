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

startAppSession();

if ($path === '/logout') {
    logoutUser();
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

if ($path === '/posts/reply' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/post-reply-create.php';
    return;
}

if ($path === '/posts/like' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/post-like.php';
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
        $profileCsrfToken = createCsrfToken('profile_edit');
        $postStatsCsrfToken = createCsrfToken('post_stats');
        $currentUserId = (int) $currentUser['id'];
    } else {
        $profileUser = fetchUserByUsername($profileSlug);
        if ($profileUser === null) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            $pageTitle = 'Profile not found — TheSocialNetworkApp';
            $activeNav = 'profile';
            $mainClass = 'profile-page';
            $postStatsCsrfToken = $isLoggedIn ? createCsrfToken('post_stats') : '';
            $currentUserId = $isLoggedIn ? (int) $currentUser['id'] : 0;
            $pageScripts = [];

            require __DIR__ . '/includes/layout/head.php';
            require __DIR__ . '/includes/layout/content-area-start.php';
            echo '<p class="profile-not-found">This profile could not be found.</p>';
            require __DIR__ . '/includes/layout/content-area-end.php';
            return;
        }

        $profileUserId = (int) ($profileUser['id'] ?? 0);
        $isOwnProfile = $isLoggedIn && $profileUserId === (int) $currentUser['id'];
        if ($isOwnProfile) {
            header('Location: ' . $url('/profile'));
            exit;
        }

        if ($isLoggedIn) {
            $postStatsCsrfToken = createCsrfToken('post_stats');
            $currentUserId = (int) $currentUser['id'];
            $profileFollowCsrfToken = createCsrfToken('profile_follow');
            $profileFollowUserId = $profileUserId;
            $viewerFollowsProfile = isUserFollowedBy($currentUserId, $profileUserId);
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

    if (is_array($profileUser)) {
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

    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/profile.php';
    return;
}

if (preg_match('#^/hashtag/([a-z0-9_]{1,50})/?$#', $path, $hashtagRouteMatch)) {
    $hashtagTag = normalizeHashtagTag((string) $hashtagRouteMatch[1]);
    if ($hashtagTag === '') {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        $pageTitle = 'Hashtag not found — TheSocialNetworkApp';
        $activeNav = 'explore';
        $mainClass = 'app-content hashtag-page';
        $currentUser = getCurrentUser();
        $isLoggedIn = $currentUser !== null;
        $loginCsrfToken = $isLoggedIn ? '' : createCsrfToken('login');
        $currentUserId = $isLoggedIn ? (int) $currentUser['id'] : 0;
        $pageScripts = [];

        require __DIR__ . '/includes/layout/head.php';
        require __DIR__ . '/includes/layout/content-area-start.php';
        echo '<p class="hashtag-page-empty">This hashtag is not valid.</p>';
        require __DIR__ . '/includes/layout/content-area-end.php';
        return;
    }

    $hashtagMeta = fetchHashtagByTag($hashtagTag);
    $hashtagPosts = fetchPostsByHashtag($hashtagTag);
    $hashtagPostCount = (int) ($hashtagMeta['post_count'] ?? count($hashtagPosts));

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
    $pageTitle = '#' . $hashtagTag . ' — TheSocialNetworkApp';
    $activeNav = 'explore';
    $mainClass = 'app-content hashtag-page';
    $pageScripts = ['/assets/js/who-to-follow.js'];
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
        $pageTitle = 'Post not found — TheSocialNetworkApp';
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
        echo '<p class="post-detail-empty">This post could not be found.</p>';
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

if ($path === '/db-check') {
    try {
        $pdo = createPdoConnection();
        $result = $pdo->query('SELECT NOW() AS server_time')->fetch();
        jsonResponse([
            'status' => 'ok',
            'database' => 'connected',
            'server_time' => $result['server_time'] ?? null,
        ]);
    } catch (Throwable $exception) {
        jsonResponse([
            'status' => 'error',
            'database' => 'unreachable',
            'message' => $exception->getMessage(),
        ], 500);
    }
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

$pageTitle = 'TheSocialNetworkApp';
$activeNav = 'explore';
$mainClass = 'app-content';
$pageScripts = ['/assets/js/post-composer.js', '/assets/js/who-to-follow.js'];
if ($showFeedReplyModal) {
    $pageScripts[] = '/assets/js/reply-media-picker.js';
    $pageScripts[] = '/assets/js/feed-reply-modal.js';
}

require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/content-area-start.php';
?>
                    <article class="post-card post-composer">
                        <img
                            class="post-avatar"
                            src="<?php echo htmlspecialchars($composerAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="Your avatar"
                        >
                        <div class="post-composer-body">
                            <div class="post-composer-box">
                                <textarea
                                    class="post-composer-input"
                                    id="post-composer-input"
                                    rows="3"
                                    maxlength="300"
                                    placeholder="What's happening?"
                                    aria-describedby="post-char-counter-label post-composer-error"
                                ></textarea>
                                <p class="post-composer-error" id="post-composer-error" hidden></p>
                                <div class="post-composer-media-preview" id="post-composer-media-preview" hidden>
                                    <div class="post-composer-media-grid" id="post-composer-media-grid"></div>
                                </div>
                                <input
                                    type="file"
                                    id="post-composer-media-input"
                                    name="media[]"
                                    class="post-composer-media-input"
                                    hidden
                                    multiple
                                >
                                <div class="post-composer-actions">
                                    <div class="post-composer-tools" aria-label="Post tools">
                                        <button type="button" class="post-tool-btn" id="post-composer-image-btn" aria-label="Add image">
                                            <i data-lucide="image" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="post-tool-btn" id="post-composer-video-btn" aria-label="Add video">
                                            <i data-lucide="film" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="post-composer-submit-wrap">
                                        <div
                                            class="post-char-counter"
                                            id="post-char-counter-label"
                                            role="status"
                                            aria-live="polite"
                                            hidden
                                        >
                                            <svg class="post-char-counter-ring" viewBox="0 0 36 36" aria-hidden="true">
                                                <circle class="post-char-counter-track" cx="18" cy="18" r="15.5"></circle>
                                                <circle class="post-char-counter-progress" cx="18" cy="18" r="15.5"></circle>
                                            </svg>
                                        </div>
                                        <button type="button" class="post-submit-btn" id="post-composer-submit" disabled>Post</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

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
