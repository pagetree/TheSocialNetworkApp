<?php

declare(strict_types=1);

/** @var bool $isLoggedIn */
/** @var callable(string): string $url */
/** @var string $activeNav explore|profile|messages|notifications */
/** @var string $mainClass */

$activeNav = $activeNav ?? 'explore';
$layoutHasRightSidebar = !empty($layoutHasRightSidebar);
$appTopbarClass = $layoutHasRightSidebar ? 'app-topbar app-topbar--with-right-sidebar' : 'app-topbar';
$appMainClass = $layoutHasRightSidebar ? 'app-main app-main--with-right-sidebar' : 'app-main';

$navLinkClass = static function (string $item) use ($activeNav): string {
    return $item === $activeNav ? 'topbar-link is-active' : 'topbar-link';
};
?>
<body<?php echo $isLoggedIn ? '' : ' class="auth-locked"'; ?>>
    <?php if (!$isLoggedIn) {
        require dirname(__DIR__, 2) . '/auth/login-modal.php';
    } ?>
    <div class="glass-overlay"<?php echo $isLoggedIn ? '' : ' aria-hidden="true"'; ?>>
        <div class="app-container">
            <header class="<?php echo htmlspecialchars($appTopbarClass, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="topbar-sidebar">
                    <a href="<?php echo htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="topbar-logo" aria-label="TheSocialNetworkApp">
                        <img src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="TheSocialNetworkApp logo">
                    </a>
                </div>
                <div class="topbar-content">
                    <nav class="app-topbar-nav" aria-label="Primary navigation">
                        <a href="<?php echo htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $navLinkClass('explore'); ?>">
                            <i data-lucide="compass" aria-hidden="true"></i>
                            <span>Explore</span>
                        </a>
                        <a href="#" class="<?php echo $navLinkClass('messages'); ?>">
                            <i data-lucide="message-circle" aria-hidden="true"></i>
                            <span>Messages</span>
                        </a>
                        <a href="#" class="<?php echo $navLinkClass('notifications'); ?>">
                            <i data-lucide="bell" aria-hidden="true"></i>
                            <span>Notifications</span>
                        </a>
                        <a href="<?php echo htmlspecialchars($url('/profile'), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $navLinkClass('profile'); ?>">
                            <i data-lucide="user-round" aria-hidden="true"></i>
                            <span>Profile</span>
                        </a>
                    </nav>
                </div>
                <?php if ($layoutHasRightSidebar) : ?>
                <div class="topbar-aside" aria-hidden="true"></div>
                <?php endif; ?>
            </header>

            <div class="<?php echo htmlspecialchars($appMainClass, ENT_QUOTES, 'UTF-8'); ?>">
<?php require __DIR__ . '/sidebar.php'; ?>
                <main class="<?php echo htmlspecialchars($mainClass, ENT_QUOTES, 'UTF-8'); ?>">
