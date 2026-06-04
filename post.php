<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var bool $isLoggedIn */
/** @var string $loginCsrfToken */
/** @var array<string, mixed> $post */
/** @var int $currentUserId */
/** @var string $postStatsCsrfToken */
/** @var array<string, mixed>|null $currentUser */

$pageTitle = __('meta.post_title');
$activeNav = 'explore';
$mainClass = 'app-content post-detail-page';
$pageScripts = ['/assets/js/reply-media-picker.js', '/assets/js/post-reply-composer.js'];
$postParticipants = fetchVisiblePostParticipants((int) ($post['id'] ?? 0), POST_PARTICIPANTS_LIMIT);
$postParticipantFollowedIds = [];
$profileFollowCsrfToken = '';
if ($isLoggedIn) {
    $profileFollowCsrfToken = createCsrfToken('profile_follow');
    if ($postParticipants !== []) {
        $postParticipantFollowedIds = fetchFollowedUserIdsAmong(
            $currentUserId,
            array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $postParticipants)
        );
    }
}
$layoutHasRightSidebar = true;

require __DIR__ . '/includes/layout/head.php';
require __DIR__ . '/includes/layout/content-area-start.php';

$post = postFeedPayload($post, $url);
$post['viewer_liked'] = $isLoggedIn && isPostLikedByUser((int) $post['id'], $currentUserId);
$replies = fetchPostReplies((int) $post['id']);
$replyCsrfToken = $isLoggedIn ? createCsrfToken('post_reply') : '';
$postLikeCsrfToken = $isLoggedIn ? createCsrfToken('post_like') : '';
$composerAvatarUrl = $isLoggedIn && is_array($currentUser ?? null)
    ? userMediaUrl($currentUser, 'avatar_url', $url)
    : 'https://pub-a912eacf8fe9461083def05076743bb3.r2.dev/assets/romeo-leaupepe-su-a-70gb9CHBX4g-unsplash.jpg';

require __DIR__ . '/includes/posts/post-detail.php';

require __DIR__ . '/includes/layout/content-area-end.php';
