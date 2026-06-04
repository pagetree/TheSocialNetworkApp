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

$sidebarViewerId = is_array($sidebarUser) ? (int) ($sidebarUser['id'] ?? 0) : 0;
$whoToFollowSuggestions = $sidebarViewerId > 0
    ? fetchWhoToFollowSuggestions($sidebarViewerId, SIDEBAR_WHO_TO_FOLLOW_LIMIT)
    : [];
$whoToFollowFollowedIds = [];
if ($sidebarViewerId > 0 && $whoToFollowSuggestions !== []) {
    $whoToFollowFollowedIds = fetchFollowedUserIdsAmong(
        $sidebarViewerId,
        array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $whoToFollowSuggestions)
    );
}
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

                    <?php if ($sidebarViewerId > 0) : ?>
                    <article class="who-to-follow-card">
                        <h2 class="who-to-follow-card-title">Who to follow</h2>
                        <?php if ($whoToFollowSuggestions === []) : ?>
                        <p class="who-to-follow-empty">No suggestions right now.</p>
                        <?php else : ?>
                        <ul class="who-to-follow-list">
                            <?php foreach ($whoToFollowSuggestions as $suggestion) :
                                $suggestionId = (int) ($suggestion['id'] ?? 0);
                                $viewerFollows = isset($whoToFollowFollowedIds[$suggestionId]);
                                $suggestionName = (string) ($suggestion['display_name'] ?? 'User');
                                $suggestionHandle = (string) ($suggestion['handle'] ?? '@user');
                                $suggestionAvatar = userMediaUrl($suggestion, 'avatar_url', $url);
                                $suggestionProfileUrl = profileUrlForUser($suggestion, $url);
                                ?>
                            <li class="who-to-follow-item">
                                <div class="who-to-follow-row">
                                    <a class="who-to-follow-identity" href="<?php echo htmlspecialchars($suggestionProfileUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                        <img
                                            class="who-to-follow-avatar"
                                            src="<?php echo htmlspecialchars($suggestionAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo htmlspecialchars($suggestionName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                                            width="60"
                                            height="60"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                        <div class="who-to-follow-meta">
                                            <span class="who-to-follow-name"><?php echo htmlspecialchars($suggestionName, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="who-to-follow-handle"><?php echo htmlspecialchars($suggestionHandle, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </a>
                                    <button
                                        type="button"
                                        class="profile-follow-btn post-participants-follow-btn<?php echo $viewerFollows ? ' is-following' : ''; ?>"
                                        data-user-id="<?php echo $suggestionId; ?>"
                                        data-following="<?php echo $viewerFollows ? '1' : '0'; ?>"
                                        aria-pressed="<?php echo $viewerFollows ? 'true' : 'false'; ?>"
                                        aria-label="<?php echo $viewerFollows ? 'Unfollow ' : 'Follow '; ?><?php echo htmlspecialchars($suggestionName, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <span class="profile-follow-btn-label profile-follow-btn-label--follow">Follow</span>
                                        <span class="profile-follow-btn-label profile-follow-btn-label--following">Following</span>
                                        <span class="profile-follow-btn-label profile-follow-btn-label--unfollow">Unfollow</span>
                                    </button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </article>
                    <?php endif; ?>
                </aside>
