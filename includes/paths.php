<?php

declare(strict_types=1);

/**
 * @return array{base: string, path: string, url: callable(string): string}
 */
function appPaths(): array
{
    static $paths = null;

    if ($paths !== null) {
        return $paths;
    }

    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
        $appPath = substr($requestPath, strlen($basePath)) ?: '/';
    } else {
        $appPath = $requestPath;
    }

    if ($appPath === '') {
        $appPath = '/';
    }

    $url = static function (string $uri) use ($basePath): string {
        if ($uri === '') {
            return $basePath ?: '/';
        }

        return $basePath . ($uri[0] === '/' ? $uri : '/' . $uri);
    };

    $paths = [
        'base' => $basePath,
        'path' => $appPath,
        'url' => $url,
    ];

    return $paths;
}
