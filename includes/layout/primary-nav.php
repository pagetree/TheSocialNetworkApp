<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var callable(string): string $navLinkClass */
/** @var string $primaryNavClass */
/** @var string $activeNav */

$primaryNavClass = $primaryNavClass ?? 'app-topbar-nav';
$notificationsUnreadCount = $notificationsUnreadCount ?? (
    !empty($isLoggedIn) && isLoggedIn()
        ? fetchUnreadNotificationCount((int) (getCurrentUser()['id'] ?? 0))
        : 0
);
$showNotificationsBadge = ($notificationsUnreadCount ?? 0) > 0 && ($activeNav ?? '') !== 'notifications';
?>
                    <nav class="<?php echo htmlspecialchars($primaryNavClass, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo __e('nav.primary'); ?>">
                        <a href="<?php echo htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $navLinkClass('explore'); ?>">
                            <i data-lucide="compass" aria-hidden="true"></i>
                            <span><?php echo __e('nav.explore'); ?></span>
                        </a>
                        <a href="#" class="<?php echo $navLinkClass('messages'); ?>">
                            <i data-lucide="message-circle" aria-hidden="true"></i>
                            <span><?php echo __e('nav.chat'); ?></span>
                        </a>
                        <a
                            href="<?php echo htmlspecialchars($url('/notifications'), ENT_QUOTES, 'UTF-8'); ?>"
                            class="<?php echo $navLinkClass('notifications'); ?>"
                            data-notifications-nav
                        >
                            <span class="app-sidebar-nav-icon-wrap">
                                <i data-lucide="bell" aria-hidden="true"></i>
                                <span
                                    class="app-sidebar-nav-badge"
                                    data-notifications-badge
                                    <?php echo $showNotificationsBadge ? '' : 'hidden'; ?>
                                    aria-hidden="true"
                                ></span>
                            </span>
                            <span><?php echo __e('nav.notifications'); ?></span>
                        </a>
                        <a href="<?php echo htmlspecialchars($url('/profile'), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $navLinkClass('profile'); ?>">
                            <i data-lucide="user-round" aria-hidden="true"></i>
                            <span><?php echo __e('nav.profile'); ?></span>
                        </a>
                    </nav>
