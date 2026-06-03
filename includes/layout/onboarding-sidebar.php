<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var string $onboardingStep */

$steps = onboardingSteps();
$currentIndex = 0;
foreach ($steps as $index => $step) {
    if ($step['key'] === $onboardingStep) {
        $currentIndex = $index;
        break;
    }
}
?>
                <aside class="app-sidebar app-sidebar--onboarding">
                    <nav class="onboarding-steps" aria-label="Onboarding steps">
                        <p class="onboarding-steps-heading"><?php echo $onboardingStep === 'welcome' ? 'Your setup journey' : 'Set up your profile'; ?></p>
                        <?php if ($onboardingStep === 'welcome') : ?>
                        <p class="onboarding-steps-intro">Four quick stops. Everything is optional&mdash;change it all later from your profile.</p>
                        <?php endif; ?>
                        <ol class="onboarding-steps-list">
                            <?php foreach ($steps as $index => $step) :
                                $isCurrent = $step['key'] === $onboardingStep;
                                $isComplete = $index < $currentIndex;
                                $itemClass = 'onboarding-step';
                                if ($isCurrent) {
                                    $itemClass .= ' is-current';
                                }
                                if ($isComplete) {
                                    $itemClass .= ' is-complete';
                                }
                                ?>
                            <li class="<?php echo htmlspecialchars($itemClass, ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="onboarding-step-marker" aria-hidden="true">
                                    <?php if ($isComplete) : ?>
                                    <i data-lucide="check" aria-hidden="true"></i>
                                    <?php else : ?>
                                    <span class="onboarding-step-number"><?php echo $index + 1; ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="onboarding-step-label"><?php echo htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                </aside>
