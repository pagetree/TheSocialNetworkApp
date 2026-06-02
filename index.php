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
                            src="https://pub-a912eacf8fe9461083def05076743bb3.r2.dev/assets/romeo-leaupepe-su-a-70gb9CHBX4g-unsplash.jpg"
                            alt="Your avatar"
                        >
                        <div class="post-composer-body">
                            <div class="post-composer-box">
                                <textarea
                                    class="post-composer-input"
                                    rows="3"
                                    maxlength="300"
                                    placeholder="What's happening?"
                                    aria-describedby="post-char-counter-label"
                                ></textarea>
                                <div class="post-composer-actions">
                                    <div class="post-composer-tools" aria-label="Post tools">
                                        <button type="button" class="post-tool-btn" aria-label="Add image">
                                            <i data-lucide="image" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="post-tool-btn" aria-label="Add GIF">
                                            <i data-lucide="film" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="post-tool-btn" aria-label="Add poll">
                                            <i data-lucide="chart-no-axes-column" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="post-tool-btn" aria-label="Add emoji">
                                            <i data-lucide="smile" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="post-tool-btn" aria-label="Add location">
                                            <i data-lucide="map-pin" aria-hidden="true"></i>
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
                                        <button type="button" class="post-submit-btn">Post</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="post-card">
                        <header class="post-header">
                            <img
                                class="post-avatar"
                                src="https://placehold.co/96x96/png"
                                alt="Ecommerce Industry avatar"
                            >
                            <div class="post-meta">
                                <p class="post-meta-line">
                                    <span class="post-author">Ecommerce Industry</span>
                                    <span class="post-handle">@ecommerce__industry</span>
                                    <time class="post-time" datetime="PT13M">13 minutes ago</time>
                                </p>
                            </div>
                            <button type="button" class="post-menu-btn" aria-label="Post options">
                                <i data-lucide="ellipsis" aria-hidden="true"></i>
                            </button>
                        </header>
                        <p class="post-text">The Ecommerce Industry is booming. Are you ready to take advantage of this growing market?</p>
                        <footer class="post-actions" aria-label="Post engagement">
                            <button type="button" class="post-action"><i data-lucide="message-circle" aria-hidden="true"></i><span>20.5K</span></button>
                            <button type="button" class="post-action"><i data-lucide="repeat-2" aria-hidden="true"></i><span>36.1K</span></button>
                            <button type="button" class="post-action"><i data-lucide="heart" aria-hidden="true"></i><span>9.2K</span></button>
                            <button type="button" class="post-action"><i data-lucide="bar-chart-2" aria-hidden="true"></i><span>1.1M</span></button>
                        </footer>
                    </article>

                    <article class="post-card">
                        <header class="post-header">
                            <img
                                class="post-avatar"
                                src="https://placehold.co/96x96/png"
                                alt="Entrepreneur avatar"
                            >
                            <div class="post-meta">
                                <p class="post-meta-line">
                                    <span class="post-author">Entrepreneur</span>
                                    <span class="post-handle">@EntrepreneurQ</span>
                                    <time class="post-time" datetime="PT1H">1 hour ago</time>
                                </p>
                            </div>
                            <button type="button" class="post-menu-btn" aria-label="Post options">
                                <i data-lucide="ellipsis" aria-hidden="true"></i>
                            </button>
                        </header>
                        <p class="post-text">How to build your online business from zero to hero.</p>
                        <img
                            class="post-media"
                            src="https://placehold.co/900x500/1a2a38/d9d9d9?text=Post+Media"
                            alt="Post media"
                        >
                        <footer class="post-actions" aria-label="Post engagement">
                            <button type="button" class="post-action"><i data-lucide="message-circle" aria-hidden="true"></i><span>1.2K</span></button>
                            <button type="button" class="post-action"><i data-lucide="repeat-2" aria-hidden="true"></i><span>4.8K</span></button>
                            <button type="button" class="post-action"><i data-lucide="heart" aria-hidden="true"></i><span>12.4K</span></button>
                            <button type="button" class="post-action"><i data-lucide="bar-chart-2" aria-hidden="true"></i><span>210K</span></button>
                        </footer>
                    </article>

                    <article class="post-card">
                        <header class="post-header">
                            <img
                                class="post-avatar"
                                src="https://placehold.co/96x96/png"
                                alt="CNN avatar"
                            >
                            <div class="post-meta">
                                <p class="post-meta-line">
                                    <span class="post-author">CNN</span>
                                    <span class="post-handle">@CNN</span>
                                    <time class="post-time" datetime="PT2H">2 hours ago</time>
                                </p>
                            </div>
                            <button type="button" class="post-menu-btn" aria-label="Post options">
                                <i data-lucide="ellipsis" aria-hidden="true"></i>
                            </button>
                        </header>
                        <p class="post-text">Breaking news and top stories from around the world.</p>
                        <img
                            class="post-media"
                            src="https://placehold.co/900x500/203447/d9d9d9?text=CNN+Media"
                            alt="CNN post media"
                        >
                        <footer class="post-actions" aria-label="Post engagement">
                            <button type="button" class="post-action"><i data-lucide="message-circle" aria-hidden="true"></i><span>8.4K</span></button>
                            <button type="button" class="post-action"><i data-lucide="repeat-2" aria-hidden="true"></i><span>15.2K</span></button>
                            <button type="button" class="post-action"><i data-lucide="heart" aria-hidden="true"></i><span>42.7K</span></button>
                            <button type="button" class="post-action"><i data-lucide="bar-chart-2" aria-hidden="true"></i><span>3.6M</span></button>
                        </footer>
                    </article>
<?php
require __DIR__ . '/includes/layout/content-area-end.php';
