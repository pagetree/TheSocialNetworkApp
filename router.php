<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/paths.php';

$basePath = appBasePath();
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
    $appPath = substr($requestPath, strlen($basePath)) ?: '/';
} else {
    $appPath = $requestPath;
}

if ($appPath === '' || !str_starts_with($appPath, '/')) {
    $appPath = $appPath === '' ? '/' : '/' . ltrim($appPath, '/');
}

$filePath = __DIR__ . $appPath;

if ($appPath !== '/' && is_file($filePath)) {
    return false;
}

require __DIR__ . '/index.php';
