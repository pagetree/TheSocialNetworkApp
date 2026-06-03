<?php

declare(strict_types=1);

/** @var string $replyMediaPrefix */
?>
                                <div class="post-composer-media-preview" id="<?php echo htmlspecialchars($replyMediaPrefix, ENT_QUOTES, 'UTF-8'); ?>-media-preview" hidden>
                                    <div class="post-composer-media-grid" id="<?php echo htmlspecialchars($replyMediaPrefix, ENT_QUOTES, 'UTF-8'); ?>-media-grid"></div>
                                </div>
                                <input
                                    type="file"
                                    id="<?php echo htmlspecialchars($replyMediaPrefix, ENT_QUOTES, 'UTF-8'); ?>-media-input"
                                    class="post-reply-media-input"
                                    hidden
                                    multiple
                                >
