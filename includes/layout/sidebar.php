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

const SIDEBAR_WHO_TO_FOLLOW_LIMIT = 4;

/** @var list<array{display_name: string, handle: string}> $whoToFollowPlaceholders */
$whoToFollowPlaceholders = [
    ['display_name' => 'Maya Chen', 'handle' => '@mayachen'],
    ['display_name' => 'Jordan Blake', 'handle' => '@jblake'],
    ['display_name' => 'Sofia Ruiz', 'handle' => '@sofiaruiz'],
    ['display_name' => 'Alex Kim', 'handle' => '@alexkim'],
];
$whoToFollowPlaceholders = array_slice($whoToFollowPlaceholders, 0, SIDEBAR_WHO_TO_FOLLOW_LIMIT);
$whoToFollowAvatarUrl = userMediaUrl(null, 'avatar_url', $url);
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

                    <article class="who-to-follow-card">
                        <h2 class="who-to-follow-card-title">Who to follow</h2>
                        <ul class="who-to-follow-list">
                            <?php foreach ($whoToFollowPlaceholders as $suggestion) : ?>
                            <li class="who-to-follow-item">
                                <div class="who-to-follow-row">
                                    <div class="who-to-follow-identity">
                                        <img
                                            class="who-to-follow-avatar"
                                            src="<?php echo htmlspecialchars($whoToFollowAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo htmlspecialchars((string) $suggestion['display_name'] . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                                            width="60"
                                            height="60"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                        <div class="who-to-follow-meta">
                                            <span class="who-to-follow-name"><?php echo htmlspecialchars((string) $suggestion['display_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="who-to-follow-handle"><?php echo htmlspecialchars((string) $suggestion['handle'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="profile-follow-btn post-participants-follow-btn"
                                        data-placeholder-follow
                                        aria-pressed="false"
                                    >
                                        <span class="profile-follow-btn-label">Follow</span>
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                </aside>
