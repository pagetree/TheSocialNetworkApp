<?php

declare(strict_types=1);

/** @var string $composerAvatarUrl */
?>
                    <div class="post-composer-modal" id="post-composer-modal">
                        <button
                            type="button"
                            class="post-composer-fab"
                            id="post-composer-fab"
                            aria-controls="post-composer-modal-panel"
                            aria-expanded="false"
                            aria-label="<?php echo __e('composer.create_post'); ?>"
                        >
                            <i data-lucide="plus" aria-hidden="true"></i>
                        </button>
                        <div
                            class="post-composer-modal-overlay"
                            id="post-composer-modal-overlay"
                            hidden
                        >
                            <button
                                type="button"
                                class="post-composer-modal-backdrop"
                                id="post-composer-modal-backdrop"
                                aria-label="<?php echo __e('composer.close'); ?>"
                                tabindex="-1"
                            ></button>
                            <div
                                class="post-composer-modal-panel"
                                id="post-composer-modal-panel"
                                role="dialog"
                                aria-modal="true"
                                aria-labelledby="post-composer-modal-title"
                            >
                                <header class="post-composer-modal-header">
                                    <h2 id="post-composer-modal-title" class="post-composer-modal-title"><?php echo __e('composer.create_post'); ?></h2>
                                    <button
                                        type="button"
                                        class="post-composer-modal-close"
                                        id="post-composer-modal-close"
                                        aria-label="<?php echo __e('composer.close'); ?>"
                                    >
                                        <i data-lucide="x" aria-hidden="true"></i>
                                    </button>
                                </header>
                                <div class="post-composer-modal-body">
<?php require __DIR__ . '/feed-post-composer.php'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
