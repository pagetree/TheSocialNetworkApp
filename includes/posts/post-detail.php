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
$viewCount = formatEngagementCount((int) ($post['view_count'] ?? 0));
$interactionCount = formatEngagementCount((int) ($post['interaction_count'] ?? 0));
$postUserId = (int) ($post['user_id'] ?? 0);
$trackStats = $currentUserId > 0 && $postUserId !== $currentUserId;
$postMediaItems = is_array($post['media'] ?? null) ? $post['media'] : [];
$hasMedia = count($postMediaItems) > 0;
$replies = $replies ?? [];
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

                        <footer class="post-detail-meta" aria-label="<?php echo __e('post.info'); ?>">
                            <?php if ($detailDateLabel !== '') : ?>
                            <p class="post-detail-meta-item">
                                <i data-lucide="calendar" aria-hidden="true"></i>
                                <time datetime="<?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($detailDateLabel, ENT_QUOTES, 'UTF-8'); ?></time>
                            </p>
                            <?php endif; ?>
                            <button
                                type="button"
                                class="post-detail-meta-item post-detail-meta-views-btn post-action-stat-views"
                                aria-label="<?php echo __e('post.view_stats'); ?>"
                                data-post-id="<?php echo (int) ($post['id'] ?? 0); ?>"
                            >
                                <span><span class="post-detail-view-count"><?php echo htmlspecialchars($viewCount, ENT_QUOTES, 'UTF-8'); ?></span> <?php echo __e('stats.metrics.views'); ?></span>
                            </button>
                            <span class="post-stat-interactions" hidden aria-hidden="true"><?php echo htmlspecialchars($interactionCount, ENT_QUOTES, 'UTF-8'); ?></span>
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
