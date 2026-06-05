<?php

declare(strict_types=1);

/** @var array<string, mixed> $quotedPost */
/** @var callable(string): string $url */

$quotedAuthorName = (string) ($quotedPost['author']['display_name'] ?? 'User');
$quotedAuthorHandle = (string) ($quotedPost['author']['handle'] ?? '@user');
$quotedAuthorProfileUrl = (string) ($quotedPost['author']['profile_url'] ?? '');
$quotedAuthorAvatar = (string) ($quotedPost['author']['avatar_url'] ?? '');
$quotedBody = (string) ($quotedPost['body'] ?? '');
$quotedPostUrl = (string) ($quotedPost['post_url'] ?? '');
$quotedMediaItems = is_array($quotedPost['media'] ?? null) ? $quotedPost['media'] : [];
$post = $quotedPost;
?>
                        <article
                            class="post-quoted-card"
                            aria-label="<?php echo __e('quote.embedded'); ?>"
                            data-post-id="<?php echo (int) ($quotedPost['id'] ?? 0); ?>"
                            data-post-user-id="<?php echo (int) ($quotedPost['user_id'] ?? 0); ?>"
                        >
<?php if ($quotedPostUrl !== '') : ?>
                            <a class="post-quoted-card-link" href="<?php echo htmlspecialchars($quotedPostUrl, ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1" aria-hidden="true"></a>
<?php endif; ?>
                            <header class="post-header">
                                <img
                                    class="post-avatar"
                                    src="<?php echo htmlspecialchars($quotedAuthorAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo __e('sidebar.avatar_alt', ['name' => $quotedAuthorName]); ?>"
                                >
                                <div class="post-meta">
                                    <p class="post-meta-line">
<?php if ($quotedAuthorProfileUrl !== '') : ?>
                                        <span class="post-author"><?php echo htmlspecialchars($quotedAuthorName, ENT_QUOTES, 'UTF-8'); ?></span>
<?php else : ?>
                                        <span class="post-author"><?php echo htmlspecialchars($quotedAuthorName, ENT_QUOTES, 'UTF-8'); ?></span>
<?php endif; ?>
                                        <span class="post-handle"><?php echo htmlspecialchars($quotedAuthorHandle, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </p>
                                </div>
                            </header>
<?php if ($quotedBody !== '') : ?>
                            <p class="post-text"><?php echo formatPostBodyHtml($quotedBody, $url); ?></p>
<?php endif; ?>
<?php if (count($quotedMediaItems) > 0) {
    require __DIR__ . '/post-media-gallery.php';
} ?>
                        </article>
