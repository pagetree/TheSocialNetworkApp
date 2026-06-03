<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var list<array<string, mixed>> $postParticipants */
/** @var bool $isLoggedIn */
/** @var int $currentUserId */
/** @var array<int, true> $postParticipantFollowedIds */

$postParticipants = $postParticipants ?? [];
$postParticipantFollowedIds = $postParticipantFollowedIds ?? [];
$currentUserId = $currentUserId ?? 0;
?>
                <aside class="app-sidebar app-sidebar--right" aria-label="Post activity sidebar">
                    <article class="profile-card post-participants-card">
                        <h2 class="post-participants-card-title">Relevant people</h2>
                        <?php if ($postParticipants === []) : ?>
                        <p class="post-participants-empty">No public profiles to show yet.</p>
                        <?php else : ?>
                        <ul class="post-participants-list">
                            <?php foreach ($postParticipants as $participantRow) :
                                $participantId = (int) ($participantRow['id'] ?? 0);
                                $viewerFollows = isset($postParticipantFollowedIds[$participantId]);
                                $participant = postParticipantPayload($participantRow, $url, $viewerFollows);
                                $participantName = (string) ($participant['display_name'] ?? 'User');
                                $participantHandle = (string) ($participant['handle'] ?? '@user');
                                $participantBio = (string) ($participant['bio'] ?? '');
                                $participantAvatar = (string) ($participant['avatar_url'] ?? '');
                                $participantProfileUrl = (string) ($participant['profile_url'] ?? '');
                                $showFollowBtn = $isLoggedIn
                                    && $participantId > 0
                                    && $participantId !== $currentUserId;
                                ?>
                            <li class="post-participants-item">
                                <div class="post-participants-row">
                                    <?php if ($participantProfileUrl !== '') : ?>
                                    <a class="post-participants-identity" href="<?php echo htmlspecialchars($participantProfileUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                        <img
                                            class="post-participants-avatar"
                                            src="<?php echo htmlspecialchars($participantAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo htmlspecialchars($participantName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                                            loading="lazy"
                                        >
                                        <span class="post-participants-meta">
                                            <span class="post-participants-name"><?php echo htmlspecialchars($participantName, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="post-participants-handle"><?php echo htmlspecialchars($participantHandle, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </span>
                                    </a>
                                    <?php else : ?>
                                    <div class="post-participants-identity">
                                        <img
                                            class="post-participants-avatar"
                                            src="<?php echo htmlspecialchars($participantAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo htmlspecialchars($participantName . ' avatar', ENT_QUOTES, 'UTF-8'); ?>"
                                            loading="lazy"
                                        >
                                        <span class="post-participants-meta">
                                            <span class="post-participants-name"><?php echo htmlspecialchars($participantName, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="post-participants-handle"><?php echo htmlspecialchars($participantHandle, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($showFollowBtn) : ?>
                                    <button
                                        type="button"
                                        class="profile-follow-btn post-participants-follow-btn<?php echo $viewerFollows ? ' is-following' : ''; ?>"
                                        data-user-id="<?php echo $participantId; ?>"
                                        data-following="<?php echo $viewerFollows ? '1' : '0'; ?>"
                                        aria-pressed="<?php echo $viewerFollows ? 'true' : 'false'; ?>"
                                        aria-label="<?php echo $viewerFollows ? 'Unfollow ' : 'Follow '; ?><?php echo htmlspecialchars($participantName, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <span class="profile-follow-btn-label profile-follow-btn-label--follow">Follow</span>
                                        <span class="profile-follow-btn-label profile-follow-btn-label--following">Following</span>
                                        <span class="profile-follow-btn-label profile-follow-btn-label--unfollow">Unfollow</span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($participantBio !== '') : ?>
                                <p class="post-participants-bio"><?php echo htmlspecialchars($participantBio, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </article>
                </aside>
