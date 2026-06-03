<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $onboardingSuggestions */
/** @var array<int, true> $followedSuggestionIds */

$stepTitle = 'Accounts you may like';
$stepSubtitle = 'Follow a few people to fill your feed. You can change this anytime.';
require dirname(__DIR__) . '/step-shell-start.php';
?>
            <?php if ($onboardingSuggestions === []) : ?>
            <p class="onboarding-empty-hint">No suggestions yet. Check back soon or skip to explore the app.</p>
            <?php else : ?>
            <ul class="onboarding-suggestion-list">
                <?php foreach ($onboardingSuggestions as $suggestion) :
                    $suggestionId = (int) ($suggestion['id'] ?? 0);
                    $isFollowed = isset($followedSuggestionIds[$suggestionId]);
                    $avatarUrl = userMediaUrl($suggestion, 'avatar_url', $url);
                    $displayName = (string) ($suggestion['display_name'] ?? 'User');
                    $handle = (string) ($suggestion['handle'] ?? '@user');
                    $bio = trim((string) ($suggestion['bio'] ?? ''));
                    $sharedCount = (int) ($suggestion['shared_interests'] ?? 0);
                    ?>
                <li class="onboarding-suggestion-item">
                    <img class="onboarding-suggestion-avatar" src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                    <div class="onboarding-suggestion-body">
                        <p class="onboarding-suggestion-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="onboarding-suggestion-handle"><?php echo htmlspecialchars($handle, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if ($bio !== '') : ?><p class="onboarding-suggestion-bio"><?php echo htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                        <?php if ($sharedCount > 0) : ?><p class="onboarding-suggestion-meta"><?php echo $sharedCount; ?> shared interest<?php echo $sharedCount === 1 ? '' : 's'; ?></p><?php endif; ?>
                    </div>
                    <button
                        type="button"
                        class="onboarding-interest-chip onboarding-suggestion-follow<?php echo $isFollowed ? ' is-selected' : ''; ?>"
                        data-user-id="<?php echo $suggestionId; ?>"
                        data-followed-on-load="<?php echo $isFollowed ? '1' : '0'; ?>"
                        aria-pressed="<?php echo $isFollowed ? 'true' : 'false'; ?>"
                    >
                        <span class="onboarding-suggestion-follow-label onboarding-suggestion-follow-label--follow">Follow</span>
                        <span class="onboarding-suggestion-follow-label onboarding-suggestion-follow-label--following">Following</span>
                        <span class="onboarding-suggestion-follow-label onboarding-suggestion-follow-label--unfollow">Unfollow</span>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <p class="onboarding-form-error" id="onboarding-step-error" hidden></p>
<?php
require dirname(__DIR__) . '/step-shell-end.php';
$onboardingPrimaryId = 'onboarding-suggestions-finish';
$onboardingPrimaryLabel = 'Finish';
require dirname(__DIR__) . '/step-footer.php';
