<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/paths.php';

$appPath = appPaths()['path'];
$filePath = __DIR__ . $appPath;

if ($appPath !== '/' && is_file($filePath)) {
    return false;
}

require __DIR__ . '/index.php';
