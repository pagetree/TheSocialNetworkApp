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
            <div class="onboarding-chrome">
                <div class="onboarding-chrome-top">
                    <a
                        href="<?php echo htmlspecialchars($url('/onboarding/welcome'), ENT_QUOTES, 'UTF-8'); ?>"
                        class="onboarding-chrome-logo-link"
                        aria-label="<?php echo __e('nav.home'); ?>"
                    >
                        <img
                            class="onboarding-chrome-logo"
                            src="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>"
                            alt=""
                        >
                    </a>
                    <?php require __DIR__ . '/lang-switcher.php'; ?>
                    <?php require __DIR__ . '/theme-toggle.php'; ?>
                </div>

                <nav class="onboarding-inline-steps" aria-label="<?php echo __e('onboarding.progress'); ?>">
                    <ol class="onboarding-inline-steps-list">
                        <?php foreach ($steps as $index => $step) :
                            $isCurrent = $step['key'] === $onboardingStep;
                            $isComplete = $index < $currentIndex;
                            $itemClass = 'onboarding-inline-step';
                            if ($isCurrent) {
                                $itemClass .= ' is-current';
                            }
                            if ($isComplete) {
                                $itemClass .= ' is-complete';
                            }
                            ?>
                        <li class="<?php echo htmlspecialchars($itemClass, ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="onboarding-inline-step-marker" aria-hidden="true">
                                <?php if ($isComplete) : ?>
                                <span class="onboarding-inline-step-check">&#10003;</span>
                                <?php else : ?>
                                <span class="onboarding-inline-step-number"><?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="onboarding-inline-step-label"><?php echo htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <?php if ($index < count($steps) - 1) : ?>
                        <li class="onboarding-inline-step-connector" aria-hidden="true"></li>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            </div>
