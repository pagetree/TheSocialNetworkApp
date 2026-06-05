<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var callable(string): string $sidebarNavLinkClass */
?>
                    <nav class="app-sidebar-nav" aria-label="<?php echo __e('nav.primary'); ?>">
                        <a href="<?php echo htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $sidebarNavLinkClass('explore'); ?>">
                            <i data-lucide="compass" aria-hidden="true"></i>
                            <span><?php echo __e('nav.explore'); ?></span>
                        </a>
                        <a href="#" class="<?php echo $sidebarNavLinkClass('messages'); ?>">
                            <i data-lucide="message-circle" aria-hidden="true"></i>
                            <span><?php echo __e('nav.chat'); ?></span>
                        </a>
                        <a href="#" class="<?php echo $sidebarNavLinkClass('notifications'); ?>">
                            <i data-lucide="bell" aria-hidden="true"></i>
                            <span><?php echo __e('nav.notifications'); ?></span>
                        </a>
                        <a href="#" class="<?php echo $sidebarNavLinkClass('analytics'); ?>">
                            <i data-lucide="bar-chart-2" aria-hidden="true"></i>
                            <span><?php echo __e('nav.analytics'); ?></span>
                        </a>
                        <a href="<?php echo htmlspecialchars($url('/profile'), ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $sidebarNavLinkClass('profile'); ?>">
                            <i data-lucide="user-round" aria-hidden="true"></i>
                            <span><?php echo __e('nav.profile'); ?></span>
                        </a>
                    </nav>
