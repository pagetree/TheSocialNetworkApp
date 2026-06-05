<?php

declare(strict_types=1);

/** @var int $followUserId */
/** @var string $followUserName */
/** @var bool $viewerFollows */
/** @var bool $followsViewer */
/** @var string $followBtnClass */
/** @var string $followBtnId */

$followUserId = (int) ($followUserId ?? 0);
$followUserName = (string) ($followUserName ?? 'User');
$viewerFollows = (bool) ($viewerFollows ?? false);
$followsViewer = (bool) ($followsViewer ?? false);
$followBtnClass = trim((string) ($followBtnClass ?? ''));
$followBtnId = trim((string) ($followBtnId ?? ''));
$showFollowBack = !$viewerFollows && $followsViewer;

$btnClasses = 'profile-follow-btn';
if ($followBtnClass !== '') {
    $btnClasses .= ' ' . $followBtnClass;
}
if ($viewerFollows) {
    $btnClasses .= ' is-following';
} elseif ($showFollowBack) {
    $btnClasses .= ' is-follow-back';
}

if ($viewerFollows) {
    $followAriaLabel = __e('follow.unfollow_user', ['name' => $followUserName]);
} elseif ($showFollowBack) {
    $followAriaLabel = __e('follow.follow_back_user', ['name' => $followUserName]);
} else {
    $followAriaLabel = __e('follow.follow_user', ['name' => $followUserName]);
}
?>
                                    <button
                                        type="button"
                                        class="<?php echo htmlspecialchars($btnClasses, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php if ($followBtnId !== '') : ?>id="<?php echo htmlspecialchars($followBtnId, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
                                        data-user-id="<?php echo $followUserId; ?>"
                                        data-user-name="<?php echo htmlspecialchars($followUserName, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-following="<?php echo $viewerFollows ? '1' : '0'; ?>"
                                        data-follows-viewer="<?php echo $followsViewer ? '1' : '0'; ?>"
                                        aria-pressed="<?php echo $viewerFollows ? 'true' : 'false'; ?>"
                                        aria-label="<?php echo $followAriaLabel; ?>"
                                    >
                                        <span class="profile-follow-btn-label profile-follow-btn-label--follow"><?php echo __e('follow.follow'); ?></span>
                                        <span class="profile-follow-btn-label profile-follow-btn-label--follow-back"><?php echo __e('follow.follow_back'); ?></span>
                                        <span class="profile-follow-btn-label profile-follow-btn-label--following"><?php echo __e('follow.following'); ?></span>
                                        <span class="profile-follow-btn-label profile-follow-btn-label--unfollow"><?php echo __e('follow.unfollow'); ?></span>
                                    </button>
