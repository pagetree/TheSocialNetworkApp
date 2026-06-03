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

if ($currentUserId < 1 || $menuOwnerUserId !== $currentUserId || $menuTargetId < 1) {
    return;
}

$removeLabel = $menuKind === 'reply' ? 'Remove reply' : 'Remove post';
$menuAriaLabel = $menuKind === 'reply' ? 'Reply options' : 'Post options';
?>
                            <div
                                class="post-menu"
                                data-menu-kind="<?php echo htmlspecialchars($menuKind, ENT_QUOTES, 'UTF-8'); ?>"
                                data-target-id="<?php echo $menuTargetId; ?>"
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
                                    <button
                                        type="button"
                                        class="post-menu-option post-menu-option--remove"
                                        role="menuitem"
                                    ><?php echo htmlspecialchars($removeLabel, ENT_QUOTES, 'UTF-8'); ?></button>
                                </div>
                            </div>
