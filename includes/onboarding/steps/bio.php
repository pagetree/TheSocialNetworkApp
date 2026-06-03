<?php

declare(strict_types=1);

/** @var array<string, mixed> $currentUser */

$stepTitle = 'Write your bio';
$stepSubtitle = 'Tell people a little about yourself.';
require dirname(__DIR__) . '/step-shell-start.php';

$currentBio = trim((string) ($currentUser['bio'] ?? ''));
?>
            <label class="auth-field onboarding-bio-field">
                <span>Bio</span>
                <textarea class="onboarding-bio-input" id="onboarding-bio-input" rows="5" maxlength="<?php echo PROFILE_BIO_MAX_LENGTH; ?>" placeholder="A few words about you…"><?php echo htmlspecialchars($currentBio, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <span class="onboarding-char-count" id="onboarding-bio-count"><?php echo mb_strlen($currentBio); ?> / <?php echo PROFILE_BIO_MAX_LENGTH; ?></span>
            </label>
            <p class="onboarding-form-error" id="onboarding-step-error" hidden></p>
<?php
require dirname(__DIR__) . '/step-shell-end.php';
$onboardingPrimaryId = 'onboarding-bio-continue';
$onboardingPrimaryLabel = 'Continue';
require dirname(__DIR__) . '/step-footer.php';
