<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var bool $isLoggedIn */
/** @var string $loginCsrfToken */
/** @var array<string, mixed>|null $profileUser */
/** @var string $profileCsrfToken */

$profileUser = $profileUser ?? null;
$profileCsrfToken = $profileCsrfToken ?? '';

$hasProfileUser = is_array($profileUser);
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

$pageTitle = 'Profile — TheSocialNetworkApp';
$activeNav = 'profile';
$mainClass = 'profile-page';
$pageScripts = $isLoggedIn ? ['/assets/js/edit-profile.js'] : [];

require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/content-area-start.php';
?>
                    <section class="profile-hero" aria-label="Profile overview">
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
                                    alt="<?php echo htmlspecialchars($displayName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <div class="profile-hero-actions">
                                    <?php if ($isLoggedIn) : ?>
                                    <button type="button" class="profile-edit-btn" id="profile-edit-open">
                                        <i data-lucide="square-pen" aria-hidden="true"></i>
                                        <span>Edit profile</span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="profile-hero-details">
                                <h1 id="profile-display-name" class="profile-hero-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                                <p class="profile-hero-handle"><?php echo htmlspecialchars($handle, ENT_QUOTES, 'UTF-8'); ?></p>
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
                            </div>
                        </div>
                    </section>

                    <nav class="profile-tabs" aria-label="Profile sections">
                        <a href="#" class="profile-tab is-active">Posts</a>
                        <a href="#" class="profile-tab">Replies</a>
                        <a href="#" class="profile-tab">Media</a>
                        <a href="#" class="profile-tab">Likes</a>
                    </nav>

                    <div class="profile-feed">
                        <article class="post-card">
                            <header class="post-header">
                                <img
                                    class="post-avatar"
                                    src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($displayName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <div class="post-meta">
                                    <p class="post-meta-line">
                                        <span class="post-author"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="post-handle"><?php echo htmlspecialchars($handle, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <time class="post-time" datetime="2026-03-15">Mar 15</time>
                                    </p>
                                </div>
                                <button type="button" class="post-menu-btn" aria-label="Post options">
                                    <i data-lucide="ellipsis" aria-hidden="true"></i>
                                </button>
                            </header>
                            <p class="post-text">Just shipped a new profile layout. Clean, dark, and ready for real data next.</p>
                            <footer class="post-actions" aria-label="Post engagement">
                                <button type="button" class="post-action"><i data-lucide="message-circle" aria-hidden="true"></i><span>12</span></button>
                                <button type="button" class="post-action"><i data-lucide="repeat-2" aria-hidden="true"></i><span>4</span></button>
                                <button type="button" class="post-action"><i data-lucide="heart" aria-hidden="true"></i><span>28</span></button>
                                <button type="button" class="post-action"><i data-lucide="bar-chart-2" aria-hidden="true"></i><span>1.2K</span></button>
                            </footer>
                        </article>

                        <article class="post-card">
                            <header class="post-header">
                                <img
                                    class="post-avatar"
                                    src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($displayName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <div class="post-meta">
                                    <p class="post-meta-line">
                                        <span class="post-author"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="post-handle"><?php echo htmlspecialchars($handle, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <time class="post-time" datetime="2026-03-10">Mar 10</time>
                                    </p>
                                </div>
                                <button type="button" class="post-menu-btn" aria-label="Post options">
                                    <i data-lucide="ellipsis" aria-hidden="true"></i>
                                </button>
                            </header>
                            <p class="post-text">Working on some UI flows for the social feed. Loving this color palette.</p>
                            <img
                                class="post-media"
                                src="https://placehold.co/900x500/1a2a38/d9d9d9?text=Design+WIP"
                                alt="Design work in progress"
                            >
                            <footer class="post-actions" aria-label="Post engagement">
                                <button type="button" class="post-action"><i data-lucide="message-circle" aria-hidden="true"></i><span>8</span></button>
                                <button type="button" class="post-action"><i data-lucide="repeat-2" aria-hidden="true"></i><span>2</span></button>
                                <button type="button" class="post-action"><i data-lucide="heart" aria-hidden="true"></i><span>41</span></button>
                                <button type="button" class="post-action"><i data-lucide="bar-chart-2" aria-hidden="true"></i><span>890</span></button>
                            </footer>
                        </article>

                        <article class="post-card">
                            <header class="post-header">
                                <img
                                    class="post-avatar"
                                    src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($displayName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <div class="post-meta">
                                    <p class="post-meta-line">
                                        <span class="post-author"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="post-handle"><?php echo htmlspecialchars($handle, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <time class="post-time" datetime="2026-02-28">Feb 28</time>
                                    </p>
                                </div>
                                <button type="button" class="post-menu-btn" aria-label="Post options">
                                    <i data-lucide="ellipsis" aria-hidden="true"></i>
                                </button>
                            </header>
                            <p class="post-text">Coffee, code, repeat. Building something fun from Croatia.</p>
                            <footer class="post-actions" aria-label="Post engagement">
                                <button type="button" class="post-action"><i data-lucide="message-circle" aria-hidden="true"></i><span>3</span></button>
                                <button type="button" class="post-action"><i data-lucide="repeat-2" aria-hidden="true"></i><span>1</span></button>
                                <button type="button" class="post-action"><i data-lucide="heart" aria-hidden="true"></i><span>15</span></button>
                                <button type="button" class="post-action"><i data-lucide="bar-chart-2" aria-hidden="true"></i><span>320</span></button>
                            </footer>
                        </article>
                    </div>
<?php
$showProfileEditModal = $isLoggedIn;
require __DIR__ . '/includes/layout/content-area-end.php';
