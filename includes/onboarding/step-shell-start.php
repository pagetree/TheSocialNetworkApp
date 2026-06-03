<?php

declare(strict_types=1);

/** @var string $stepTitle */
/** @var string $stepSubtitle */
/** @var string $onboardingStep */
/** @var string $stepSectionClass */

$stepShowSkip = ($onboardingStep ?? '') !== 'welcome';
$stepSectionClass = trim((string) ($stepSectionClass ?? 'onboarding-panel'));
?>
<section class="<?php echo htmlspecialchars($stepSectionClass, ENT_QUOTES, 'UTF-8'); ?>" data-onboarding-step="<?php echo htmlspecialchars($onboardingStep, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($stepShowSkip) : ?>
    <div class="onboarding-step-skip-row">
        <button type="button" class="onboarding-step-skip" data-onboarding-skip>Skip</button>
    </div>
    <?php endif; ?>
    <div class="onboarding-step-layout">
        <div class="onboarding-step-col onboarding-step-col--lead">
            <header class="onboarding-panel-header onboarding-step-header">
                <h1 class="onboarding-panel-title"><?php echo htmlspecialchars($stepTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="onboarding-panel-subtitle"><?php echo htmlspecialchars($stepSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
            </header>
        </div>
        <div class="onboarding-step-col onboarding-step-col--body">
