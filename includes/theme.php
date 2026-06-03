<?php

declare(strict_types=1);

const APP_THEME_COOKIE = 'app_theme';
const APP_THEME_DEFAULT = 'dark';

function resolveAppTheme(): string
{
    $raw = $_COOKIE[APP_THEME_COOKIE] ?? APP_THEME_DEFAULT;

    return $raw === 'light' ? 'light' : 'dark';
}

function renderThemeHeadScript(): void
{
    echo '<script>(function(){var m=document.cookie.match(/(?:^|;\\s*)app_theme=(dark|light)/);var t=m&&m[1]==="light"?"light":"dark";document.documentElement.setAttribute("data-theme",t);document.documentElement.style.colorScheme=t;})();</script>' . "\n";
}
