<?php

declare(strict_types=1);

/** @var bool $isLoggedIn */
/** @var callable(string): string $url */
/** @var string $activeNav explore|profile|messages|notifications */
/** @var string|null $contentPageTitle */
/** @var string $mainClass */

$activeNav = $activeNav ?? 'explore';
$onboardingLayout = !empty($onboardingLayout);
$appMainClass = 'app-main app-main--with-right-sidebar';
?>
<body<?php
if (!$isLoggedIn) {
    echo ' class="auth-locked"';
} elseif ($onboardingLayout) {
    echo ' class="onboarding-page"';
}
?>>
    <?php if (!$isLoggedIn) {
        require dirname(__DIR__, 2) . '/auth/login-modal.php';
    } ?>
    <div class="app-shell"<?php echo $isLoggedIn ? '' : ' aria-hidden="true"'; ?>>
        <div class="app-container<?php echo $onboardingLayout ? ' app-container--onboarding' : ''; ?>">
            <?php if (!$onboardingLayout) : ?>
            <div class="<?php echo htmlspecialchars($appMainClass, ENT_QUOTES, 'UTF-8'); ?>">
                <?php require __DIR__ . '/sidebar.php'; ?>
            <?php endif; ?>
                <main class="<?php echo htmlspecialchars($mainClass, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($onboardingLayout) {
                require __DIR__ . '/onboarding-chrome.php';
            } else { ?>
                    <div class="app-content-body">
                    <?php require __DIR__ . '/content-header.php'; ?>
            <?php } ?>
                    <div class="onboarding-content-card">
