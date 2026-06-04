<?php

declare(strict_types=1);

/** @var callable(string): string $url */
/** @var string $onboardingStep */
/** @var string $onboardingPrimaryId */
/** @var string $onboardingPrimaryLabel */
/** @var string|null $onboardingPrimaryHref */

$previousStep = onboardingPreviousStep($onboardingStep);
$onboardingPrimaryHref = $onboardingPrimaryHref ?? null;
?>
    <footer class="onboarding-panel-actions">
        <?php if ($previousStep !== null) : ?>
        <a
            href="<?php echo htmlspecialchars($url($previousStep['path']), ENT_QUOTES, 'UTF-8'); ?>"
            class="onboarding-btn onboarding-btn--ghost onboarding-btn--back"
        >
            <i data-lucide="arrow-left" aria-hidden="true"></i>
            <span><?php echo __e('onboarding.back'); ?></span>
        </a>
        <?php endif; ?>
        <?php if ($onboardingPrimaryHref !== null) : ?>
        <a
            href="<?php echo htmlspecialchars($onboardingPrimaryHref, ENT_QUOTES, 'UTF-8'); ?>"
            class="onboarding-btn onboarding-btn--primary"
        ><?php echo htmlspecialchars($onboardingPrimaryLabel, ENT_QUOTES, 'UTF-8'); ?></a>
        <?php else : ?>
        <button
            type="button"
            class="onboarding-btn onboarding-btn--primary"
            id="<?php echo htmlspecialchars($onboardingPrimaryId, ENT_QUOTES, 'UTF-8'); ?>"
        >
            <span class="onboarding-btn-spinner" aria-hidden="true" hidden></span>
            <span class="onboarding-btn-label"><?php echo htmlspecialchars($onboardingPrimaryLabel, ENT_QUOTES, 'UTF-8'); ?></span>
        </button>
        <?php endif; ?>
    </footer>
</section>
