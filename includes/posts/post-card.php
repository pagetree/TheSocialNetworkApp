<?php

declare(strict_types=1);

/** @var array<string, mixed> $post */
/** @var callable(string): string $url */

$authorName = (string) ($post['author']['display_name'] ?? 'User');
$authorHandle = (string) ($post['author']['handle'] ?? '@user');
$authorAvatar = (string) ($post['author']['avatar_url'] ?? '');
$postBody = (string) ($post['body'] ?? '');
$postMediaItems = is_array($post['media'] ?? null) ? $post['media'] : [];
$postTimeLabel = (string) ($post['time_label'] ?? '');
$createdAt = (string) ($post['created_at'] ?? '');
$replyCount = formatEngagementCount((int) ($post['reply_count'] ?? 0));
$repostCount = formatEngagementCount((int) ($post['repost_count'] ?? 0));
$likeCount = formatEngagementCount((int) ($post['like_count'] ?? 0));
$viewCount = formatEngagementCount((int) ($post['view_count'] ?? 0));
$mediaCount = count($postMediaItems);
$galleryClass = 'post-media-gallery';
if ($mediaCount === 1) {
    $galleryClass .= ' post-media-gallery--1';
} elseif ($mediaCount === 2) {
    $galleryClass .= ' post-media-gallery--2';
} elseif ($mediaCount === 3) {
    $galleryClass .= ' post-media-gallery--3';
} elseif ($mediaCount >= 4) {
    $galleryClass .= ' post-media-gallery--4';
}
?>
                    <article class="post-card" data-post-id="<?php echo (int) ($post['id'] ?? 0); ?>">
                        <header class="post-header">
                            <img
                                class="post-avatar"
                                src="<?php echo htmlspecialchars($authorAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($authorName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                            >
                            <div class="post-meta">
                                <p class="post-meta-line">
                                    <span class="post-author"><?php echo htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="post-handle"><?php echo htmlspecialchars($authorHandle, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($postTimeLabel !== '') : ?>
                                    <time class="post-time" datetime="<?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($postTimeLabel, ENT_QUOTES, 'UTF-8'); ?></time>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <button type="button" class="post-menu-btn" aria-label="Post options">
                                <i data-lucide="ellipsis" aria-hidden="true"></i>
                            </button>
                        </header>
                        <?php if ($postBody !== '') : ?>
                        <p class="post-text"><?php echo htmlspecialchars($postBody, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <?php if ($mediaCount > 0) : ?>
                        <div class="<?php echo htmlspecialchars($galleryClass, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php foreach ($postMediaItems as $mediaItem) :
                                $mediaUrl = (string) ($mediaItem['url'] ?? '');
                                $mediaType = (string) ($mediaItem['type'] ?? '');
                                if ($mediaUrl === '') {
                                    continue;
                                }
                                ?>
                                <?php if ($mediaType === 'video') : ?>
                            <video
                                class="post-media post-media--video"
                                controls
                                preload="metadata"
                                playsinline
                                src="<?php echo htmlspecialchars($mediaUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            ></video>
                                <?php else : ?>
                            <img
                                class="post-media post-media--zoomable"
                                src="<?php echo htmlspecialchars($mediaUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                alt=""
                                role="button"
                                tabindex="0"
                            >
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <footer class="post-actions" aria-label="Post engagement">
                            <button type="button" class="post-action"><i data-lucide="message-circle" aria-hidden="true"></i><span><?php echo htmlspecialchars($replyCount, ENT_QUOTES, 'UTF-8'); ?></span></button>
                            <button type="button" class="post-action"><i data-lucide="repeat-2" aria-hidden="true"></i><span><?php echo htmlspecialchars($repostCount, ENT_QUOTES, 'UTF-8'); ?></span></button>
                            <button type="button" class="post-action"><i data-lucide="heart" aria-hidden="true"></i><span><?php echo htmlspecialchars($likeCount, ENT_QUOTES, 'UTF-8'); ?></span></button>
                            <button type="button" class="post-action"><i data-lucide="bar-chart-2" aria-hidden="true"></i><span><?php echo htmlspecialchars($viewCount, ENT_QUOTES, 'UTF-8'); ?></span></button>
                        </footer>
                    </article>
