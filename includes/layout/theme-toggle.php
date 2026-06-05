<?php

declare(strict_types=1);

$themeToggleClass = $themeToggleClass ?? 'theme-toggle topbar-link';
?>
<button
    type="button"
    class="<?php echo htmlspecialchars($themeToggleClass, ENT_QUOTES, 'UTF-8'); ?>"
    aria-label="<?php echo __e('theme.switch_to_light'); ?>"
    aria-pressed="false"
>
    <i data-lucide="sun" class="theme-toggle-icon theme-toggle-icon--to-light" aria-hidden="true"></i>
    <i data-lucide="moon" class="theme-toggle-icon theme-toggle-icon--to-dark" aria-hidden="true"></i>
</button>
