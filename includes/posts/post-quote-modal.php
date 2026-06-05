<?php

declare(strict_types=1);

/** @var string $composerAvatarUrl */
?>
<div class="post-quote-modal-overlay" id="post-quote-modal-overlay" hidden>
    <div
        class="post-quote-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="post-quote-modal-title"
    >
        <header class="post-quote-modal-header">
            <h2 id="post-quote-modal-title" class="post-quote-modal-title"><?php echo __e('quote.title'); ?></h2>
            <button type="button" class="post-quote-modal-close" id="post-quote-modal-close" aria-label="<?php echo __e('composer.close'); ?>">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </header>

        <section class="post-quote-modal-composer post-card post-composer" aria-label="<?php echo __e('quote.write'); ?>">
            <img
                class="post-avatar"
                src="<?php echo htmlspecialchars($composerAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo __e('composer.your_avatar'); ?>"
            >
            <div class="post-composer-body">
                <div class="post-composer-box">
                    <textarea
                        class="post-composer-input"
                        id="post-quote-input"
                        rows="3"
                        maxlength="300"
                        placeholder="<?php echo __e('composer.placeholder'); ?>"
                        aria-describedby="post-quote-char-counter post-quote-error"
                    ></textarea>
                    <p class="post-composer-error" id="post-quote-error" hidden></p>
                    <input type="hidden" id="post-quote-post-id" value="">
<?php
    $replyMediaPrefix = 'post-quote';
    require __DIR__ . '/reply-composer-media.php';
?>
                    <div class="post-composer-actions">
                        <div class="post-composer-tools" aria-label="<?php echo __e('composer.tools'); ?>">
                            <button type="button" class="post-tool-btn" id="post-quote-image-btn" aria-label="<?php echo __e('composer.add_image'); ?>">
                                <i data-lucide="image" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="post-tool-btn" id="post-quote-video-btn" aria-label="<?php echo __e('composer.add_video'); ?>">
                                <i data-lucide="film" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="post-composer-submit-wrap">
                            <div
                                class="post-char-counter"
                                id="post-quote-char-counter"
                                role="status"
                                aria-live="polite"
                                hidden
                            >
                                <svg class="post-char-counter-ring" viewBox="0 0 36 36" aria-hidden="true">
                                    <circle class="post-char-counter-track" cx="18" cy="18" r="15.5"></circle>
                                    <circle class="post-char-counter-progress post-quote-char-counter-progress" cx="18" cy="18" r="15.5"></circle>
                                </svg>
                            </div>
                            <button type="button" class="post-submit-btn" id="post-quote-submit"><?php echo __e('composer.post'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="post-quote-modal-preview-slot">
            <article class="post-quote-modal-preview post-quoted-embed" id="post-quote-modal-preview" aria-label="<?php echo __e('quote.preview'); ?>">
                <header class="post-header">
                    <img
                        class="post-avatar"
                        id="post-quote-preview-avatar"
                        src=""
                        alt=""
                    >
                    <div class="post-meta">
                        <p class="post-meta-line">
                            <span class="post-author" id="post-quote-preview-author"></span>
                            <span class="post-handle" id="post-quote-preview-handle"></span>
                        </p>
                    </div>
                </header>
                <p class="post-text" id="post-quote-preview-text" hidden></p>
                <div id="post-quote-preview-media"></div>
            </article>
        </div>
    </div>
</div>
