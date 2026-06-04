<?php

declare(strict_types=1);

/**
 * @return list<string> App-relative CSS paths in cascade order.
 */
function appStylesheetPaths(): array
{
    return [
        '/assets/css/variables.css',
        '/assets/css/lang-switcher.css',
        '/assets/css/theme-toggle.css',
        '/assets/css/base.css',
        '/assets/css/layout.css',
        '/assets/css/responsive.css',
        '/assets/css/posts-feed.css',
        '/assets/css/post-detail.css',
        '/assets/css/post-replies.css',
        '/assets/css/post-composer.css',
        '/assets/css/post-composer-modal.css',
        '/assets/css/post-menu.css',
        '/assets/css/post-media.css',
        '/assets/css/sidebar.css',
        '/assets/css/profile.css',
        '/assets/css/auth.css',
        '/assets/css/onboarding.css',
        '/assets/css/hashtag.css',
        '/assets/css/post-stats-modal.css',
        '/assets/css/content-report-modal.css',
        '/assets/css/welcome.css',
        '/assets/css/register.css',
    ];
}

function renderAppStylesheets(callable $url): void
{
    foreach (appStylesheetPaths() as $stylesheetPath) {
        $href = htmlspecialchars($url($stylesheetPath), ENT_QUOTES, 'UTF-8');
        echo '    <link rel="stylesheet" href="' . $href . '">' . "\n";
    }
}
