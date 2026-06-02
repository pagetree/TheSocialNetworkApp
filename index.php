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

if ($path === '/posts/create' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/post-create.php';
    return;
}

if ($path === '/posts/stats' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require __DIR__ . '/auth/post-stats.php';
    return;
}

if ($path === '/register') {
    if (isLoggedIn()) {
        header('Location: ' . $url('/'));
        exit;
    }
    require __DIR__ . '/auth/register-page.php';
    return;
}

if ($path === '/profile.php') {
    header('Location: ' . $url('/profile'), true, 301);
    exit;
}

if ($path === '/profile') {
    $currentUser = getCurrentUser();
    $isLoggedIn = $currentUser !== null;
    $loginCsrfToken = $isLoggedIn ? '' : createCsrfToken('login');
    $profileUser = null;
    $profileCsrfToken = '';

    if ($isLoggedIn) {
        $freshUser = fetchUserById((int) $currentUser['id']);
        if ($freshUser !== null) {
            loginUser($freshUser);
            $profileUser = $freshUser;
        } else {
            $profileUser = $currentUser;
        }
        $profileCsrfToken = createCsrfToken('profile_edit');
    }

    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/profile.php';
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
$currentUserId = $isLoggedIn ? (int) $currentUser['id'] : 0;
$feedPosts = fetchFeedPosts();
$composerAvatarUrl = $isLoggedIn
    ? userMediaUrl($currentUser, 'avatar_url', $url)
    : 'https://pub-a912eacf8fe9461083def05076743bb3.r2.dev/assets/romeo-leaupepe-su-a-70gb9CHBX4g-unsplash.jpg';

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');

$pageTitle = 'TheSocialNetworkApp';
$activeNav = 'explore';
$mainClass = 'app-content';
$pageScripts = ['/assets/js/post-composer.js'];

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
    renderPostCard($feedPost, $url, $currentUserId);
} ?>
                    </div>
<?php
require __DIR__ . '/includes/layout/content-area-end.php';
