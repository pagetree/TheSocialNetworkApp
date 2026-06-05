<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var bool $isLoggedIn */
/** @var list<array<string, mixed>> $postParticipants */
/** @var int $currentUserId */
/** @var array<int, true> $postParticipantFollowedIds */

$postParticipants = $postParticipants ?? [];
$postParticipantFollowedIds = $postParticipantFollowedIds ?? [];
$currentUserId = $currentUserId ?? 0;

$sidebarUser = getCurrentUser();
$sidebarViewerId = is_array($sidebarUser) ? (int) ($sidebarUser['id'] ?? 0) : 0;
$whoToFollowSuggestions = fetchWhoToFollowSuggestions($sidebarViewerId, SIDEBAR_WHO_TO_FOLLOW_LIMIT);
$whoToFollowFollowedIds = [];
if ($sidebarViewerId > 0 && $whoToFollowSuggestions !== []) {
    $whoToFollowFollowedIds = fetchFollowedUserIdsAmong(
        $sidebarViewerId,
        array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $whoToFollowSuggestions)
    );
}
$trendingHashtags = fetchTopHashtagsByPostCount();
?>
                <aside class="app-sidebar app-sidebar--right" aria-label="<?php echo __e('profile.activity_sidebar'); ?>">
                    <header class="app-shell-header app-sidebar-header--right">
                        <label class="app-sidebar-search">
                            <i data-lucide="search" class="app-sidebar-search-icon" aria-hidden="true"></i>
                            <input
                                type="search"
                                class="app-sidebar-search-input"
                                placeholder="<?php echo __e('sidebar.search_placeholder'); ?>"
                                aria-label="<?php echo __e('sidebar.search_label'); ?>"
                                autocomplete="off"
                            >
                        </label>
                    </header>
                    <div class="app-sidebar-body">
                        <?php require __DIR__ . '/who-to-follow-panel.php'; ?>
                        <?php require __DIR__ . '/trending-hashtags-panel.php'; ?>
                        <?php if ($postParticipants !== []) {
                            require __DIR__ . '/post-participants-panel.php';
                        } ?>
                    </div>
                    <?php require __DIR__ . '/sidebar-right-footer.php'; ?>
                </aside>
