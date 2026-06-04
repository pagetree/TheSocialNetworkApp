<?php

declare(strict_types=1);

/** @var callable(string): string $url */
$platformStats = fetchPublicPlatformStats();
$appTheme = resolveAppTheme();

/** Unsplash stat card backgrounds — swap when you have final images. */
$welcomeStatCards = [
    [
        'key' => 'members',
        'icon' => 'users',
        'label' => 'Members',
        'image' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=800&h=1000&q=80',
    ],
    [
        'key' => 'posts',
        'icon' => 'message-square',
        'label' => 'Posts',
        'image' => 'https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?auto=format&fit=crop&w=800&h=1000&q=80',
    ],
    [
        'key' => 'replies',
        'icon' => 'reply',
        'label' => 'Replies',
        'image' => 'https://images.unsplash.com/photo-1604881988758-f76ad2f7aac1?auto=format&fit=crop&w=800&h=1000&q=80',
    ],
    [
        'key' => 'hashtags',
        'icon' => 'hash',
        'label' => 'Hashtags',
        'image' => 'https://images.unsplash.com/photo-1611162618479-ee3d24aaef0b?auto=format&fit=crop&w=800&h=1000&q=80',
    ],
];

$welcomePromiseBadges = [
    ['label' => 'Join free', 'dot' => '#03A9F4'],
    ['label' => 'Always free', 'dot' => '#4CAF50'],
    ['label' => 'No ads', 'dot' => '#FF9800'],
    ['label' => 'No paywalls', 'dot' => '#E91E63'],
    ['label' => 'No premium', 'dot' => '#9C27B0'],
    ['label' => 'Free speech', 'dot' => '#F44336'],
    ['label' => 'Human mods', 'dot' => '#00BCD4'],
    ['label' => 'Full reach', 'dot' => '#FF5722'],
    ['label' => 'No algorithms', 'dot' => '#3F51B5'],
    ['label' => 'No selling', 'dot' => '#795548'],
];
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
    <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;500;600;700;800;900;1000&family=Nunito+Sans:opsz,wght@6..12,200;6..12,300;6..12,400;6..12,500;6..12,600;6..12,700;6..12,800;6..12,900;6..12,1000&display=swap">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url('/assets/css/variables.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url('/assets/css/welcome.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="welcome-page">
    <div class="welcome-shell">
        <header class="welcome-header">
            <img
                class="welcome-logo"
                src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>"
                alt=""
                width="96"
                height="96"
            >
            <div class="welcome-title-wrap">
                <h1 class="welcome-title">Dots</h1>
            </div>
        </header>
        <main class="welcome-main">
            <div class="welcome-card">
                <div class="welcome-stats-head">
                    <div class="welcome-stats-head-copy">
                        <h2 class="welcome-stats-title">The problem today.</h2>
                        <p class="welcome-stats-lead">Facebook is everything but a social network anymore. X promised freedom of speech, then put reach behind a paywall: $9 a month if you want people to see your posts. We are Dots, no ads, no paywalls, no premium. Just the people.</p>
                    </div>
                    <a href="<?php echo htmlspecialchars($url('/register'), ENT_QUOTES, 'UTF-8'); ?>" class="welcome-btn welcome-btn--dark">Join Dots.</a>
                </div>
                <ul class="welcome-stats" aria-label="Community stats">
<?php foreach ($welcomeStatCards as $welcomeStatCard) {
    $statKey = (string) $welcomeStatCard['key'];
    $statCount = formatPublicStatCount((int) ($platformStats[$statKey] ?? 0));
    $statLabel = (string) $welcomeStatCard['label'];
    $statIcon = (string) $welcomeStatCard['icon'];
    $statImage = (string) $welcomeStatCard['image'];
    $statAria = $statLabel . ', ' . $statCount;
    $statBgStyle = 'background-image: url(' . json_encode($statImage, JSON_THROW_ON_ERROR) . ')';
    ?>
                    <li
                        class="welcome-stat-card"
                        style="<?php echo htmlspecialchars($statBgStyle, ENT_QUOTES, 'UTF-8'); ?>"
                        aria-label="<?php echo htmlspecialchars($statAria, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <span class="welcome-stat-icon" aria-hidden="true"><i data-lucide="<?php echo htmlspecialchars($statIcon, ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                        <div class="welcome-stat-copy">
                            <span class="welcome-stat-value"><?php echo htmlspecialchars($statCount, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="welcome-stat-name"><?php echo htmlspecialchars($statLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </li>
<?php } ?>
                </ul>
                <section class="welcome-promises" aria-labelledby="welcome-promises-title">
                    <div class="welcome-promises-head">
                        <h2 id="welcome-promises-title" class="welcome-stats-title">What Dots stands for.</h2>
                        <p class="welcome-stats-lead">No tricks, no upsells — just a social network that works for people, not advertisers.</p>
                    </div>
                    <ul class="welcome-promises-badges">
<?php foreach ($welcomePromiseBadges as $welcomePromiseBadge) {
    $badgeLabel = (string) $welcomePromiseBadge['label'];
    $badgeDot = (string) $welcomePromiseBadge['dot'];
    ?>
                        <li class="welcome-promise-badge">
                            <span class="welcome-promise-dot" style="<?php echo htmlspecialchars('background-color: ' . $badgeDot, ENT_QUOTES, 'UTF-8'); ?>"></span>
                            <span class="welcome-promise-label"><?php echo htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
<?php } ?>
                    </ul>
                </section>
                <div class="welcome-cta">
                    <a href="<?php echo htmlspecialchars($url('/register'), ENT_QUOTES, 'UTF-8'); ?>" class="welcome-btn welcome-btn--dark">Join Dots.</a>
                </div>
            </div>
        </main>
    </div>
    <script src="https://unpkg.com/lucide@0.544.0/dist/umd/lucide.min.js"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/icons.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
