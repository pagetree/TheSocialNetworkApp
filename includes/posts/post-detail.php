<?php

declare(strict_types=1);

/** @var array<string, mixed> $post */
/** @var callable(string): string $url */
/** @var int $currentUserId */
/** @var bool $isLoggedIn */
/** @var list<array<string, mixed>> $replies */
/** @var string $composerAvatarUrl */

$authorName = (string) ($post['author']['display_name'] ?? 'User');
$authorHandle = (string) ($post['author']['handle'] ?? '@user');
$authorAvatar = (string) ($post['author']['avatar_url'] ?? '');
$postBody = (string) ($post['body'] ?? '');
$detailDateLabel = (string) ($post['detail_date_label'] ?? '');
$createdAt = (string) ($post['created_at'] ?? '');
$replyCount = formatEngagementCount((int) ($post['reply_count'] ?? 0));
$repostCount = formatEngagementCount((int) ($post['repost_count'] ?? 0));
$likeCount = formatEngagementCount((int) ($post['like_count'] ?? 0));
$viewCount = formatEngagementCount((int) ($post['view_count'] ?? 0));
$interactionCount = formatEngagementCount((int) ($post['interaction_count'] ?? 0));
$postUserId = (int) ($post['user_id'] ?? 0);
$trackStats = $currentUserId > 0 && $postUserId !== $currentUserId;
$postMediaItems = is_array($post['media'] ?? null) ? $post['media'] : [];
$hasMedia = count($postMediaItems) > 0;
$replies = $replies ?? [];
$viewerLiked = (bool) ($post['viewer_liked'] ?? false);
$likeActionClass = $viewerLiked ? ' post-action-like is-liked' : ' post-action-like';
?>
                    <article
                        class="post-detail"
                        data-post-id="<?php echo (int) ($post['id'] ?? 0); ?>"
                        data-post-user-id="<?php echo $postUserId; ?>"
                        data-stat-trackable="<?php echo $trackStats ? '1' : '0'; ?>"
                    >
                        <div class="post-detail-toolbar">
                            <a href="<?php echo htmlspecialchars($url('/'), ENT_QUOTES, 'UTF-8'); ?>" class="post-detail-back-link">
                                <span class="post-detail-back" aria-hidden="true">
                                    <i data-lucide="arrow-left"></i>
                                </span>
                                <span class="post-detail-back-label">Back to feed</span>
                            </a>
                        </div>

                        <header class="post-detail-header">
                            <img
                                class="post-detail-avatar"
                                src="<?php echo htmlspecialchars($authorAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($authorName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                            >
                            <div class="post-detail-author">
                                <p class="post-detail-name"><?php echo htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="post-detail-handle"><?php echo htmlspecialchars($authorHandle, ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <?php
                            $menuKind = 'post';
                            $menuTargetId = (int) ($post['id'] ?? 0);
                            $menuOwnerUserId = $postUserId;
                            $menuConversationId = 0;
                            require __DIR__ . '/post-menu.php';
                            ?>
                        </header>

                        <?php if ($postBody !== '') : ?>
                        <p class="post-detail-text"><?php echo formatPostBodyHtml($postBody, $url); ?></p>
                        <?php endif; ?>

                        <?php if ($hasMedia) {
                            require __DIR__ . '/post-media-gallery.php';
                        } ?>

                        <footer class="post-detail-meta" aria-label="Post info">
                            <?php if ($detailDateLabel !== '') : ?>
                            <p class="post-detail-meta-item">
                                <i data-lucide="calendar" aria-hidden="true"></i>
                                <time datetime="<?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($detailDateLabel, ENT_QUOTES, 'UTF-8'); ?></time>
                            </p>
                            <?php endif; ?>
                            <button
                                type="button"
                                class="post-detail-meta-item post-detail-meta-views-btn post-action-stat-views"
                                aria-label="View post stats"
                                data-post-id="<?php echo (int) ($post['id'] ?? 0); ?>"
                            >
                                <span><span class="post-detail-view-count"><?php echo htmlspecialchars($viewCount, ENT_QUOTES, 'UTF-8'); ?></span> Views</span>
                            </button>
                            <span class="post-stat-interactions" hidden aria-hidden="true"><?php echo htmlspecialchars($interactionCount, ENT_QUOTES, 'UTF-8'); ?></span>
                        </footer>

                        <footer class="post-actions post-detail-actions" aria-label="Post engagement">
                            <button type="button" class="post-action post-detail-reply-count"><i data-lucide="message-circle" aria-hidden="true"></i><span><?php echo htmlspecialchars($replyCount, ENT_QUOTES, 'UTF-8'); ?></span></button>
                            <button type="button" class="post-action"><i data-lucide="repeat-2" aria-hidden="true"></i><span><?php echo htmlspecialchars($repostCount, ENT_QUOTES, 'UTF-8'); ?></span></button>
                            <button
                                type="button"
                                class="post-action<?php echo $likeActionClass; ?>"
                                aria-label="<?php echo $viewerLiked ? 'Unlike post' : 'Like post'; ?>"
                                aria-pressed="<?php echo $viewerLiked ? 'true' : 'false'; ?>"
                                data-liked="<?php echo $viewerLiked ? '1' : '0'; ?>"
                            ><i data-lucide="heart" aria-hidden="true"></i><span><?php echo htmlspecialchars($likeCount, ENT_QUOTES, 'UTF-8'); ?></span></button>
                            <button
                                type="button"
                                class="post-action post-action-stat-views"
                                aria-label="View post stats"
                                data-post-id="<?php echo (int) ($post['id'] ?? 0); ?>"
                            >
                                <i data-lucide="bar-chart-2" aria-hidden="true"></i>
                                <span class="post-detail-view-count"><?php echo htmlspecialchars($viewCount, ENT_QUOTES, 'UTF-8'); ?></span>
                            </button>
                        </footer>
                    </article>

                    <?php if ($isLoggedIn) {
                        require __DIR__ . '/post-reply-composer.php';
                    } ?>

                    <section class="post-replies" id="post-replies" aria-label="Replies">
<?php renderPostReplyTree($replies, $url, $currentUserId, (int) ($post['id'] ?? 0)); ?>
                    </section>

                    <?php if ($isLoggedIn) {
                        require __DIR__ . '/reply-to-reply-modal.php';
                    } ?>
