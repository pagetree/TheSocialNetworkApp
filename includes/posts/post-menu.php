<?php

declare(strict_types=1);

/** @var string $menuKind post|reply */
/** @var int $menuTargetId */
/** @var int $menuOwnerUserId */
/** @var int $currentUserId */
/** @var int $menuConversationId */

$menuKind = $menuKind ?? 'post';
$menuTargetId = (int) ($menuTargetId ?? 0);
$menuOwnerUserId = (int) ($menuOwnerUserId ?? 0);
$menuConversationId = (int) ($menuConversationId ?? 0);
$currentUserId = (int) ($currentUserId ?? 0);

if ($currentUserId < 1 || $menuTargetId < 1) {
    return;
}

$isOwn = $currentUserId > 0 && $menuOwnerUserId === $currentUserId;
$menuAriaLabel = $menuKind === 'reply' ? __('reply.options') : __('post.options');
?>
                            <div
                                class="post-menu"
                                data-menu-kind="<?php echo htmlspecialchars($menuKind, ENT_QUOTES, 'UTF-8'); ?>"
                                data-target-id="<?php echo $menuTargetId; ?>"
                                data-is-own="<?php echo $isOwn ? '1' : '0'; ?>"
                                <?php if ($menuKind === 'reply') : ?>
                                data-conversation-id="<?php echo $menuConversationId; ?>"
                                <?php endif; ?>
                            >
                                <button
                                    type="button"
                                    class="post-menu-btn"
                                    aria-haspopup="menu"
                                    aria-expanded="false"
                                    aria-label="<?php echo htmlspecialchars($menuAriaLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <i data-lucide="ellipsis" aria-hidden="true"></i>
                                </button>
                                <div class="post-menu-dropdown" role="menu" hidden>
                                    <?php if ($isOwn) : ?>
                                    <button
                                        type="button"
                                        class="post-menu-option post-menu-option--remove"
                                        role="menuitem"
                                    >
                                        <i data-lucide="trash-2" aria-hidden="true"></i>
                                        <span><?php echo __e('post.remove'); ?></span>
                                    </button>
                                    <?php else : ?>
                                    <button
                                        type="button"
                                        class="post-menu-option post-menu-option--report"
                                        role="menuitem"
                                    >
                                        <i data-lucide="flag" aria-hidden="true"></i>
                                        <span><?php echo __e('post.report'); ?></span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
