<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var bool $isLoggedIn */
/** @var string $loginCsrfToken */
/** @var string $hashtagTag */
/** @var int $hashtagPostCount */
/** @var list<array<string, mixed>> $hashtagPosts */
/** @var array<int, int> $hashtagLikedPostIds */
/** @var int $currentUserId */
/** @var bool $showFeedReplyModal */

$hashtagTag = $hashtagTag ?? '';
$hashtagPostCount = $hashtagPostCount ?? 0;
$hashtagPosts = $hashtagPosts ?? [];
$hashtagLikedPostIds = $hashtagLikedPostIds ?? [];
$currentUserId = $currentUserId ?? 0;
$showFeedReplyModal = $showFeedReplyModal ?? false;

$hashtagLabel = '#' . $hashtagTag;
$postCountLabel = $hashtagPostCount === 1
    ? '1 post'
    : formatEngagementCount($hashtagPostCount) . ' posts';
?>
                    <header class="hashtag-page-header">
                        <a href="<?php echo htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="hashtag-page-back">
                            <i data-lucide="arrow-left" aria-hidden="true"></i>
                            <span>Back</span>
                        </a>
                        <h1 class="hashtag-page-title"><?php echo htmlspecialchars($hashtagLabel, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="hashtag-page-meta"><?php echo htmlspecialchars($postCountLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                    </header>

                    <div class="post-feed" id="hashtag-post-feed">
<?php if ($hashtagPosts === []) : ?>
                        <p class="hashtag-page-empty">No posts with this hashtag yet.</p>
<?php else :
    foreach ($hashtagPosts as $hashtagPost) {
        $contentPostId = postContentPostId($hashtagPost);
        renderPostCard(
            $hashtagPost,
            $url,
            $currentUserId,
            isset($hashtagLikedPostIds[$contentPostId]),
            isset($hashtagRepostedPostIds[$contentPostId])
        );
    }
endif; ?>
                    </div>
