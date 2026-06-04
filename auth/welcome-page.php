<?php

declare(strict_types=1);

/** @var callable(string): string $url */
$platformStats = fetchPublicPlatformStats();
$appTheme = resolveAppTheme();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo htmlspecialchars($appTheme, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php renderThemeHeadScript(); ?>
    <title>Welcome — Dots</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;500;600;700;800;900;1000&family=Nunito+Sans:opsz,wght@6..12,200;6..12,300;6..12,400;6..12,500;6..12,600;6..12,700;6..12,800;6..12,900;6..12,1000&display=swap">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url('/assets/css/variables.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url('/assets/css/welcome.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="welcome-page">
    <div class="welcome-shell">
        <main class="welcome-main">
            <div class="welcome-card">
                <img
                    class="welcome-logo"
                    src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>"
                    alt="Dots"
                    width="96"
                    height="96"
                >
                <div class="welcome-title-wrap">
                    <h1 class="welcome-title">
                        <span class="welcome-title-word">Dots</span><span class="welcome-brand-dot" aria-hidden="true"></span>
                    </h1>
                </div>
                <p class="welcome-subtitle">Closed beta. Only a few spots left. Sign up, show up, and help us build the social network you actually want to use.</p>
                <dl class="welcome-stats" aria-label="Community stats">
                    <div class="welcome-stat">
                        <dt class="welcome-stat-label">
                            <i data-lucide="users" aria-hidden="true"></i>
                            <span>Members</span>
                        </dt>
                        <dd class="welcome-stat-value"><?php echo htmlspecialchars(formatPublicStatCount($platformStats['members']), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="welcome-stat">
                        <dt class="welcome-stat-label">
                            <i data-lucide="message-square" aria-hidden="true"></i>
                            <span>Posts</span>
                        </dt>
                        <dd class="welcome-stat-value"><?php echo htmlspecialchars(formatPublicStatCount($platformStats['posts']), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="welcome-stat">
                        <dt class="welcome-stat-label">
                            <i data-lucide="reply" aria-hidden="true"></i>
                            <span>Replies</span>
                        </dt>
                        <dd class="welcome-stat-value"><?php echo htmlspecialchars(formatPublicStatCount($platformStats['replies']), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="welcome-stat">
                        <dt class="welcome-stat-label">
                            <i data-lucide="hash" aria-hidden="true"></i>
                            <span>Hashtags</span>
                        </dt>
                        <dd class="welcome-stat-value"><?php echo htmlspecialchars(formatPublicStatCount($platformStats['hashtags']), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                    <div class="welcome-stat">
                        <dt class="welcome-stat-label">
                            <i data-lucide="heart" aria-hidden="true"></i>
                            <span>Likes</span>
                        </dt>
                        <dd class="welcome-stat-value"><?php echo htmlspecialchars(formatPublicStatCount($platformStats['likes']), ENT_QUOTES, 'UTF-8'); ?></dd>
                    </div>
                </dl>
                <div class="welcome-actions">
                    <a href="<?php echo htmlspecialchars($url('/register'), ENT_QUOTES, 'UTF-8'); ?>" class="welcome-btn welcome-btn--primary">Create account</a>
                    <a href="<?php echo htmlspecialchars($url('/login'), ENT_QUOTES, 'UTF-8'); ?>" class="welcome-login-link">Sign in</a>
                </div>
            </div>
        </main>
    </div>
    <script src="https://unpkg.com/lucide@0.544.0/dist/umd/lucide.min.js"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/icons.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
