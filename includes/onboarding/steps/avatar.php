<?php

declare(strict_types=1);

/** @var array<string, mixed> $currentUser */

$stepTitle = 'Add a profile photo';
$stepSubtitle = 'Pick a photo or upload your own. You can change it later.';
require dirname(__DIR__) . '/step-shell-start.php';

$currentAvatarUrl = userMediaUrl($currentUser, 'avatar_url', $url);
$presetAvatars = onboardingPresetAvatarUrls();
?>
            <div class="onboarding-avatar-preview-wrap">
                <img class="onboarding-avatar-preview" id="onboarding-avatar-preview" src="<?php echo htmlspecialchars($currentAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Your profile photo preview" width="140" height="140" decoding="async">
            </div>
            <div class="onboarding-avatar-presets" role="list" aria-label="Suggested profile photos">
                <?php foreach ($presetAvatars as $presetIndex => $presetUrl) : ?>
                <button type="button" class="onboarding-avatar-preset<?php echo $presetUrl === $currentAvatarUrl ? ' is-selected' : ''; ?>" data-preset-url="<?php echo htmlspecialchars($presetUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Use suggested photo <?php echo $presetIndex + 1; ?>">
                    <img src="<?php echo htmlspecialchars($presetUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                </button>
                <?php endforeach; ?>
            </div>
            <label class="onboarding-upload-btn">
                <input type="file" id="onboarding-avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
                <i data-lucide="upload" aria-hidden="true"></i>
                <span>Upload photo</span>
            </label>
            <p class="onboarding-form-error" id="onboarding-step-error" hidden></p>
<?php
require dirname(__DIR__) . '/step-shell-end.php';
$onboardingPrimaryId = 'onboarding-avatar-continue';
$onboardingPrimaryLabel = 'Continue';
require dirname(__DIR__) . '/step-footer.php';
