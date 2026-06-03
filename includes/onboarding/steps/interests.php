<?php

declare(strict_types=1);

/** @var list<array{id: int, slug: string, label: string}> $onboardingInterests */
/** @var list<int> $userInterestIds */

$stepTitle = 'What are you into?';
$stepSubtitle = 'Pick up to ' . ONBOARDING_MAX_INTERESTS . ' interests to personalize your experience.';
require dirname(__DIR__) . '/step-shell-start.php';

$selectedMap = array_flip($userInterestIds);
?>
            <div class="onboarding-interest-grid" role="group" aria-label="Interests">
                <?php foreach ($onboardingInterests as $interest) :
                    $interestId = (int) $interest['id'];
                    $isSelected = isset($selectedMap[$interestId]);
                    ?>
                <label class="onboarding-interest-chip<?php echo $isSelected ? ' is-selected' : ''; ?>">
                    <input type="checkbox" name="interest_ids[]" value="<?php echo $interestId; ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                    <span><?php echo htmlspecialchars((string) $interest['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="onboarding-form-error" id="onboarding-step-error" hidden></p>
<?php
require dirname(__DIR__) . '/step-shell-end.php';
$onboardingPrimaryId = 'onboarding-interests-continue';
$onboardingPrimaryLabel = 'Continue';
require dirname(__DIR__) . '/step-footer.php';
