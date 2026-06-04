<?php

declare(strict_types=1);

/** @var bool $isLoggedIn */
/** @var callable(string): string $url */
/** @var string $activeNav explore|profile|messages|notifications */
/** @var string $mainClass */

$activeNav = $activeNav ?? 'explore';
$onboardingLayout = !empty($onboardingLayout);
$layoutHasRightSidebar = !empty($layoutHasRightSidebar);
$appTopbarClass = $layoutHasRightSidebar ? 'app-topbar app-topbar--with-right-sidebar' : 'app-topbar';
$appMainClass = $layoutHasRightSidebar ? 'app-main app-main--with-right-sidebar' : 'app-main';

$navLinkClass = static function (string $item) use ($activeNav): string {
    return $item === $activeNav ? 'topbar-link is-active' : 'topbar-link';
};
?>
<body<?php
if (!$isLoggedIn) {
    echo ' class="auth-locked"';
} elseif ($onboardingLayout) {
    echo ' class="onboarding-page"';
}
?>>
    <?php if (!$isLoggedIn) {
        require dirname(__DIR__, 2) . '/auth/login-modal.php';
    } ?>
    <div class="glass-overlay"<?php echo $isLoggedIn ? '' : ' aria-hidden="true"'; ?>>
        <div class="app-container<?php echo $onboardingLayout ? ' app-container--onboarding' : ''; ?>">
            <?php if (!$onboardingLayout) : ?>
            <header class="<?php echo htmlspecialchars($appTopbarClass, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="topbar-sidebar">
                    <a href="<?php echo htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="topbar-logo" aria-label="TheSocialNetworkApp">
                        <img src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="TheSocialNetworkApp logo">
                    </a>
                </div>
                <div class="topbar-content">
<?php
$primaryNavClass = 'app-topbar-nav';
require __DIR__ . '/primary-nav.php';
?>
                </div>
                <div class="topbar-end">
                    <div class="topbar-end-desktop">
                        <?php require __DIR__ . '/theme-toggle.php'; ?>
                        <?php if ($isLoggedIn) :
                            $logoutCsrfToken = createCsrfToken('logout');
                        ?>
                        <form
                            method="post"
                            action="<?php echo htmlspecialchars($url('/logout'), ENT_QUOTES, 'UTF-8'); ?>"
                            class="topbar-logout-form"
                        >
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($logoutCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="topbar-link">
                                <i data-lucide="log-out" aria-hidden="true"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <button
                        type="button"
                        class="topbar-mobile-menu-btn"
                        id="app-mobile-nav-open"
                        aria-controls="app-mobile-nav-panel"
                        aria-expanded="false"
                        aria-label="Open menu"
                    >
                        <i data-lucide="menu" aria-hidden="true"></i>
                    </button>
                </div>
            </header>
<?php require __DIR__ . '/mobile-nav.php'; ?>

            <div class="<?php echo htmlspecialchars($appMainClass, ENT_QUOTES, 'UTF-8'); ?>">
                <?php require __DIR__ . '/sidebar.php'; ?>
            <?php endif; ?>
                <main class="<?php echo htmlspecialchars($mainClass, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($onboardingLayout) {
                require __DIR__ . '/onboarding-chrome.php';
            } ?>
                    <div class="onboarding-content-card">
