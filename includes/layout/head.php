<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var callable(string): string $url */

$appTheme = resolveAppTheme();
?>
<!doctype html>
<html lang="en" data-theme="<?php echo htmlspecialchars($appTheme, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php renderThemeHeadScript(); ?>
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($url('/assets/img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;500;600;700;800;900;1000&family=Nunito+Sans:opsz,wght@6..12,200;6..12,300;6..12,400;6..12,500;6..12,600;6..12,700;6..12,800;6..12,900;6..12,1000&display=swap">
<?php
require __DIR__ . '/stylesheets.php';
renderAppStylesheets($url);
?>
</head>
