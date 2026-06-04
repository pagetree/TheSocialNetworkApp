<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var callable(string): string $navLinkClass */
/** @var bool $isLoggedIn */

$mobileNavLogoutCsrfToken = $isLoggedIn ? createCsrfToken('logout') : '';
?>
            <div id="app-mobile-nav" class="app-mobile-nav" hidden>
                <button
                    type="button"
                    class="app-mobile-nav-backdrop"
                    id="app-mobile-nav-backdrop"
                    aria-label="<?php echo __e('nav.close_menu'); ?>"
                    tabindex="-1"
                ></button>
                <aside
                    class="app-mobile-nav-panel"
                    id="app-mobile-nav-panel"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="app-mobile-nav-title"
                >
                    <header class="app-mobile-nav-header">
                        <h2 id="app-mobile-nav-title" class="app-mobile-nav-title"><?php echo __e('nav.menu'); ?></h2>
                        <button
                            type="button"
                            class="app-mobile-nav-close"
                            id="app-mobile-nav-close"
                            aria-label="<?php echo __e('nav.close_menu'); ?>"
                        >
                            <i data-lucide="x" aria-hidden="true"></i>
                        </button>
                    </header>
<?php
$primaryNavClass = 'app-mobile-nav-links';
require __DIR__ . '/primary-nav.php';
?>
                    <footer class="app-mobile-nav-footer">
                        <?php require __DIR__ . '/lang-switcher.php'; ?>
                        <?php require __DIR__ . '/theme-toggle.php'; ?>
                        <?php if ($isLoggedIn) : ?>
                        <form
                            method="post"
                            action="<?php echo htmlspecialchars($url('/logout'), ENT_QUOTES, 'UTF-8'); ?>"
                            class="topbar-logout-form app-mobile-nav-logout"
                        >
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($mobileNavLogoutCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="topbar-link app-mobile-nav-logout-btn">
                                <i data-lucide="log-out" aria-hidden="true"></i>
                                <span><?php echo __e('nav.logout'); ?></span>
                            </button>
                        </form>
                        <?php endif; ?>
                    </footer>
                </aside>
            </div>
