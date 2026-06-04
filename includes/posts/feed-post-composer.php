<?php

declare(strict_types=1);

/** @var string $composerAvatarUrl */
?>
                    <article class="post-card post-composer">
                        <img
                            class="post-avatar"
                            src="<?php echo htmlspecialchars($composerAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="Your avatar"
                        >
                        <div class="post-composer-body">
                            <div class="post-composer-box">
                                <textarea
                                    class="post-composer-input"
                                    id="post-composer-input"
                                    rows="3"
                                    maxlength="300"
                                    placeholder="What's happening?"
                                    aria-describedby="post-char-counter-label post-composer-error"
                                ></textarea>
                                <p class="post-composer-error" id="post-composer-error" hidden></p>
                                <div class="post-composer-media-preview" id="post-composer-media-preview" hidden>
                                    <div class="post-composer-media-grid" id="post-composer-media-grid"></div>
                                </div>
                                <input
                                    type="file"
                                    id="post-composer-media-input"
                                    name="media[]"
                                    class="post-composer-media-input"
                                    hidden
                                    multiple
                                >
                                <div class="post-composer-actions">
                                    <div class="post-composer-tools" aria-label="Post tools">
                                        <button type="button" class="post-tool-btn" id="post-composer-image-btn" aria-label="Add image">
                                            <i data-lucide="image" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="post-tool-btn" id="post-composer-video-btn" aria-label="Add video">
                                            <i data-lucide="film" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="post-composer-submit-wrap">
                                        <div
                                            class="post-char-counter"
                                            id="post-char-counter-label"
                                            role="status"
                                            aria-live="polite"
                                            hidden
                                        >
                                            <svg class="post-char-counter-ring" viewBox="0 0 36 36" aria-hidden="true">
                                                <circle class="post-char-counter-track" cx="18" cy="18" r="15.5"></circle>
                                                <circle class="post-char-counter-progress" cx="18" cy="18" r="15.5"></circle>
                                            </svg>
                                        </div>
                                        <button type="button" class="post-submit-btn" id="post-composer-submit" disabled>Post</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
