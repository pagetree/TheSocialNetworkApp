<?php

declare(strict_types=1);

/** @var array<string, mixed> $reply */
/** @var callable(string): string $url */
/** @var int $depth */
/** @var int $currentUserId */
/** @var int $menuConversationId */

$depth = $depth ?? 0;
$currentUserId = (int) ($currentUserId ?? 0);
$menuConversationId = (int) ($menuConversationId ?? 0);
$replyUserId = (int) ($reply['author']['user_id'] ?? $reply['user_id'] ?? 0);
$authorName = (string) ($reply['author']['display_name'] ?? 'User');
$authorHandle = (string) ($reply['author']['handle'] ?? '@user');
$authorAvatar = (string) ($reply['author']['avatar_url'] ?? '');
$replyBody = (string) ($reply['body'] ?? '');
$replyTimeLabel = (string) ($reply['time_label'] ?? '');
$createdAt = (string) ($reply['created_at'] ?? '');
$likeCount = formatEngagementCount((int) ($reply['like_count'] ?? 0));
$replyCount = formatEngagementCount((int) ($reply['reply_count'] ?? 0));
$replyId = (int) ($reply['id'] ?? 0);
$nestedClass = $depth > 0 ? ' post-reply-item--nested' : '';
$nestedStyle = $depth > 0 ? ' style="--reply-depth: ' . (int) $depth . ';"' : '';
?>
                        <article
                            class="post-reply-item<?php echo $nestedClass; ?>"
                            data-reply-id="<?php echo (int) ($reply['id'] ?? 0); ?>"
                            data-reply-depth="<?php echo (int) $depth; ?>"
                            data-parent-reply-id="<?php echo (int) ($reply['parent_reply_id'] ?? 0); ?>"
                            data-reply-user-id="<?php echo $replyUserId; ?>"
                            <?php echo $nestedStyle; ?>
                        >
                            <div class="post-reply-avatar-col">
                                <img
                                    class="post-reply-avatar"
                                    src="<?php echo htmlspecialchars($authorAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($authorName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <span class="post-reply-thread-line" aria-hidden="true"></span>
                            </div>
                            <div class="post-reply-body">
                                <header class="post-reply-header">
                                    <p class="post-reply-meta-line">
                                        <span class="post-reply-author"><?php echo htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="post-reply-handle"><?php echo htmlspecialchars($authorHandle, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($replyTimeLabel !== '') : ?>
                                        <time class="post-reply-time" datetime="<?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($replyTimeLabel, ENT_QUOTES, 'UTF-8'); ?></time>
                                        <?php endif; ?>
                                    </p>
                                    <?php
                                    $menuKind = 'reply';
                                    $menuTargetId = (int) ($reply['id'] ?? 0);
                                    $menuOwnerUserId = $replyUserId;
                                    require __DIR__ . '/post-menu.php';
                                    ?>
                                </header>
                                <?php if ($replyBody !== '') : ?>
                                <p class="post-reply-text"><?php echo formatPostBodyHtml($replyBody, $url); ?></p>
                                <?php endif; ?>
                                <?php
                                $replyMedia = is_array($reply['media'] ?? null) ? $reply['media'] : [];
                                if ($replyMedia !== []) {
                                    $post = ['media' => $replyMedia];
                                    require __DIR__ . '/post-media-gallery.php';
                                }
                                ?>
                                <footer class="post-actions post-reply-actions" aria-label="Reply engagement">
                                    <button type="button" class="post-action post-reply-action-reply" aria-label="Reply to this reply">
                                        <i data-lucide="message-circle" aria-hidden="true"></i>
                                        <span><?php echo htmlspecialchars($replyCount, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </button>
                                    <button type="button" class="post-action" aria-label="Repost reply">
                                        <i data-lucide="repeat-2" aria-hidden="true"></i>
                                        <span>0</span>
                                    </button>
                                    <button type="button" class="post-action" aria-label="Like reply">
                                        <i data-lucide="heart" aria-hidden="true"></i>
                                        <span><?php echo htmlspecialchars($likeCount, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </button>
                                    <button
                                        type="button"
                                        class="post-action post-action-stat-views"
                                        aria-label="View reply stats"
                                        data-reply-id="<?php echo $replyId; ?>"
                                    >
                                        <i data-lucide="bar-chart-2" aria-hidden="true"></i>
                                        <span><?php echo htmlspecialchars($replyCount, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </button>
                                </footer>
                            </div>
                        </article>
