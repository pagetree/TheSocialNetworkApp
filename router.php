<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/paths.php';

function isSensitivePublicPath(string $appPath): bool
{
    $blockedPrefixes = [
        '/includes/',
        '/config/',
        '/vendor/',
        '/sql/',
    ];

    foreach ($blockedPrefixes as $prefix) {
        if (str_starts_with($appPath, $prefix)) {
            return true;
        }
    }

    if ($appPath === '/composer.json' || $appPath === '/composer.lock') {
        return true;
    }

    if (str_contains($appPath, '.env')) {
        return true;
    }

    return (bool) preg_match('#^/auth/.+\.php$#', $appPath);
}

$appPath = appRequestPath();

if ($appPath === '/profile.php') {
    require __DIR__ . '/index.php';
    return true;
}

if (isSensitivePublicPath($appPath)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    return true;
}

$filePath = __DIR__ . $appPath;

if ($appPath !== '/' && is_file($filePath)) {
    return false;
}

require __DIR__ . '/index.php';
