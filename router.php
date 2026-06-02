<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/paths.php';

$appPath = appRequestPath();

if ($appPath === '/profile.php') {
    require __DIR__ . '/index.php';
    return true;
}

$filePath = __DIR__ . $appPath;

if ($appPath !== '/' && is_file($filePath)) {
    return false;
}

require __DIR__ . '/index.php';
