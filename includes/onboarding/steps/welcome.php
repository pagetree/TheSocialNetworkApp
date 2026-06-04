<?php

declare(strict_types=1);

$stepTitle = __('onboarding.welcome.title');
$stepSubtitle = __('onboarding.welcome.subtitle');
$stepSectionClass = 'onboarding-welcome';
require dirname(__DIR__) . '/step-shell-start.php';
?>
            <ul class="onboarding-welcome-features">
                <li class="onboarding-welcome-feature">
                    <span class="onboarding-welcome-feature-icon" aria-hidden="true"><i data-lucide="camera"></i></span>
                    <span class="onboarding-welcome-feature-text"><strong><?php echo __e('onboarding.welcome.photo_title'); ?></strong><span><?php echo __e('onboarding.welcome.photo_text'); ?></span></span>
                </li>
                <li class="onboarding-welcome-feature">
                    <span class="onboarding-welcome-feature-icon" aria-hidden="true"><i data-lucide="sparkles"></i></span>
                    <span class="onboarding-welcome-feature-text"><strong><?php echo __e('onboarding.welcome.bio_title'); ?></strong><span><?php echo __e('onboarding.welcome.bio_text'); ?></span></span>
                </li>
                <li class="onboarding-welcome-feature">
                    <span class="onboarding-welcome-feature-icon" aria-hidden="true"><i data-lucide="tags"></i></span>
                    <span class="onboarding-welcome-feature-text"><strong><?php echo __e('onboarding.welcome.interests_title'); ?></strong><span><?php echo __e('onboarding.welcome.interests_text'); ?></span></span>
                </li>
                <li class="onboarding-welcome-feature">
                    <span class="onboarding-welcome-feature-icon" aria-hidden="true"><i data-lucide="users"></i></span>
                    <span class="onboarding-welcome-feature-text"><strong><?php echo __e('onboarding.welcome.people_title'); ?></strong><span><?php echo __e('onboarding.welcome.people_text'); ?></span></span>
                </li>
            </ul>
<?php
require dirname(__DIR__) . '/step-shell-end.php';
$onboardingPrimaryId = 'onboarding-welcome-start';
$onboardingPrimaryLabel = __('onboarding.start_now');
$onboardingPrimaryHref = $url('/onboarding/avatar');
require dirname(__DIR__) . '/step-footer.php';
