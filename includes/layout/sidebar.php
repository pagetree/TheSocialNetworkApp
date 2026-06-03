<?php

declare(strict_types=1);

/** @var callable(string): string $url */

$sidebarUser = getCurrentUser();
$sidebarName = is_array($sidebarUser) ? (string) $sidebarUser['display_name'] : 'User Name';
$sidebarHandle = is_array($sidebarUser) ? (string) $sidebarUser['handle'] : '@username';
$sidebarLocation = is_array($sidebarUser) ? trim((string) ($sidebarUser['location'] ?? '')) : 'Croatia';
$sidebarBio = is_array($sidebarUser)
    ? trim((string) ($sidebarUser['bio'] ?? ''))
    : 'UI, UX Designer and Web Developer from Croatia';
$sidebarAvatar = userMediaUrl($sidebarUser, 'avatar_url', $url);
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
                        <p id="profile-sidebar-location" class="profile-card-location"><?php echo htmlspecialchars($sidebarLocation, ENT_QUOTES, 'UTF-8'); ?></p>
                        <p id="profile-sidebar-bio" class="profile-card-bio"><?php echo htmlspecialchars($sidebarBio, ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="profile-card-stats" aria-label="Profile stats">
                            <span class="profile-card-stat">
                                <span class="profile-card-stat-value">499</span>
                                <span class="profile-card-stat-label">Followers</span>
                            </span>
                            <span class="profile-card-stat">
                                <span class="profile-card-stat-value">46</span>
                                <span class="profile-card-stat-label">Following</span>
                            </span>
                        </div>
                    </article>
                </aside>
