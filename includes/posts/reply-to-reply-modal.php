<?php

declare(strict_types=1);

/** @var array<string, mixed> $post */
?>
<div class="reply-modal-overlay" id="reply-modal-overlay" hidden data-reply-level="nested">
    <div
        class="reply-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="reply-modal-title"
    >
        <span class="profile-edit-sr-only" id="reply-modal-title"><?php echo __e('reply.to_post'); ?></span>

        <header class="reply-modal-header">
            <img
                class="reply-modal-avatar"
                id="reply-modal-avatar"
                src="<?php echo htmlspecialchars((string) ($post['author']['avatar_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                alt=""
            >
            <div class="reply-modal-author">
                <p class="reply-modal-name" id="reply-modal-name"><?php echo htmlspecialchars((string) ($post['author']['display_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="reply-modal-handle" id="reply-modal-handle"><?php echo htmlspecialchars((string) ($post['author']['handle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </header>

        <div class="reply-modal-body">
            <textarea
                class="post-composer-input reply-modal-input"
                id="reply-modal-input"
                rows="4"
                maxlength="300"
                placeholder="<?php echo __e('reply.placeholder'); ?>"
                aria-describedby="reply-modal-char-counter reply-modal-error"
            ></textarea>
            <p class="post-composer-error reply-modal-error" id="reply-modal-error" hidden></p>
<?php
    $replyMediaPrefix = 'reply-modal';
    require __DIR__ . '/reply-composer-media.php';
?>
            <div class="reply-modal-footer-row">
                <div class="post-composer-tools reply-modal-tools" aria-label="<?php echo __e('reply.tools'); ?>">
                    <button type="button" class="post-tool-btn" id="reply-modal-image-btn" aria-label="<?php echo __e('composer.add_image'); ?>">
                        <i data-lucide="image" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="post-tool-btn" id="reply-modal-video-btn" aria-label="<?php echo __e('composer.add_video'); ?>">
                        <i data-lucide="film" aria-hidden="true"></i>
                    </button>
                </div>
                <div
                    class="post-char-counter reply-modal-char-counter"
                    id="reply-modal-char-counter"
                    role="status"
                    aria-live="polite"
                    hidden
                >
                    <svg class="post-char-counter-ring" viewBox="0 0 36 36" aria-hidden="true">
                        <circle class="post-char-counter-track" cx="18" cy="18" r="15.5"></circle>
                        <circle class="post-char-counter-progress reply-modal-char-progress" cx="18" cy="18" r="15.5"></circle>
                    </svg>
                </div>
                <div class="reply-modal-actions">
                    <button type="button" class="profile-edit-cancel reply-modal-cancel" id="reply-modal-cancel"><?php echo __e('common.cancel'); ?></button>
                    <button type="button" class="profile-edit-save reply-modal-submit" id="reply-modal-submit" disabled><?php echo __e('reply.submit'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="reply-modal-parent-id" value="">
