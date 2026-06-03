<?php

declare(strict_types=1);

/** @var callable(string): string $url */

$sidebarUser = getCurrentUser();
$sidebarName = is_array($sidebarUser) ? (string) $sidebarUser['display_name'] : 'User Name';
$sidebarHandle = is_array($sidebarUser) ? (string) $sidebarUser['handle'] : '@username';
$sidebarAvatar = userMediaUrl($sidebarUser, 'avatar_url', $url);
$sidebarFollowersCount = 0;
$sidebarFollowingCount = 0;
if (is_array($sidebarUser)) {
    $sidebarFollowCounts = fetchUserFollowCounts((int) ($sidebarUser['id'] ?? 0));
    $sidebarFollowersCount = $sidebarFollowCounts['followers'];
    $sidebarFollowingCount = $sidebarFollowCounts['following'];
}
$sidebarFollowersLabel = formatEngagementCount($sidebarFollowersCount);
$sidebarFollowingLabel = formatEngagementCount($sidebarFollowingCount);
?>
                <aside class="app-sidebar">
                    <article class="profile-card">
                        <header class="profile-card-header">
                            <img
                                id="profile-sidebar-avatar"
                                class="profile-card-avatar"
                                src="<?php echo htmlspecialchars($sidebarAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($sidebarName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                            >
                            <div class="profile-card-identity">
                                <h2 id="profile-sidebar-name" class="profile-card-name"><?php echo htmlspecialchars($sidebarName, ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p class="profile-card-handle"><?php echo htmlspecialchars($sidebarHandle, ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </header>
                        <div class="profile-card-stats" aria-label="Profile stats">
                            <span class="profile-card-stat">
                                <span id="profile-sidebar-followers-count" class="profile-card-stat-value"><?php echo htmlspecialchars($sidebarFollowersLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="profile-card-stat-label">Followers</span>
                            </span>
                            <span class="profile-card-stat">
                                <span id="profile-sidebar-following-count" class="profile-card-stat-value"><?php echo htmlspecialchars($sidebarFollowingLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="profile-card-stat-label">Following</span>
                            </span>
                        </div>
                    </article>
                </aside>
