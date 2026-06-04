<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var bool $isLoggedIn */
/** @var array<string, mixed> $post */
/** @var string $composerAvatarUrl */
/** @var int $currentUserId */

$postId = (int) ($post['id'] ?? 0);
?>
                    <section class="post-reply-composer" aria-label="<?php echo __e('reply.write'); ?>" data-reply-level="top">
                        <img
                            class="post-reply-composer-avatar"
                            src="<?php echo htmlspecialchars($composerAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo __e('composer.your_avatar'); ?>"
                        >
                        <div class="post-reply-composer-body">
                            <div class="post-composer-box post-reply-composer-box">
                                <textarea
                                    class="post-composer-input post-reply-composer-input"
                                    id="post-reply-input"
                                    rows="3"
                                    maxlength="300"
                                    placeholder="<?php echo __e('reply.placeholder'); ?>"
                                    aria-describedby="post-reply-char-counter-label post-reply-form-error"
                                ></textarea>
                                <p class="post-composer-error post-reply-form-error" id="post-reply-form-error" hidden></p>
<?php
    $replyMediaPrefix = 'post-reply';
    require __DIR__ . '/reply-composer-media.php';
?>
                                <div class="post-composer-actions">
                                    <div class="post-composer-tools" aria-label="<?php echo __e('reply.tools'); ?>">
                                        <button type="button" class="post-tool-btn" id="post-reply-image-btn" aria-label="<?php echo __e('composer.add_image'); ?>">
                                            <i data-lucide="image" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="post-tool-btn" id="post-reply-video-btn" aria-label="<?php echo __e('composer.add_video'); ?>">
                                            <i data-lucide="film" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="post-composer-submit-wrap">
                                        <div
                                            class="post-char-counter"
                                            id="post-reply-char-counter-label"
                                            role="status"
                                            aria-live="polite"
                                            hidden
                                        >
                                            <svg class="post-char-counter-ring" viewBox="0 0 36 36" aria-hidden="true">
                                                <circle class="post-char-counter-track" cx="18" cy="18" r="15.5"></circle>
                                                <circle class="post-char-counter-progress post-reply-char-counter-progress" cx="18" cy="18" r="15.5"></circle>
                                            </svg>
                                        </div>
                                        <button type="button" class="post-submit-btn" id="post-reply-submit" disabled><?php echo __e('reply.reply'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
