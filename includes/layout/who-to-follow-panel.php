<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var bool $isLoggedIn */
/** @var list<array<string, mixed>> $whoToFollowSuggestions */
/** @var array<int, true> $whoToFollowFollowedIds */
?>
                    <article class="who-to-follow-card">
                        <h2 class="who-to-follow-card-title"><?php echo __e('sidebar.who_to_follow'); ?></h2>
                        <?php if ($whoToFollowSuggestions === []) : ?>
                        <p class="who-to-follow-empty"><?php echo __e('sidebar.no_suggestions'); ?></p>
                        <?php else : ?>
                        <ul class="who-to-follow-list">
                            <?php foreach ($whoToFollowSuggestions as $suggestion) :
                                $suggestionId = (int) ($suggestion['id'] ?? 0);
                                $viewerFollows = isset($whoToFollowFollowedIds[$suggestionId]);
                                $suggestionName = (string) ($suggestion['display_name'] ?? 'User');
                                $suggestionHandle = (string) ($suggestion['handle'] ?? '@user');
                                $suggestionAvatar = userMediaUrl($suggestion, 'avatar_url', $url);
                                $suggestionProfileUrl = profileUrlForUser($suggestion, $url);
                                ?>
                            <li class="who-to-follow-item">
                                <div class="who-to-follow-row">
                                    <a class="who-to-follow-identity" href="<?php echo htmlspecialchars($suggestionProfileUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                        <img
                                            class="who-to-follow-avatar"
                                            src="<?php echo htmlspecialchars($suggestionAvatar, ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo __e('sidebar.avatar_alt', ['name' => $suggestionName]); ?>"
                                            width="60"
                                            height="60"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                        <div class="who-to-follow-meta">
                                            <span class="who-to-follow-name"><?php echo htmlspecialchars($suggestionName, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="who-to-follow-handle"><?php echo htmlspecialchars($suggestionHandle, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    </a>
                                    <?php if ($isLoggedIn && $suggestionId > 0) : ?>
                                    <button
                                        type="button"
                                        class="profile-follow-btn post-participants-follow-btn<?php echo $viewerFollows ? ' is-following' : ''; ?>"
                                        data-user-id="<?php echo $suggestionId; ?>"
                                        data-following="<?php echo $viewerFollows ? '1' : '0'; ?>"
                                        aria-pressed="<?php echo $viewerFollows ? 'true' : 'false'; ?>"
                                        aria-label="<?php echo $viewerFollows
                                            ? __e('follow.unfollow_user', ['name' => $suggestionName])
                                            : __e('follow.follow_user', ['name' => $suggestionName]); ?>"
                                    >
                                        <span class="profile-follow-btn-label profile-follow-btn-label--follow"><?php echo __e('follow.follow'); ?></span>
                                        <span class="profile-follow-btn-label profile-follow-btn-label--following"><?php echo __e('follow.following'); ?></span>
                                        <span class="profile-follow-btn-label profile-follow-btn-label--unfollow"><?php echo __e('follow.unfollow'); ?></span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </article>
