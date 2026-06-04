<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $onboardingSuggestions */
/** @var array<int, true> $followedSuggestionIds */

$stepTitle = __('onboarding.suggestions.title');
$stepSubtitle = __('onboarding.suggestions.subtitle');
require dirname(__DIR__) . '/step-shell-start.php';
?>
            <?php if ($onboardingSuggestions === []) : ?>
            <p class="onboarding-empty-hint"><?php echo __e('onboarding.suggestions.empty'); ?></p>
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
                        <?php if ($sharedCount > 0) : ?><p class="onboarding-suggestion-meta"><?php echo htmlspecialchars(__n('onboarding.suggestions.shared_interest', $sharedCount, ['count' => $sharedCount]), ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                    </div>
                    <button
                        type="button"
                        class="onboarding-interest-chip onboarding-suggestion-follow<?php echo $isFollowed ? ' is-selected' : ''; ?>"
                        data-user-id="<?php echo $suggestionId; ?>"
                        data-followed-on-load="<?php echo $isFollowed ? '1' : '0'; ?>"
                        aria-pressed="<?php echo $isFollowed ? 'true' : 'false'; ?>"
                    >
                        <span class="onboarding-suggestion-follow-label onboarding-suggestion-follow-label--follow"><?php echo __e('follow.follow'); ?></span>
                        <span class="onboarding-suggestion-follow-label onboarding-suggestion-follow-label--following"><?php echo __e('follow.following'); ?></span>
                        <span class="onboarding-suggestion-follow-label onboarding-suggestion-follow-label--unfollow"><?php echo __e('follow.unfollow'); ?></span>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <p class="onboarding-form-error" id="onboarding-step-error" hidden></p>
<?php
require dirname(__DIR__) . '/step-shell-end.php';
$onboardingPrimaryId = 'onboarding-suggestions-finish';
$onboardingPrimaryLabel = __('onboarding.finish');
require dirname(__DIR__) . '/step-footer.php';
