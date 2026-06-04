<?php

declare(strict_types=1);

/** @var array<string, mixed> $currentUser */

$stepTitle = __('onboarding.bio.title');
$stepSubtitle = __('onboarding.bio.subtitle');
require dirname(__DIR__) . '/step-shell-start.php';

$currentBio = trim((string) ($currentUser['bio'] ?? ''));
?>
            <div class="auth-field onboarding-bio-field">
                <textarea class="onboarding-bio-input" id="onboarding-bio-input" rows="5" maxlength="<?php echo PROFILE_BIO_MAX_LENGTH; ?>" placeholder="<?php echo __e('onboarding.bio.placeholder'); ?>" aria-label="<?php echo __e('onboarding.bio.aria'); ?>"><?php echo htmlspecialchars($currentBio, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <span class="onboarding-char-count" id="onboarding-bio-count"><?php echo mb_strlen($currentBio); ?> / <?php echo PROFILE_BIO_MAX_LENGTH; ?></span>
            </div>
            <p class="onboarding-form-error" id="onboarding-step-error" hidden></p>
<?php
require dirname(__DIR__) . '/step-shell-end.php';
$onboardingPrimaryId = 'onboarding-bio-continue';
$onboardingPrimaryLabel = __('onboarding.continue');
require dirname(__DIR__) . '/step-footer.php';
