<?php

declare(strict_types=1);

/** @var string $activeNav */
/** @var string|null $contentPageTitle */

$contentPageTitle = isset($contentPageTitle) && is_string($contentPageTitle) && trim($contentPageTitle) !== ''
    ? trim($contentPageTitle)
    : match ($activeNav) {
        'profile' => __('nav.page_profile'),
        'messages' => __('nav.page_chat'),
        'notifications' => __('nav.page_notifications'),
        'analytics' => __('nav.page_analytics'),
        default => __('nav.explore'),
    };
?>
                    <header class="app-shell-header app-content-header">
                        <h1 class="app-content-header-title">/<?php echo htmlspecialchars($contentPageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <div class="app-content-header-actions">
                            <?php
                            $themeToggleClass = 'theme-toggle app-content-header-theme';
                            require __DIR__ . '/theme-toggle.php';
                            ?>
                        </div>
                    </header>
