<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
$url = static fn(string $uri): string => $basePath . $uri;

if ($path === '/health') {
    jsonResponse([
        'status' => 'ok',
        'service' => 'TheSocialNetworkApp',
    ]);
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

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TheSocialNetworkApp</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url('/assets/css/main.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <a class="brand" href="<?php echo htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8'); ?>">
                <span class="material-symbols-outlined">hub</span>
                <span>SocialNet</span>
            </a>
            <nav class="nav-links" aria-label="Primary">
                <a href="#features">Features</a>
                <a href="#communities">Communities</a>
                <a href="#safety">Safety</a>
            </nav>
            <div class="nav-actions">
                <a class="btn btn-ghost" href="#">Log in</a>
                <a class="btn btn-primary" href="#">Join now</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Built for real connections</p>
                    <h1>Meet, share, and grow with your people.</h1>
                    <p class="lead">
                        A modern social network experience where communities feel safe, profiles feel personal,
                        and conversations actually matter.
                    </p>
                    <div class="hero-actions">
                        <a class="btn btn-primary btn-lg" href="#">Create account</a>
                        <a class="btn btn-ghost btn-lg" href="#features">Explore features</a>
                    </div>
                    <div class="hero-stats">
                        <div>
                            <strong>120K+</strong>
                            <span>Daily posts</span>
                        </div>
                        <div>
                            <strong>4.8/5</strong>
                            <span>User trust score</span>
                        </div>
                        <div>
                            <strong>180+</strong>
                            <span>Active communities</span>
                        </div>
                    </div>
                </div>
                <div class="hero-card-wrap" aria-hidden="true">
                    <article class="hero-card">
                        <header>
                            <img src="https://placehold.co/56x56" alt="" width="56" height="56">
                            <div>
                                <h3>@alex.m</h3>
                                <p>Design Circle</p>
                            </div>
                        </header>
                        <p>
                            "Shipped the new onboarding today. Feedback has been amazing -
                            drop your thoughts below."
                        </p>
                        <footer>
                            <span class="material-symbols-outlined">favorite</span>
                            <span>1.2K</span>
                            <span class="material-symbols-outlined">chat_bubble</span>
                            <span>248</span>
                            <span class="material-symbols-outlined">share</span>
                            <span>64</span>
                        </footer>
                    </article>
                </div>
            </div>
        </section>

        <section id="features" class="section">
            <div class="container">
                <div class="section-head">
                    <p class="eyebrow">Everything you need</p>
                    <h2>Core features for a thriving social app</h2>
                </div>
                <div class="feature-grid">
                    <article class="feature-card">
                        <span class="material-symbols-outlined">groups</span>
                        <h3>Community Spaces</h3>
                        <p>Create topic-based spaces with private and public visibility options.</p>
                    </article>
                    <article class="feature-card">
                        <span class="material-symbols-outlined">shield_lock</span>
                        <h3>Trust & Safety</h3>
                        <p>Moderation controls, reporting flows, and role-based permissions.</p>
                    </article>
                    <article class="feature-card">
                        <span class="material-symbols-outlined">auto_awesome</span>
                        <h3>Smart Feed</h3>
                        <p>Personalized timeline balancing your friends, creators, and interests.</p>
                    </article>
                    <article class="feature-card">
                        <span class="material-symbols-outlined">forum</span>
                        <h3>Real Conversations</h3>
                        <p>Threads, reactions, and rich comments designed for meaningful discussion.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="communities" class="section alt">
            <div class="container">
                <div class="section-head">
                    <p class="eyebrow">Grow together</p>
                    <h2>Made for creators, groups, and teams</h2>
                </div>
                <div class="pill-row">
                    <span>Creator Hubs</span>
                    <span>Topic Channels</span>
                    <span>Events</span>
                    <span>Mentions</span>
                    <span>Verified Profiles</span>
                </div>
            </div>
        </section>

        <section id="safety" class="section cta">
            <div class="container cta-box">
                <h2>Launch your network with confidence</h2>
                <p>Production-ready scaffold powered by Railway + PostgreSQL.</p>
                <a class="btn btn-primary btn-lg" href="<?php echo htmlspecialchars($url('/db-check'), ENT_QUOTES, 'UTF-8'); ?>">Test database connection</a>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-row">
            <p>&copy; <?php echo date('Y'); ?> TheSocialNetworkApp</p>
            <div class="footer-links">
                <a href="<?php echo htmlspecialchars($url('/health'), ENT_QUOTES, 'UTF-8'); ?>">Health</a>
                <a href="<?php echo htmlspecialchars($url('/db-check'), ENT_QUOTES, 'UTF-8'); ?>">DB Check</a>
            </div>
        </div>
    </footer>
</body>
</html>
