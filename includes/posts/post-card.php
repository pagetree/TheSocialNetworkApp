<?php

declare(strict_types=1);

/** @var array<string, mixed> $post */
/** @var callable(string): string $url */
/** @var int $currentUserId */

$authorName = (string) ($post['author']['display_name'] ?? 'User');
$authorHandle = (string) ($post['author']['handle'] ?? '@user');
$authorProfileUrl = (string) ($post['author']['profile_url'] ?? '');
$authorAvatar = (string) ($post['author']['avatar_url'] ?? '');
$postBody = (string) ($post['body'] ?? '');
$postMediaItems = is_array($post['media'] ?? null) ? $post['media'] : [];
$postTimeLabel = (string) ($post['time_label'] ?? '');
$createdAt = (string) ($post['created_at'] ?? '');
$replyCount = formatEngagementCount((int) ($post['reply_count'] ?? 0));
$repostCount = formatEngagementCount((int) ($post['repost_count'] ?? 0));
$likeCount = formatEngagementCount((int) ($post['like_count'] ?? 0));
$viewCount = formatEngagementCount((int) ($post['view_count'] ?? 0));
$interactionCount = formatEngagementCount((int) ($post['interaction_count'] ?? 0));
$postUserId = (int) ($post['user_id'] ?? 0);
$trackStats = $currentUserId > 0 && $postUserId !== $currentUserId;
$postUrl = (string) ($post['post_url'] ?? postUrl((int) ($post['id'] ?? 0), $url));
$hasMedia = count($postMediaItems) > 0;
$postLinkLabel = 'View post by ' . $authorName;
$viewerLiked = (bool) ($post['viewer_liked'] ?? false);
$likeActionClass = $viewerLiked ? ' post-action-like is-liked' : ' post-action-like';
?>
                    <article
                        class="post-card post-card--linkable"
                        data-post-id="<?php echo (int) ($post['id'] ?? 0); ?>"
                        data-post-user-id="<?php echo $postUserId; ?>"
                        data-stat-trackable="<?php echo $trackStats ? '1' : '0'; ?>"
                        data-post-url="<?php echo htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <a
                            class="post-card-cover-link"
                            href="<?php echo htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            aria-label="<?php echo htmlspecialchars($postLinkLabel, ENT_QUOTES, 'UTF-8'); ?>"
                        ></a>
                        <header class="post-header">
                            <img
                                class="post-avatar"
                                src="<?php echo htmlspecialchars($authorAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($authorName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                            >
                            <div class="post-meta">
                                <p class="post-meta-line">
                                    <?php if ($authorProfileUrl !== '') : ?>
                                    <a class="post-author-link" href="<?php echo htmlspecialchars($authorProfileUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php else : ?>
                                    <span class="post-author"><?php echo htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
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
                        <?php if ($hasMedia) {
                            require __DIR__ . '/post-media-gallery.php';
                        } ?>
                        <footer class="post-actions" aria-label="Post engagement">
                            <button type="button" class="post-action post-action-reply" aria-label="Reply to post"><i data-lucide="message-circle" aria-hidden="true"></i><span><?php echo htmlspecialchars($replyCount, ENT_QUOTES, 'UTF-8'); ?></span></button>
                            <button type="button" class="post-action"><i data-lucide="repeat-2" aria-hidden="true"></i><span><?php echo htmlspecialchars($repostCount, ENT_QUOTES, 'UTF-8'); ?></span></button>
                            <button
                                type="button"
                                class="post-action<?php echo $likeActionClass; ?>"
                                aria-label="<?php echo $viewerLiked ? 'Unlike post' : 'Like post'; ?>"
                                aria-pressed="<?php echo $viewerLiked ? 'true' : 'false'; ?>"
                                data-liked="<?php echo $viewerLiked ? '1' : '0'; ?>"
                            ><i data-lucide="heart" aria-hidden="true"></i><span><?php echo htmlspecialchars($likeCount, ENT_QUOTES, 'UTF-8'); ?></span></button>
                            <button type="button" class="post-action post-action-stat-views" aria-label="Post views">
                                <i data-lucide="bar-chart-2" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars($viewCount, ENT_QUOTES, 'UTF-8'); ?></span>
                            </button>
                            <span class="post-stat-interactions" hidden aria-hidden="true"><?php echo htmlspecialchars($interactionCount, ENT_QUOTES, 'UTF-8'); ?></span>
                        </footer>
                    </article>
