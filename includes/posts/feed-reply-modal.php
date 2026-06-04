<?php

declare(strict_types=1);

/** @var string $composerAvatarUrl */
?>
<div class="feed-reply-modal-overlay" id="feed-reply-modal-overlay" hidden>
    <div
        class="feed-reply-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="feed-reply-modal-title"
    >
        <span class="profile-edit-sr-only" id="feed-reply-modal-title"><?php echo __e('reply.to_post'); ?></span>
        <button type="button" class="feed-reply-modal-close" id="feed-reply-modal-close" aria-label="<?php echo __e('composer.close_reply'); ?>">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>

        <article class="feed-reply-modal-preview post-card" id="feed-reply-modal-preview" aria-label="<?php echo __e('post.info'); ?>">
            <header class="post-header">
                <img
                    class="post-avatar"
                    id="feed-reply-preview-avatar"
                    src=""
                    alt=""
                >
                <div class="post-meta">
                    <p class="post-meta-line">
                        <span class="post-author" id="feed-reply-preview-author"></span>
                        <span class="post-handle" id="feed-reply-preview-handle"></span>
                    </p>
                </div>
            </header>
            <p class="post-text" id="feed-reply-preview-text" hidden></p>
        </article>

        <section class="post-reply-composer feed-reply-modal-composer" aria-label="<?php echo __e('reply.write'); ?>">
            <img
                class="post-reply-composer-avatar"
                src="<?php echo htmlspecialchars($composerAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo __e('composer.your_avatar'); ?>"
            >
            <div class="post-reply-composer-body">
                <div class="post-composer-box post-reply-composer-box">
                    <textarea
                        class="post-composer-input post-reply-composer-input"
                        id="feed-reply-input"
                        rows="3"
                        maxlength="300"
                        placeholder="<?php echo __e('reply.placeholder'); ?>"
                        aria-describedby="feed-reply-char-counter feed-reply-error"
                    ></textarea>
                    <p class="post-composer-error post-reply-form-error" id="feed-reply-error" hidden></p>
                    <input type="hidden" id="feed-reply-post-id" value="">
<?php
    $replyMediaPrefix = 'feed-reply';
    require __DIR__ . '/reply-composer-media.php';
?>
                    <div class="post-composer-actions">
                        <div class="post-composer-tools" aria-label="<?php echo __e('reply.tools'); ?>">
                            <button type="button" class="post-tool-btn" id="feed-reply-image-btn" aria-label="<?php echo __e('composer.add_image'); ?>">
                                <i data-lucide="image" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="post-tool-btn" id="feed-reply-video-btn" aria-label="<?php echo __e('composer.add_video'); ?>">
                                <i data-lucide="film" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="post-composer-submit-wrap">
                            <div
                                class="post-char-counter"
                                id="feed-reply-char-counter"
                                role="status"
                                aria-live="polite"
                                hidden
                            >
                                <svg class="post-char-counter-ring" viewBox="0 0 36 36" aria-hidden="true">
                                    <circle class="post-char-counter-track" cx="18" cy="18" r="15.5"></circle>
                                    <circle class="post-char-counter-progress feed-reply-char-counter-progress" cx="18" cy="18" r="15.5"></circle>
                                </svg>
                            </div>
                            <button type="button" class="post-submit-btn" id="feed-reply-submit" disabled><?php echo __e('reply.reply'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
