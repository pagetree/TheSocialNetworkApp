<?php

declare(strict_types=1);

/** @var list<array{id: int, slug: string, label: string}> $onboardingInterests */
/** @var list<int> $userInterestIds */

$stepTitle = __('onboarding.interests.title');
$stepSubtitle = __('onboarding.interests.subtitle', ['max' => ONBOARDING_MAX_INTERESTS]);
require dirname(__DIR__) . '/step-shell-start.php';

$selectedMap = array_flip($userInterestIds);
$interestGroups = groupOnboardingInterests($onboardingInterests);
?>
            <div class="onboarding-interest-groups">
                <?php foreach ($interestGroups as $group) : ?>
                <section class="onboarding-interest-group">
                    <h2 class="onboarding-interest-group-title"><?php echo htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <div class="onboarding-interest-grid" role="group" aria-label="<?php echo htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php foreach ($group['interests'] as $interest) :
                            $interestId = (int) $interest['id'];
                            $isSelected = isset($selectedMap[$interestId]);
                            ?>
                        <label class="onboarding-interest-chip<?php echo $isSelected ? ' is-selected' : ''; ?>">
                            <input type="checkbox" name="interest_ids[]" value="<?php echo $interestId; ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars((string) $interest['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </div>
            <p class="onboarding-form-error" id="onboarding-step-error" hidden></p>
<?php
require dirname(__DIR__) . '/step-shell-end.php';
$onboardingPrimaryId = 'onboarding-interests-continue';
$onboardingPrimaryLabel = __('onboarding.continue');
require dirname(__DIR__) . '/step-footer.php';
