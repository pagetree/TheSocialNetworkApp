<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var bool $isLoggedIn */
/** @var string $activeNav */

$activeNav = $activeNav ?? 'explore';
$sidebarNavLinkClass = static function (string $item) use ($activeNav): string {
    return $item === $activeNav ? 'app-sidebar-nav-link is-active' : 'app-sidebar-nav-link';
};

$sidebarUser = getCurrentUser();
$sidebarShowFooter = $isLoggedIn && is_array($sidebarUser);
$sidebarName = $sidebarShowFooter ? (string) ($sidebarUser['display_name'] ?? 'User') : '';
$sidebarHandle = $sidebarShowFooter ? (string) ($sidebarUser['handle'] ?? '@user') : '';
$sidebarAvatar = $sidebarShowFooter ? userMediaUrl($sidebarUser, 'avatar_url', $url) : '';
$notificationsUnreadCount = $sidebarShowFooter
    ? fetchUnreadNotificationCount((int) ($sidebarUser['id'] ?? 0))
    : 0;
?>
                <aside class="app-sidebar">
                    <header class="app-shell-header">
                        <a
                            href="<?php echo htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8'); ?>"
                            class="app-sidebar-logo"
                            aria-label="<?php echo __e('nav.home'); ?>"
                        >
                            <img
                                class="app-sidebar-logo-img app-sidebar-logo-img--default"
                                src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo __e('nav.home'); ?>"
                            >
                            <img
                                class="app-sidebar-logo-img app-sidebar-logo-img--black"
                                src="<?php echo htmlspecialchars($url('/assets/img/logo-black.png'), ENT_QUOTES, 'UTF-8'); ?>"
                                alt=""
                                aria-hidden="true"
                            >
                        </a>
                    </header>
                    <?php require __DIR__ . '/sidebar-nav.php'; ?>
                    <?php if ($sidebarShowFooter) {
                        require __DIR__ . '/sidebar-footer.php';
                    } ?>
                </aside>
