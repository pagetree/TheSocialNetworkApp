<?php

declare(strict_types=1);

/** @var array<string, mixed> $currentUser */

$stepTitle = __('onboarding.avatar.title');
$stepSubtitle = __('onboarding.avatar.subtitle');
require dirname(__DIR__) . '/step-shell-start.php';

$storedAvatar = trim((string) ($currentUser['avatar_url'] ?? ''));
$hasStoredAvatar = $storedAvatar !== '';
if ($hasStoredAvatar) {
    $previewUrl = isExternalMediaUrl($storedAvatar) ? $storedAvatar : $url($storedAvatar);
} else {
    $previewUrl = '';
}
?>
            <div class="onboarding-avatar-preview-wrap<?php echo $hasStoredAvatar ? ' has-preview' : ''; ?>">
                <div
                    class="onboarding-avatar-preview-placeholder"
                    id="onboarding-avatar-placeholder"
                    <?php echo $hasStoredAvatar ? ' hidden' : ''; ?>
                    aria-hidden="<?php echo $hasStoredAvatar ? 'true' : 'false'; ?>"
                >
                    <i data-lucide="user-round" aria-hidden="true"></i>
                </div>
                <img
                    class="onboarding-avatar-preview"
                    id="onboarding-avatar-preview"
                    src="<?php echo $hasStoredAvatar ? htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') : ''; ?>"
                    alt="<?php echo __e('onboarding.avatar.preview_alt'); ?>"
                    width="140"
                    height="140"
                    decoding="async"
                    <?php echo $hasStoredAvatar ? '' : ' hidden'; ?>
                >
            </div>
            <label class="onboarding-upload-btn">
                <input type="file" id="onboarding-avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
                <i data-lucide="upload" aria-hidden="true"></i>
                <span><?php echo __e('onboarding.avatar.upload'); ?></span>
            </label>
            <p class="onboarding-form-error" id="onboarding-step-error" hidden></p>
<?php
require dirname(__DIR__) . '/step-shell-end.php';
$onboardingPrimaryId = 'onboarding-avatar-continue';
$onboardingPrimaryLabel = __('onboarding.continue');
require dirname(__DIR__) . '/step-footer.php';
