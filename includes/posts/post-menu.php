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
$menuAriaLabel = $menuKind === 'reply' ? 'Reply options' : 'Post options';
$removeLabel = $menuKind === 'reply' ? 'Remove reply' : 'Remove post';
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
                                    ><?php echo htmlspecialchars($removeLabel, ENT_QUOTES, 'UTF-8'); ?></button>
                                    <?php else : ?>
                                    <span
                                        class="post-menu-option post-menu-option--placeholder"
                                        role="menuitem"
                                        aria-disabled="true"
                                    >In progress</span>
                                    <?php endif; ?>
                                </div>
                            </div>
