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
        'label_key' => 'welcome.stats.members',
        'image' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=800&h=1000&q=80',
    ],
    [
        'key' => 'posts',
        'icon' => 'message-square',
        'label_key' => 'welcome.stats.posts',
        'image' => 'https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?auto=format&fit=crop&w=800&h=1000&q=80',
    ],
    [
        'key' => 'replies',
        'icon' => 'reply',
        'label_key' => 'welcome.stats.replies',
        'image' => 'https://images.unsplash.com/photo-1604881988758-f76ad2f7aac1?auto=format&fit=crop&w=800&h=1000&q=80',
    ],
    [
        'key' => 'hashtags',
        'icon' => 'hash',
        'label_key' => 'welcome.stats.hashtags',
        'image' => 'https://images.unsplash.com/photo-1611162618479-ee3d24aaef0b?auto=format&fit=crop&w=800&h=1000&q=80',
    ],
];

$welcomePromiseBadgeKeys = [
    'welcome.badges.join_free',
    'welcome.badges.always_free',
    'welcome.badges.no_ads',
    'welcome.badges.no_paywalls',
    'welcome.badges.no_premium',
    'welcome.badges.free_speech',
    'welcome.badges.human_mods',
    'welcome.badges.full_reach',
    'welcome.badges.no_algorithms',
    'welcome.badges.no_selling',
];

$welcomePromiseBadgeDots = [
    '#03A9F4',
    '#4CAF50',
    '#FF9800',
    '#E91E63',
    '#9C27B0',
    '#F44336',
    '#00BCD4',
    '#FF5722',
    '#3F51B5',
    '#795548',
];
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars(appHtmlLang(), ENT_QUOTES, 'UTF-8'); ?>" data-theme="<?php echo htmlspecialchars($appTheme, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php renderThemeHeadScript(); ?>
    <title><?php echo __e('meta.welcome_title'); ?></title>
<?php renderAppI18nScript(); ?>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;500;600;700;800;900;1000&family=Nunito+Sans:opsz,wght@6..12,200;6..12,300;6..12,400;6..12,500;6..12,600;6..12,700;6..12,800;6..12,900;6..12,1000&display=swap">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url('/assets/css/variables.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url('/assets/css/lang-switcher.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url('/assets/css/welcome.css'), ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="welcome-page">
    <div class="welcome-shell">
        <header class="welcome-header">
            <?php require __DIR__ . '/../includes/layout/lang-switcher.php'; ?>
            <img
                class="welcome-logo"
                src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>"
                alt=""
                width="96"
                height="96"
            >
            <div class="welcome-title-wrap">
                <h1 class="welcome-title"><?php echo __e('meta.site_name'); ?></h1>
            </div>
        </header>
        <main class="welcome-main">
            <div class="welcome-card">
                <div class="welcome-stats-head">
                    <div class="welcome-stats-head-copy">
                        <h2 class="welcome-stats-title"><?php echo __e('welcome.problem_title'); ?></h2>
                        <p class="welcome-stats-lead"><?php echo __e('welcome.problem_lead'); ?></p>
                    </div>
                    <a href="<?php echo htmlspecialchars($url('/register'), ENT_QUOTES, 'UTF-8'); ?>" class="welcome-btn welcome-btn--dark"><?php echo __e('welcome.join'); ?></a>
                </div>
                <ul class="welcome-stats" aria-label="<?php echo __e('welcome.stats.aria'); ?>">
<?php foreach ($welcomeStatCards as $welcomeStatCard) {
    $statKey = (string) $welcomeStatCard['key'];
    $statCount = formatPublicStatCount((int) ($platformStats[$statKey] ?? 0));
    $statLabel = __((string) $welcomeStatCard['label_key']);
    $statIcon = (string) $welcomeStatCard['icon'];
    $statImage = (string) $welcomeStatCard['image'];
    $statAria = __('welcome.stats.count_aria', ['label' => $statLabel, 'count' => $statCount]);
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
                        <h2 id="welcome-promises-title" class="welcome-stats-title"><?php echo __e('welcome.promises_title'); ?></h2>
                        <p class="welcome-stats-lead"><?php echo __e('welcome.promises_lead'); ?></p>
                    </div>
                    <ul class="welcome-promises-badges">
<?php foreach ($welcomePromiseBadgeKeys as $badgeIndex => $badgeLabelKey) {
    $badgeLabel = __($badgeLabelKey);
    $badgeDot = $welcomePromiseBadgeDots[$badgeIndex] ?? '#03A9F4';
    ?>
                        <li class="welcome-promise-badge">
                            <span class="welcome-promise-dot" style="<?php echo htmlspecialchars('background-color: ' . $badgeDot, ENT_QUOTES, 'UTF-8'); ?>"></span>
                            <span class="welcome-promise-label"><?php echo htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
<?php } ?>
                    </ul>
                </section>
                <div class="welcome-cta">
                    <a href="<?php echo htmlspecialchars($url('/register'), ENT_QUOTES, 'UTF-8'); ?>" class="welcome-btn welcome-btn--dark"><?php echo __e('welcome.join'); ?></a>
                </div>
            </div>
        </main>
    </div>
    <script src="https://unpkg.com/lucide@0.544.0/dist/umd/lucide.min.js"></script>
    <script src="<?php echo htmlspecialchars($url('/assets/js/icons.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
