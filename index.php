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

if ($path === '/register') {
    if (isLoggedIn()) {
        header('Location: ' . $url('/'));
        exit;
    }
    require __DIR__ . '/auth/register-page.php';
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

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TheSocialNetworkApp</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;500;600;700;800;900;1000&family=Nunito+Sans:opsz,wght@6..12,200;6..12,300;6..12,400;6..12,500;6..12,600;6..12,700;6..12,800;6..12,900;6..12,1000&display=swap">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url('/assets/css/main.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body<?php echo $isLoggedIn ? '' : ' class="auth-locked"'; ?>>
    <?php if (!$isLoggedIn) {
        require __DIR__ . '/auth/login-modal.php';
    } ?>
    <div class="glass-overlay"<?php echo $isLoggedIn ? '' : ' aria-hidden="true"'; ?>>
        <div class="app-container">
            <header class="app-topbar">
                <div class="topbar-sidebar">
                    <a href="#" class="topbar-logo" aria-label="TheSocialNetworkApp">
                        <img src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="TheSocialNetworkApp logo">
                    </a>
                </div>
                <div class="topbar-content">
                    <nav class="app-topbar-nav" aria-label="Primary navigation">
                        <a href="#" class="topbar-link is-active">
                            <i data-lucide="house" aria-hidden="true"></i>
                            <span>Home</span>
                        </a>
                        <a href="#" class="topbar-link">
                            <i data-lucide="compass" aria-hidden="true"></i>
                            <span>Explore</span>
                        </a>
                        <a href="#" class="topbar-link">
                            <i data-lucide="message-circle" aria-hidden="true"></i>
                            <span>Messages</span>
                        </a>
                        <a href="#" class="topbar-link">
                            <i data-lucide="bell" aria-hidden="true"></i>
                            <span>Notifications</span>
                        </a>
                        <a href="#" class="topbar-link">
                            <i data-lucide="user-round" aria-hidden="true"></i>
                            <span>Profile</span>
                        </a>
                    </nav>
                </div>
            </header>

            <div class="app-main">
                <aside class="app-sidebar">
                    <article class="profile-card">
                        <img
                            class="profile-card-avatar"
                            src="https://pub-a912eacf8fe9461083def05076743bb3.r2.dev/assets/romeo-leaupepe-su-a-70gb9CHBX4g-unsplash.jpg"
                            alt="User avatar placeholder"
                        >
                        <h2 class="profile-card-name">User Name</h2>
                        <p class="profile-card-handle">@username</p>
                        <p class="profile-card-location">Croatia</p>
                        <p class="profile-card-bio">UI, UX Designer and Web Developer from Croatia</p>
                        <div class="profile-card-stats" aria-label="Profile stats">
                            <div class="profile-card-stat">
                                <span class="profile-card-stat-label">Posts</span>
                                <span class="profile-card-stat-value">19</span>
                            </div>
                            <div class="profile-card-stat">
                                <span class="profile-card-stat-label">Followers</span>
                                <span class="profile-card-stat-value">499</span>
                            </div>
                            <div class="profile-card-stat">
                                <span class="profile-card-stat-label">Following</span>
                                <span class="profile-card-stat-value">46</span>
                            </div>
                        </div>
                    </article>
                </aside>
                <main class="app-content">
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
                </main>
            </div>
        </div>
    </div>
    <?php if ($isLoggedIn) : ?>
    <script src="https://unpkg.com/lucide@0.544.0/dist/umd/lucide.min.js"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/post-composer.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script>
        lucide.createIcons();
    </script>
    <?php else : ?>
    <script>
        window.APP_LOGIN_URL = <?php echo json_encode($url('/auth/login'), JSON_THROW_ON_ERROR); ?>;
    </script>
    <script src="<?php echo htmlspecialchars($url('/auth/js/login.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endif; ?>
</body>
</html>
