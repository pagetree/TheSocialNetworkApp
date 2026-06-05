<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var bool $isLoggedIn */
/** @var string $loginCsrfToken */
/** @var array<string, mixed>|null $profileUser */
/** @var string $profileCsrfToken */
/** @var bool $isOwnProfile */
/** @var bool $viewerFollowsProfile */
/** @var bool $profileFollowsViewer */
/** @var string $profileFollowCsrfToken */
/** @var int $profileFollowUserId */
/** @var list<array<string, mixed>> $profilePosts */
/** @var array<int, int> $profileLikedPostIds */
/** @var int $currentUserId */
/** @var bool $showFeedReplyModal */
/** @var bool $profileIsPrivate */
/** @var bool $showPostComposerModal */
/** @var string $postCsrfToken */
/** @var string $composerAvatarUrl */

$profileUser = $profileUser ?? null;
$profileIsPrivate = $profileIsPrivate ?? false;
$profileCsrfToken = $profileCsrfToken ?? '';
$isOwnProfile = $isOwnProfile ?? false;
$viewerFollowsProfile = $viewerFollowsProfile ?? false;
$profileFollowsViewer = $profileFollowsViewer ?? false;
$profileFollowCsrfToken = $profileFollowCsrfToken ?? '';
$profileFollowUserId = $profileFollowUserId ?? 0;
$profilePosts = $profilePosts ?? [];
$profileLikedPostIds = $profileLikedPostIds ?? [];
$currentUserId = $currentUserId ?? 0;
$showFeedReplyModal = $showFeedReplyModal ?? false;
$showPostComposerModal = !empty($showPostComposerModal);
$postCsrfToken = $postCsrfToken ?? '';

$hasProfileUser = is_array($profileUser);
$showProfileActions = $isLoggedIn && $hasProfileUser && !$isOwnProfile && !$profileIsPrivate;
$showProfileDetails = !$profileIsPrivate || $isOwnProfile;
$displayName = $hasProfileUser ? (string) $profileUser['display_name'] : 'User Name';
$handle = $hasProfileUser ? (string) $profileUser['handle'] : '@username';
$bio = $hasProfileUser
    ? trim((string) ($profileUser['bio'] ?? ''))
    : 'UI, UX Designer and Web Developer from Croatia';
$location = $hasProfileUser ? trim((string) ($profileUser['location'] ?? '')) : 'Croatia';
$websiteUrl = $hasProfileUser ? trim((string) ($profileUser['website_url'] ?? '')) : '';
$dateOfBirth = $hasProfileUser && is_string($profileUser['date_of_birth'] ?? null)
    ? trim((string) $profileUser['date_of_birth'])
    : '';
$joinedLabel = $hasProfileUser
    ? formatProfileJoinedDate((string) ($profileUser['created_at'] ?? ''))
    : 'Joined March 2026';
$avatarUrl = userMediaUrl($profileUser, 'avatar_url', $url);
$coverUrl = userMediaUrl($profileUser, 'cover_url', $url);
$websiteLabel = websiteDisplayLabel($websiteUrl);
$dobLabel = formatProfileBirthdayLabel($dateOfBirth);
$showLocation = $location !== '';
$showWebsite = $websiteUrl !== '';
$showDob = $dobLabel !== '';
$profileFollowingCount = 0;
$profileFollowersCount = 0;
if ($hasProfileUser) {
    $followCounts = fetchUserFollowCounts((int) ($profileUser['id'] ?? 0));
    $profileFollowingCount = $followCounts['following'];
    $profileFollowersCount = $followCounts['followers'];
}
$profileFollowingLabel = formatEngagementCount($profileFollowingCount);
$profileFollowersLabel = formatEngagementCount($profileFollowersCount);

$pageSeo = $hasProfileUser
    ? seoBuildProfilePage($profileUser, $url, $profileIsPrivate, $isOwnProfile)
    : seoNoindexPage('/profile');
$pageTitle = seoApplyPageTitle(
    $pageSeo,
    $hasProfileUser
        ? __('meta.profile_title', ['name' => (string) ($profileUser['display_name'] ?? 'Profile')])
        : __('meta.profile_default_title')
);
$activeNav = 'profile';
$mainClass = 'profile-page';
$pageScripts = [];
if ($isOwnProfile) {
    $pageScripts[] = '/assets/js/edit-profile.js';
} elseif ($showProfileActions && $profileFollowUserId > 0) {
    $pageScripts[] = '/assets/js/profile-menu.js';
}
if ($showPostComposerModal) {
    $pageScripts[] = '/assets/js/post-composer.js';
}
if ($showFeedReplyModal) {
    $pageScripts[] = '/assets/js/reply-media-picker.js';
    $pageScripts[] = '/assets/js/feed-reply-modal.js';
}

