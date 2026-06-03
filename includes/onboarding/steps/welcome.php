<?php

declare(strict_types=1);

$stepTitle = 'Welcome';
$stepSubtitle = 'A few quick choices help us shape a feed that feels relevant and worth opening. Every step is optional; update anything later from your profile.';
$stepSectionClass = 'onboarding-welcome';
require dirname(__DIR__) . '/step-shell-start.php';
?>
            <ul class="onboarding-welcome-features">
                <li class="onboarding-welcome-feature">
                    <span class="onboarding-welcome-feature-icon" aria-hidden="true"><i data-lucide="camera"></i></span>
                    <span class="onboarding-welcome-feature-text"><strong>Profile photo</strong><span>Put a face to your name</span></span>
                </li>
                <li class="onboarding-welcome-feature">
                    <span class="onboarding-welcome-feature-icon" aria-hidden="true"><i data-lucide="sparkles"></i></span>
                    <span class="onboarding-welcome-feature-text"><strong>Bio</strong><span>Say who you are in a line or two</span></span>
                </li>
                <li class="onboarding-welcome-feature">
                    <span class="onboarding-welcome-feature-icon" aria-hidden="true"><i data-lucide="tags"></i></span>
                    <span class="onboarding-welcome-feature-text"><strong>Interests</strong><span>Steer what shows up for you</span></span>
                </li>
                <li class="onboarding-welcome-feature">
                    <span class="onboarding-welcome-feature-icon" aria-hidden="true"><i data-lucide="users"></i></span>
                    <span class="onboarding-welcome-feature-text"><strong>People</strong><span>Follow accounts you&rsquo;ll enjoy</span></span>
                </li>
            </ul>
<?php
require dirname(__DIR__) . '/step-shell-end.php';
$onboardingPrimaryId = 'onboarding-welcome-start';
$onboardingPrimaryLabel = 'Start now';
$onboardingPrimaryHref = $url('/onboarding/avatar');
require dirname(__DIR__) . '/step-footer.php';
