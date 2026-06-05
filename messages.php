<?php

declare(strict_types=1);
?>
                    <div class="chat-page" id="chat-page">
                        <section class="chat-window is-active" aria-label="<?php echo __e('nav.page_chat'); ?>">
                            <div class="chat-messages" id="chat-messages" role="log" aria-live="polite" aria-relevant="additions">
                                <div class="chat-empty" role="status">
                                    <div class="chat-empty-icon" aria-hidden="true">
                                        <i data-lucide="messages-square"></i>
                                    </div>
                                    <h2 class="chat-empty-title"><?php echo __e('chat.dev_title'); ?></h2>
                                    <p class="chat-empty-hint"><?php echo __e('chat.dev_hint'); ?></p>
                                </div>
                            </div>
                        </section>

                        <footer class="chat-composer" aria-label="<?php echo __e('chat.composer_label'); ?>">
                            <div class="chat-composer-body">
                                <label class="chat-composer-field">
                                    <span class="visually-hidden"><?php echo __e('chat.message_placeholder'); ?></span>
                                    <textarea
                                        class="chat-composer-input"
                                        id="chat-composer-input"
                                        rows="1"
                                        maxlength="2000"
                                        placeholder="<?php echo __e('chat.message_placeholder'); ?>"
                                    ></textarea>
                                </label>
                                <div class="chat-composer-actions">
                                    <button type="button" class="chat-composer-send" aria-label="<?php echo __e('chat.send'); ?>">
                                        <i data-lucide="send" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </footer>
                    </div>
