<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var bool $isLoggedIn */
/** @var list<array<string, mixed>> $whoToFollowSuggestions */
/** @var array<int, true> $whoToFollowFollowedIds */
/** @var array<int, true> $whoToFollowFollowsViewerIds */

$whoToFollowFollowsViewerIds = $whoToFollowFollowsViewerIds ?? [];
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
                                $followsViewer = isset($whoToFollowFollowsViewerIds[$suggestionId]);
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
                                    <?php if ($isLoggedIn && $suggestionId > 0) :
                                        $followUserId = $suggestionId;
                                        $followUserName = $suggestionName;
                                        $followBtnClass = 'post-participants-follow-btn';
                                        $followBtnId = '';
                                        require dirname(__DIR__) . '/profile/follow-button.php';
                                    endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </article>