require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/content-area-start.php';
?>
                    <section class="profile-hero" aria-label="<?php echo __e('profile.overview'); ?>">
                        <div class="profile-cover">
                            <img
                                id="profile-display-cover"
                                class="profile-cover-image"
                                src="<?php echo htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                alt=""
                            >
                        </div>
                        <div class="profile-hero-body">
                            <div class="profile-hero-top">
                                <img
                                    id="profile-display-avatar"
                                    class="profile-hero-avatar"
                                    src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo __e('sidebar.avatar_alt', ['name' => $displayName]); ?>"
                                >
                                <div class="profile-hero-actions">
                                    <?php if ($isOwnProfile) : ?>
                                    <input
                                        type="hidden"
                                        id="profile-display-is-visible"
                                        value="<?php echo $hasProfileUser && userProfileIsVisible($profileUser) ? '1' : '0'; ?>"
                                    >
                                    <button type="button" class="profile-edit-btn" id="profile-edit-open">
                                        <i data-lucide="square-pen" aria-hidden="true"></i>
                                        <span><?php echo __e('profile.edit'); ?></span>
                                    </button>
                                    <?php elseif ($showProfileActions) : ?>
                                    <?php
                                        $profileMenuUserId = $profileFollowUserId;
                                        $profileMenuUserName = $displayName;
                                        require __DIR__ . '/includes/profile/profile-menu.php';
                                    ?>
                                    <button
                                        type="button"
                                        class="profile-chat-btn"
                                        aria-label="<?php echo __e('profile.message_soon'); ?>"
                                        title="<?php echo __e('profile.coming_soon'); ?>"
                                    >
                                        <i data-lucide="message-circle" aria-hidden="true"></i>
                                    </button>
                                    <?php
                                        $followUserId = $profileFollowUserId;
                                        $followUserName = $displayName;
                                        $viewerFollows = $viewerFollowsProfile;
                                        $followsViewer = $profileFollowsViewer;
                                        $followBtnId = 'profile-follow-btn';
                                        $followBtnClass = '';
                                        require __DIR__ . '/includes/profile/follow-button.php';
                                    ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="profile-hero-details">
                                <h1 id="profile-display-name" class="profile-hero-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                                <p class="profile-hero-handle"><?php echo htmlspecialchars($handle, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php if ($showProfileDetails) : ?>
                                <p id="profile-display-bio" class="profile-hero-bio"><?php echo htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="profile-hero-meta">
                                    <?php if ($showLocation) : ?>
                                    <p class="profile-hero-location" id="profile-display-location-wrap">
                                        <i data-lucide="map-pin" aria-hidden="true"></i>
                                        <span id="profile-display-location"><?php echo htmlspecialchars($location, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </p>
                                    <?php endif; ?>
                                    <?php if ($showWebsite) : ?>
                                    <p class="profile-hero-website" id="profile-display-website">
                                        <i data-lucide="link" aria-hidden="true"></i>
                                        <a
                                            id="profile-display-website-link"
                                            href="<?php echo htmlspecialchars($websiteUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        ><?php echo htmlspecialchars($websiteLabel, ENT_QUOTES, 'UTF-8'); ?></a>
                                    </p>
                                    <?php endif; ?>
                                    <?php if ($showDob) : ?>
                                    <p
                                        class="profile-hero-dob"
                                        id="profile-display-dob"
                                        data-iso="<?php echo htmlspecialchars($dateOfBirth, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <i data-lucide="cake" aria-hidden="true"></i>
                                        <span id="profile-display-dob-text"><?php echo htmlspecialchars($dobLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </p>
                                    <?php endif; ?>
                                    <p class="profile-hero-joined">
                                        <i data-lucide="calendar" aria-hidden="true"></i>
                                        <span id="profile-display-joined"><?php echo htmlspecialchars($joinedLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </p>
                                </div>
                                <?php endif; ?>
                                <?php if ($hasProfileUser && $showProfileDetails) : ?>
                                <div class="profile-hero-social-stats" aria-label="<?php echo __e('profile.follow_stats'); ?>">
                                    <p class="profile-hero-social-stat">
                                        <span class="profile-hero-social-stat-value" id="profile-following-count"><?php echo htmlspecialchars($profileFollowingLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="profile-hero-social-stat-label"><?php echo __e('sidebar.following'); ?></span>
                                    </p>
                                    <p class="profile-hero-social-stat">
                                        <span class="profile-hero-social-stat-value" id="profile-followers-count"><?php echo htmlspecialchars($profileFollowersLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="profile-hero-social-stat-label"><?php echo __e('sidebar.followers'); ?></span>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <nav class="profile-tabs" aria-label="<?php echo __e('profile.sections'); ?>">
                        <a href="#" class="profile-tab is-active"><?php echo __e('profile.tabs.posts'); ?></a>
                        <a href="#" class="profile-tab"><?php echo __e('profile.tabs.replies'); ?></a>
                        <a href="#" class="profile-tab"><?php echo __e('profile.tabs.media'); ?></a>
                        <a href="#" class="profile-tab"><?php echo __e('profile.tabs.likes'); ?></a>
                    </nav>

<?php if ($showPostComposerModal) {
    require __DIR__ . '/includes/posts/post-composer-modal.php';
} ?>

                    <div class="profile-feed" id="profile-post-feed">
<?php if ($profileIsPrivate) : ?>
                        <p class="profile-feed-empty"><?php echo __e('profile.empty.private'); ?></p>
<?php elseif ($profilePosts === []) : ?>
                        <p class="profile-feed-empty"><?php echo $isOwnProfile ? __e('profile.empty.own_no_posts') : __e('profile.empty.no_posts'); ?></p>
<?php else :
    foreach ($profilePosts as $profilePost) {
        $contentPostId = postContentPostId($profilePost);
        renderPostCard(
            $profilePost,
            $url,
            $currentUserId,
            isset($profileLikedPostIds[$contentPostId]),
            isset($profileRepostedPostIds[$contentPostId])
        );
    }
endif; ?>
                    </div>
<?php
$showProfileEditModal = $isOwnProfile;
require __DIR__ . '/includes/layout/content-area-end.php';
