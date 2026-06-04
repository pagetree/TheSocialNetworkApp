<?php

declare(strict_types=1);

$currentLocale = appLocale();
?>
<div class="lang-switcher" role="navigation" aria-label="<?php echo __e('lang.switch'); ?>">
<?php foreach (APP_SUPPORTED_LOCALES as $localeCode) :
    $isActive = $localeCode === $currentLocale;
    $labelKey = $localeCode === 'es' ? 'lang.spanish' : 'lang.english';
    ?>
    <a
        href="<?php echo htmlspecialchars(localeSwitchUrl($localeCode), ENT_QUOTES, 'UTF-8'); ?>"
        class="lang-switcher-link<?php echo $isActive ? ' is-active' : ''; ?>"
        hreflang="<?php echo htmlspecialchars($localeCode, ENT_QUOTES, 'UTF-8'); ?>"
        lang="<?php echo htmlspecialchars($localeCode, ENT_QUOTES, 'UTF-8'); ?>"
        <?php echo $isActive ? 'aria-current="true"' : ''; ?>
    ><?php echo __e($labelKey); ?></a>
<?php endforeach; ?>
</div>
